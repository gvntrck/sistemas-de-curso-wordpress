<?php
/*
Snippet Name: Shortcode Cadastro de Usuário
Description: Adiciona o shortcode [cadastro-usuario] para registro de usuários com campos personalizados.
*/

// Hook para processar o download antes de qualquer HTML ser enviado
add_action('init', 'cadastro_usuario_handle_csv_download');

function cadastro_usuario_handle_csv_download()
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

function cadastro_usuario_convert_date_to_iso($date)
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


function cadastro_usuario_shortcode()
{
    $output = '';
    $message = '';
    $import_message = '';

    // [PROCESSAR FORMULÁRIO MANUAL]
    if (isset($_POST['submit_cadastro_usuario']) && isset($_POST['cadastro_nonce_field']) && wp_verify_nonce($_POST['cadastro_nonce_field'], 'cadastro_usuario_action')) {
        // [CÓDIGO DE CADASTRO MANUAL MANTIDO AQUI]
        $nome = sanitize_text_field($_POST['nome']);
        $sobrenome = sanitize_text_field($_POST['sobrenome']);
        $email = sanitize_email($_POST['email']);
        $cpf = sanitize_text_field($_POST['cpf']);
        $aniversario = sanitize_text_field($_POST['aniversario']);
        $aniversario = cadastro_usuario_convert_date_to_iso($aniversario);

        $instagram = sanitize_text_field($_POST['instagram']);
        $cep = sanitize_text_field($_POST['cep']);
        $rua = sanitize_text_field($_POST['rua']);
        $numero = sanitize_text_field($_POST['numero']);
        $complemento = sanitize_text_field($_POST['complemento']);
        $bairro = sanitize_text_field($_POST['bairro']);
        $cidade = sanitize_text_field($_POST['cidade']);
        $estado = sanitize_text_field($_POST['estado']);

        if (empty($email) || empty($nome)) {
            $message = '<div class="cadastro-alert cadastro-error">Por favor, preencha o Nome e o Email.</div>';
        } elseif (email_exists($email)) {
            $message = '<div class="cadastro-alert cadastro-error">Este email já está cadastrado.</div>';
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

                $message = '<div class="cadastro-alert cadastro-success">Usuário cadastrado com sucesso!</div>';
            } else {
                $message = '<div class="cadastro-alert cadastro-error">Erro ao criar usuário: ' . $user_id->get_error_message() . '</div>';
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
                        // Remove BOM do primeiro elemento se existir (issue com arquivos salvos com UTF-8 BOM)
                        if (isset($data[0])) {
                            $bom = pack('H*', 'EFBBBF');
                            $data[0] = preg_replace("/^$bom/", '', $data[0]);
                        }

                        $headers = array_map('strtolower', $data); // Normaliza headers para lowercase
                        continue;
                    }

                    // Mapeia colunas pelo header
                    $user_data = [];
                    foreach ($headers as $index => $key) {
                        $user_data[trim($key)] = isset($data[$index]) ? trim($data[$index]) : '';
                    }

                    // Validar campo email obrigatório
                    if (empty($user_data['email'])) {
                        $error_list[] = "Linha $row: Email não encontrado.";
                        continue;
                    }

                    if (email_exists($user_data['email'])) {
                        $error_list[] = "Linha $row: Email " . $user_data['email'] . " já existe.";
                        continue;
                    }

                    // Criação do usuário
                    $password = wp_generate_password(12, false);
                    $new_user_id = wp_create_user($user_data['email'], $password, $user_data['email']);

                    if (!is_wp_error($new_user_id)) {
                        wp_update_user([
                            'ID' => $new_user_id,
                            'first_name' => isset($user_data['nome']) ? $user_data['nome'] : '',
                            'last_name' => isset($user_data['sobrenome']) ? $user_data['sobrenome'] : '',
                            'role' => 'aluno'
                        ]);

                        // Lista de meta keys esperadas
                        $meta_keys = ['cpf', 'aniversario', 'instagram', 'cep', 'rua', 'numero', 'complemento', 'bairro', 'cidade', 'estado'];

                        if (isset($user_data['aniversario'])) {
                            $user_data['aniversario'] = cadastro_usuario_convert_date_to_iso($user_data['aniversario']);
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

                $import_message .= '<div class="cadastro-alert cadastro-success">Importação concluída! ' . $success_count . ' usuários criados.</div>';
                if (!empty($error_list)) {
                    $import_message .= '<div class="cadastro-alert cadastro-warning">Alguns erros ocorreram:<ul>';
                    foreach ($error_list as $err) {
                        $import_message .= '<li>' . esc_html($err) . '</li>';
                    }
                    $import_message .= '</ul></div>';
                }
            } else {
                $import_message = '<div class="cadastro-alert cadastro-error">Erro ao abrir o arquivo CSV.</div>';
            }
        } else {
            $import_message = '<div class="cadastro-alert cadastro-error">Por favor, selecione um arquivo CSV.</div>';
        }
    }


    ob_start();
    ?>
    <style>
        :root {
            /* Variaveis Globais (Baseadas em modelo.html) */
            --bg-color: #121212;
            --bg-card: #121212;
            --bg-header: linear-gradient(180deg, #1f1f1f 0%, #161616 100%);
            --bg-input-hover: #1a1a1a;
            --bg-input-focus: #222;
            --bg-footer: #161616;

            --text-color: #e0e0e0;
            --text-heading: #fff;
            --text-muted: #888;
            --text-label: #666;

            --border-color: #2a2a2a;
            --border-input: #333;
            --border-input-hover: #444;

            --accent-color: #FDC110;
            --accent-shadow: rgba(108, 92, 231, 0.2);

            --success-bg: rgba(46, 213, 115, 0.15);
            --success-text: #2ed573;
            --success-border: rgba(46, 213, 115, 0.2);
            --error-bg: rgba(255, 71, 87, 0.15);
            --error-text: #ff4757;
            --error-border: rgba(255, 71, 87, 0.2);
            --warning-bg: rgba(255, 165, 2, 0.15);
            --warning-text: #ffa502;
            --warning-border: rgba(255, 165, 2, 0.2);

            --font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            --radius-card: 12px;
            --radius-btn: 6px;
        }

        /* Container Card */
        .mc-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: var(--bg-card);
            color: var(--text-color);
            border-radius: var(--radius-card);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            border: 1px solid var(--border-color);
            font-family: var(--font-family);
        }

        .mc-header {
            background: var(--bg-header);
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
        }

        .mc-header h2 {
            margin: 0;
            color: var(--text-heading);
            font-size: 1.8rem;
        }

        .mc-body {
            padding: 30px;
        }

        /* Tabs - Adaptado para Dark Theme */
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

        /* Inputs & Form Groups */
        .mc-field-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 20px;
        }

        .mc-field-label {
            font-size: 0.75rem;
            color: var(--text-label);
            margin-bottom: 8px;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .mc-input {
            background-color: transparent;
            border: 1px solid transparent;
            border-bottom: 1px solid var(--border-input);
            color: #fff;
            padding: 10px 0;
            font-size: 1.1rem;
            width: 100%;
            font-family: inherit;
            transition: all 0.3s ease;
            outline: none;
            border-radius: 4px;
        }

        .mc-input:hover {
            background-color: var(--bg-input-hover);
            border-color: var(--border-input-hover);
            padding-left: 10px;
        }

        .mc-input:focus {
            background-color: var(--bg-input-focus);
            border-color: var(--accent-color);
            padding-left: 10px;
            box-shadow: 0 0 0 3px var(--accent-shadow);
        }

        input[type="file"].mc-input {
            padding: 10px;
        }

        /* Grid System */
        .mc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .mc-full-width {
            grid-column: 1 / -1;
        }

        /* Buttons */
        .mc-btn-save {
            background-color: var(--accent-color);
            color: #000;
            /* Contraste melhor com o amarelo */
            border: none;
            padding: 12px 30px;
            font-size: 1rem;
            font-weight: 700;
            border-radius: var(--radius-btn);
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 10px var(--accent-shadow);
            text-decoration: none;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 1px;
            width: 100%;
        }

        .mc-btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px var(--accent-shadow);
        }

        .mc-btn-secondary {
            background-color: transparent;
            border: 1px solid var(--border-input);
            color: var(--text-muted);
            padding: 10px 20px;
            font-size: 0.9rem;
            border-radius: var(--radius-btn);
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }

        .mc-btn-secondary:hover {
            border-color: var(--text-color);
            color: var(--text-color);
        }

        /* Alerts */
        .mc-alert,
        .cadastro-alert {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 6px;
            text-align: left;
            font-weight: 500;
            border: 1px solid;
            display: flex;
            align-items: center;
        }

        .cadastro-success {
            background-color: var(--success-bg);
            color: var(--success-text);
            border-color: var(--success-border);
        }

        .cadastro-error {
            background-color: var(--error-bg);
            color: var(--error-text);
            border-color: var(--error-border);
        }

        .cadastro-warning {
            background-color: var(--warning-bg);
            color: var(--warning-text);
            border-color: var(--warning-border);
        }

        .mc-section-title {
            font-size: 1rem;
            color: var(--accent-color);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
            margin-bottom: 25px;
            margin-top: 30px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 600;
        }

        /* Mobile overrides */
        @media (max-width: 600px) {
            .mc-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="mc-container">
        <div class="mc-header">
            <h2>Cadastro de Aluno</h2>
        </div>

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
                            <input type="text" name="cpf" id="cpf" class="mc-input"
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
                            <input type="text" name="cep" id="cep" class="mc-input"
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
                        para
                        cadastrar em massa.</p>
                    <a href="?action=download_cadastro_csv_template" class="mc-btn-secondary" target="_blank">
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
                content[i].style.display = "none"; // Fallback
            }
            tablinks = document.getElementsByClassName("cadastro-tab");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }

            var activeContent = document.getElementById(tabName);
            activeContent.style.display = "block";
            // Pequeno delay para permitir a transição de opacidade da classe active
            setTimeout(function () {
                activeContent.classList.add("active");
            }, 10);

            evt.currentTarget.classList.add("active");
        }

        // Mantém máscaras e auto-preenchimento
        document.addEventListener('DOMContentLoaded', function () {
            // Inicializar tabs corretamente (display block para o ativo)
            var activeTab = document.querySelector('.cadastro-content.active');
            if (activeTab) activeTab.style.display = 'block';

            var cpfInput = document.getElementById('cpf');
            if (cpfInput) {
                cpfInput.addEventListener('input', function (e) {
                    var v = e.target.value.replace(/\D/g, '').slice(0, 11);
                    v = v.replace(/^(\d{3})(\d)/, '$1.$2');
                    v = v.replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3');
                    v = v.replace(/\.(\d{3})(\d)/, '.$1-$2');
                    e.target.value = v;
                });
            }
            var cepInput = document.getElementById('cep');
            if (cepInput) {
                cepInput.addEventListener('blur', function () {
                    var cep = cepInput.value.replace(/\D/g, '').slice(0, 8);
                    if (cep.length === 8) {
                        // Visual feedback de carregamento
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

                                    // Focar no número após preencher
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
add_shortcode('cadastro-usuario', 'cadastro_usuario_shortcode');
