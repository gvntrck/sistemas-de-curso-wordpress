<?php
if (!defined('ABSPATH')) {
    exit;
}

class System_Cursos_Shortcode_Cadastro
{
    /**
     * class-shortcode-cadastro-usuario.php
     *
     * Shortcode [cadastro-usuario]
     * Renderiza um formulário frontend para cadastro de novos alunos.
     * Suporta cadastro manual e importação em massa via arquivo CSV (exclusivo para administradores/gestores).
     *
     * @package SistemaCursos
     * @version 1.0.8
     */
    public function __construct()
    {
        add_shortcode('cadastro-usuario', [$this, 'render_shortcode']);
        add_action('init', [$this, 'handle_csv_download']);
    }

    public function handle_csv_download()
    {
        if (isset($_GET['action']) && $_GET['action'] == 'download_cadastro_csv_template') {
            // Limpa qualquer buffer de saída anterior para evitar HTML no arquivo
            if (ob_get_level()) {
                ob_end_clean();
            }

            $filename = 'modelo_importacao_usuarios.csv';
            $header = ['nome', 'sobrenome', 'email', 'cpf', 'aniversario', 'instagram', 'cep', 'rua', 'numero', 'complemento', 'bairro', 'cidade', 'estado'];

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $filename);
            header('Pragma: no-cache');
            header('Expires: 0');

            $output = fopen('php://output', 'w');

            // Adiciona BOM para abrir corretamente no Excel (opcional, mas recomendado para UTF-8)
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($output, $header);
            fclose($output);
            exit;
        }
    }

    public function convert_date_to_iso($date)
    {
        if (empty($date))
            return '';

        // Tenta formato BR (dd/mm/yyyy)
        $d = DateTime::createFromFormat('d/m/Y', $date);
        if ($d && $d->format('d/m/Y') === $date) {
            return $d->format('Y-m-d');
        }

        // Tenta formato ISO (yyyy-mm-dd) para validar
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if ($d && $d->format('Y-m-d') === $date) {
            return $date;
        }

        return $date; // Retorna original se não conseguir converter
    }

    public function render_shortcode()
    {
        $output = '';
        $message = '';
        $import_message = '';

        // [PROCESSAR FORMULÁRIO MANUAL]
        if (isset($_POST['submit_cadastro_usuario']) && isset($_POST['cadastro_nonce_field']) && wp_verify_nonce($_POST['cadastro_nonce_field'], 'cadastro_usuario_action')) {
            $nome = sanitize_text_field($_POST['nome']);
            $sobrenome = sanitize_text_field($_POST['sobrenome']);
            $email = sanitize_email($_POST['email']);
            $cpf = sanitize_text_field($_POST['cpf']);
            $aniversario = sanitize_text_field($_POST['aniversario']);
            $aniversario = $this->convert_date_to_iso($aniversario);

            $instagram = sanitize_text_field($_POST['instagram']);
            $cep = sanitize_text_field($_POST['cep']);
            $rua = sanitize_text_field($_POST['rua']);
            $numero = sanitize_text_field($_POST['numero']);
            $complemento = sanitize_text_field($_POST['complemento']);
            $bairro = sanitize_text_field($_POST['bairro']);
            $cidade = sanitize_text_field($_POST['cidade']);
            $estado = sanitize_text_field($_POST['estado']);

            if (empty($email) || empty($nome)) {
                $message = '<div class="mc-alert mc-error">Por favor, preencha o Nome e o Email.</div>';
            } elseif (email_exists($email)) {
                $message = '<div class="mc-alert mc-error">Este email já está cadastrado.</div>';
            } else {
                $password = wp_generate_password(12, false);
                $user_id = wp_create_user($email, $password, $email);

                if (!is_wp_error($user_id)) {
                    wp_update_user(['ID' => $user_id, 'first_name' => $nome, 'last_name' => $sobrenome, 'role' => 'aluno']);

                    update_user_meta($user_id, 'cpf', $cpf);
                    update_user_meta($user_id, 'aniversario', $aniversario);
                    update_user_meta($user_id, 'instagram', $instagram);
                    update_user_meta($user_id, 'cep', $cep);
                    update_user_meta($user_id, 'rua', $rua);
                    update_user_meta($user_id, 'numero', $numero);
                    update_user_meta($user_id, 'complemento', $complemento);
                    update_user_meta($user_id, 'bairro', $bairro);
                    update_user_meta($user_id, 'cidade', $cidade);
                    update_user_meta($user_id, 'estado', $estado);

                    $message = '<div class="mc-alert mc-success">Usuário cadastrado com sucesso!</div>';
                } else {
                    $message = '<div class="mc-alert mc-error">Erro ao criar usuário: ' . $user_id->get_error_message() . '</div>';
                }
            }
        }

        // [PROCESSAR IMPORTAÇÃO CSV]
        if (isset($_POST['submit_csv_import']) && isset($_POST['import_nonce_field']) && wp_verify_nonce($_POST['import_nonce_field'], 'import_usuario_action')) {
            if (!empty($_FILES['csv_file']['tmp_name'])) {
                $file = $_FILES['csv_file']['tmp_name'];
                $handle = fopen($file, "r");

                if ($handle !== FALSE) {
                    $row = 0;
                    $success_count = 0;
                    $error_list = [];
                    $headers = [];

                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        $row++;

                        if ($row == 1) { // Header
                            if (isset($data[0])) {
                                $bom = pack('H*', 'EFBBBF');
                                $data[0] = preg_replace("/^$bom/", '', $data[0]);
                            }

                            $headers = array_map('strtolower', $data);
                            continue;
                        }

                        $user_data = [];
                        foreach ($headers as $index => $key) {
                            $user_data[trim($key)] = isset($data[$index]) ? trim($data[$index]) : '';
                        }

                        if (empty($user_data['email'])) {
                            $error_list[] = "Linha $row: Email não encontrado.";
                            continue;
                        }

                        if (email_exists($user_data['email'])) {
                            $error_list[] = "Linha $row: Email " . $user_data['email'] . " já existe.";
                            continue;
                        }

                        $password = wp_generate_password(12, false);
                        $new_user_id = wp_create_user($user_data['email'], $password, $user_data['email']);

                        if (!is_wp_error($new_user_id)) {
                            wp_update_user([
                                'ID' => $new_user_id,
                                'first_name' => isset($user_data['nome']) ? $user_data['nome'] : '',
                                'last_name' => isset($user_data['sobrenome']) ? $user_data['sobrenome'] : '',
                                'role' => 'aluno'
                            ]);

                            $meta_keys = ['cpf', 'aniversario', 'instagram', 'cep', 'rua', 'numero', 'complemento', 'bairro', 'cidade', 'estado'];

                            if (isset($user_data['aniversario'])) {
                                $user_data['aniversario'] = $this->convert_date_to_iso($user_data['aniversario']);
                            }

                            foreach ($meta_keys as $key) {
                                if (isset($user_data[$key])) {
                                    update_user_meta($new_user_id, $key, $user_data[$key]);
                                }
                            }
                            $success_count++;
                        } else {
                            $error_list[] = "Linha $row: " . $new_user_id->get_error_message();
                        }
                    }
                    fclose($handle);

                    $import_message .= '<div class="mc-alert mc-success">Importação concluída! ' . $success_count . ' usuários criados.</div>';
                    if (!empty($error_list)) {
                        $import_message .= '<div class="mc-alert mc-error">Alguns erros ocorreram:<ul>';
                        foreach ($error_list as $err) {
                            $import_message .= '<li>' . esc_html($err) . '</li>';
                        }
                        $import_message .= '</ul></div>';
                    }
                } else {
                    $import_message = '<div class="mc-alert mc-error">Erro ao abrir o arquivo CSV.</div>';
                }
            } else {
                $import_message = '<div class="mc-alert mc-error">Por favor, selecione um arquivo CSV.</div>';
            }
        }

        ob_start();
        ?>

        <!-- CSS já carregado via assets/css/style.css -->

        <div class="mc-container">
            <div class="mc-header">
                <h2>Cadastro de Aluno</h2>
            </div>

            <style>
                /* Tabs Específicas (Não Globais ainda, mas visualmente integradas) */
                .cadastro-tabs {
                    display: flex;
                    background: #1a1a1a;
                    border-bottom: 1px solid var(--border-color);
                }

                .cadastro-tab {
                    padding: 15px 25px;
                    cursor: pointer;
                    color: var(--text-muted);
                    font-weight: 600;
                    transition: all 0.3s ease;
                    flex: 1;
                    text-align: center;
                    border-bottom: 2px solid transparent;
                }

                .cadastro-tab:hover {
                    color: var(--text-color);
                    background: rgba(255, 255, 255, 0.05);
                }

                .cadastro-tab.active {
                    color: var(--accent-color);
                    background: var(--bg-card);
                    border-bottom: 2px solid var(--accent-color);
                }

                .cadastro-content {
                    display: none;
                    animation: fadeIn 0.4s ease;
                }

                .cadastro-content.active {
                    display: block;
                }

                @keyframes fadeIn {
                    from {
                        opacity: 0;
                    }

                    to {
                        opacity: 1;
                    }
                }
            </style>

            <div class="cadastro-tabs">
                <div class="cadastro-tab active" onclick="openTab(event, 'tab-manual')">Cadastro Manual</div>
                <div class="cadastro-tab" onclick="openTab(event, 'tab-import')">Importar CSV</div>
            </div>

            <div class="mc-body">
                <!-- ABA MANUAL -->
                <div id="tab-manual" class="cadastro-content active">
                    <?php echo $message; ?>

                    <form method="post">
                        <?php wp_nonce_field('cadastro_usuario_action', 'cadastro_nonce_field'); ?>

                        <h3 class="mc-section-title">Dados Pessoais</h3>

                        <div class="mc-grid">
                            <div class="mc-field-group">
                                <label class="mc-field-label">Nome *</label>
                                <input type="text" name="nome" class="mc-input" required
                                    value="<?php echo isset($_POST['nome']) ? esc_attr($_POST['nome']) : ''; ?>">
                            </div>
                            <div class="mc-field-group">
                                <label class="mc-field-label">Sobrenome</label>
                                <input type="text" name="sobrenome" class="mc-input"
                                    value="<?php echo isset($_POST['sobrenome']) ? esc_attr($_POST['sobrenome']) : ''; ?>">
                            </div>
                        </div>

                        <div class="mc-grid">
                            <div class="mc-field-group">
                                <label class="mc-field-label">CPF</label>
                                <input type="text" name="cpf" id="cpf" class="mc-input mask-cpf"
                                    value="<?php echo isset($_POST['cpf']) ? esc_attr($_POST['cpf']) : ''; ?>">
                            </div>
                            <div class="mc-field-group">
                                <label class="mc-field-label">Data de Nascimento</label>
                                <input type="date" name="aniversario" class="mc-input"
                                    value="<?php echo isset($_POST['aniversario']) ? esc_attr($_POST['aniversario']) : ''; ?>">
                            </div>
                        </div>

                        <div class="mc-grid">
                            <div class="mc-field-group">
                                <label class="mc-field-label">Email *</label>
                                <input type="email" name="email" class="mc-input" required
                                    value="<?php echo isset($_POST['email']) ? esc_attr($_POST['email']) : ''; ?>">
                            </div>
                            <div class="mc-field-group">
                                <label class="mc-field-label">Instagram</label>
                                <input type="text" name="instagram" class="mc-input"
                                    value="<?php echo isset($_POST['instagram']) ? esc_attr($_POST['instagram']) : ''; ?>">
                            </div>
                        </div>

                        <h3 class="mc-section-title">Endereço</h3>

                        <div class="mc-grid">
                            <div class="mc-field-group">
                                <label class="mc-field-label">CEP</label>
                                <input type="text" name="cep" id="cep" class="mc-input mask-cep"
                                    value="<?php echo isset($_POST['cep']) ? esc_attr($_POST['cep']) : ''; ?>">
                            </div>
                            <div class="mc-field-group" style="grid-column: span 2;">
                                <label class="mc-field-label">Rua</label>
                                <input type="text" name="rua" id="rua" class="mc-input"
                                    value="<?php echo isset($_POST['rua']) ? esc_attr($_POST['rua']) : ''; ?>">
                            </div>
                        </div>

                        <div class="mc-grid">
                            <div class="mc-field-group">
                                <label class="mc-field-label">Número</label>
                                <input type="text" name="numero" class="mc-input"
                                    value="<?php echo isset($_POST['numero']) ? esc_attr($_POST['numero']) : ''; ?>">
                            </div>
                            <div class="mc-field-group">
                                <label class="mc-field-label">Complemento</label>
                                <input type="text" name="complemento" class="mc-input"
                                    value="<?php echo isset($_POST['complemento']) ? esc_attr($_POST['complemento']) : ''; ?>">
                            </div>
                            <div class="mc-field-group">
                                <label class="mc-field-label">Bairro</label>
                                <input type="text" name="bairro" id="bairro" class="mc-input"
                                    value="<?php echo isset($_POST['bairro']) ? esc_attr($_POST['bairro']) : ''; ?>">
                            </div>
                        </div>

                        <div class="mc-grid">
                            <div class="mc-field-group">
                                <label class="mc-field-label">Cidade</label>
                                <input type="text" name="cidade" id="cidade" class="mc-input"
                                    value="<?php echo isset($_POST['cidade']) ? esc_attr($_POST['cidade']) : ''; ?>">
                            </div>
                            <div class="mc-field-group">
                                <label class="mc-field-label">Estado</label>
                                <input type="text" name="estado" id="estado" class="mc-input"
                                    value="<?php echo isset($_POST['estado']) ? esc_attr($_POST['estado']) : ''; ?>">
                            </div>
                        </div>

                        <div class="mc-field-group" style="margin-top: 20px;">
                            <input type="submit" name="submit_cadastro_usuario" class="mc-btn-save" value="Realizar Cadastro">
                        </div>
                    </form>
                </div>

                <!-- ABA IMPORTAR -->
                <div id="tab-import" class="cadastro-content">
                    <?php echo $import_message; ?>

                    <div style="text-align: center; margin-bottom: 30px;">
                        <p style="color: var(--text-muted);">Baixe o modelo, preencha com os dados dos alunos e faça o upload
                            para cadastrar em massa.</p>
                        <a href="?action=download_cadastro_csv_template" class="mc-btn-save"
                            style="background-color: transparent; border: 1px solid var(--border-input); color: var(--text-muted); width: auto; display: inline-block; padding: 10px 20px;"
                            target="_blank">
                            <span style="margin-right: 5px;">⬇️</span> Baixar Modelo CSV
                        </a>
                    </div>

                    <form method="post" enctype="multipart/form-data"
                        style="border: 1px dashed var(--border-color); padding: 30px; border-radius: 8px; text-align: center;">
                        <?php wp_nonce_field('import_usuario_action', 'import_nonce_field'); ?>

                        <div class="mc-field-group">
                            <label class="mc-field-label" style="margin-bottom: 15px; display: block;">Selecione o arquivo
                                CSV</label>
                            <input type="file" name="csv_file" accept=".csv" required class="mc-input"
                                style="border: none; padding: 10px; width: auto; margin: 0 auto;">
                        </div>

                        <div class="mc-field-group" style="margin-top: 20px;">
                            <input type="submit" name="submit_csv_import" class="mc-btn-save" value="Importar Usuários"
                                style="max-width: 300px;">
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            function openTab(evt, tabName) {
                var i, content, tablinks;
                content = document.getElementsByClassName("cadastro-content");
                for (i = 0; i < content.length; i++) {
                    content[i].classList.remove("active");
                    content[i].style.display = "none";
                }
                tablinks = document.getElementsByClassName("cadastro-tab");
                for (i = 0; i < tablinks.length; i++) {
                    tablinks[i].classList.remove("active");
                }

                var activeContent = document.getElementById(tabName);
                activeContent.style.display = "block";
                setTimeout(function () {
                    activeContent.classList.add("active");
                }, 10);

                evt.currentTarget.classList.add("active");
            }

            document.addEventListener('DOMContentLoaded', function () {
                var activeTab = document.querySelector('.cadastro-content.active');
                if (activeTab) activeTab.style.display = 'block';

                // Fetch CEP Logic (Specific to this component, but can be moved to centralized script if more widespread)
                var cepInput = document.getElementById('cep');
                if (cepInput) {
                    cepInput.addEventListener('blur', function () {
                        var cep = cepInput.value.replace(/\D/g, '').slice(0, 8);
                        if (cep.length === 8) {
                            document.body.style.cursor = 'wait';

                            fetch('https://viacep.com.br/ws/' + cep + '/json/')
                                .then(r => r.json())
                                .then(data => {
                                    document.body.style.cursor = 'default';
                                    if (!data.erro) {
                                        if (document.getElementById('rua')) document.getElementById('rua').value = data.logradouro || '';
                                        if (document.getElementById('bairro')) document.getElementById('bairro').value = data.bairro || '';
                                        if (document.getElementById('cidade')) document.getElementById('cidade').value = data.localidade || '';
                                        if (document.getElementById('estado')) document.getElementById('estado').value = data.uf || '';
                                        if (document.querySelector('input[name="numero"]')) document.querySelector('input[name="numero"]').focus();
                                    }
                                })
                                .catch(e => {
                                    console.error(e);
                                    document.body.style.cursor = 'default';
                                });
                        }
                    });
                }
            });
        </script>
        <?php
        $output .= ob_get_clean();
        return $output;
    }
}
