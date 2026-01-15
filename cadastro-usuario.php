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
        .cadastro-container {
            max-width: 600px;
            margin: 0 auto;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: sans-serif;
        }

        .cadastro-tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            background: #eee;
        }

        .cadastro-tab {
            padding: 15px 20px;
            cursor: pointer;
            border-right: 1px solid #ddd;
            background: #eee;
            font-weight: 600;
        }

        .cadastro-tab.active {
            background: #f9f9f9;
            border-bottom: 1px solid #f9f9f9;
            margin-bottom: -1px;
        }

        .cadastro-tab:hover {
            background-color: #e5e5e5;
        }

        .cadastro-content {
            padding: 20px;
            display: none;
        }

        .cadastro-content.active {
            display: block;
        }

        .shortcode-form-group {
            margin-bottom: 15px;
        }

        .shortcode-form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .shortcode-form-group input[type="text"],
        .shortcode-form-group input[type="email"],
        .shortcode-form-group input[type="date"],
        .shortcode-form-group input[type="file"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .shortcode-submit-btn,
        .btn-secondary {
            background-color: #0073aa;
            color: white;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            border-radius: 4px;
            display: inline-block;
            text-decoration: none;
        }

        .shortcode-submit-btn:hover {
            background-color: #005177;
        }

        .btn-secondary {
            background-color: #666;
            margin-right: 10px;
        }

        .btn-secondary:hover {
            background-color: #555;
        }

        .cadastro-alert {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid transparent;
            border-radius: 4px;
        }

        .cadastro-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .cadastro-error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .cadastro-warning {
            color: #856404;
            background-color: #fff3cd;
            border-color: #ffeeba;
        }
    </style>

    <div class="cadastro-container">
        <div class="cadastro-tabs">
            <div class="cadastro-tab active" onclick="openTab(event, 'tab-manual')">Cadastro Manual</div>
            <div class="cadastro-tab" onclick="openTab(event, 'tab-import')">Importar CSV</div>
        </div>

        <!-- ABA MANUAL -->
        <div id="tab-manual" class="cadastro-content active">
            <?php echo $message; ?>
            <form method="post">
                <?php wp_nonce_field('cadastro_usuario_action', 'cadastro_nonce_field'); ?>

                <h3>Dados Pessoais</h3>
                <div class="shortcode-form-group"><label>Nome *</label><input type="text" name="nome" required
                        value="<?php echo isset($_POST['nome']) ? esc_attr($_POST['nome']) : ''; ?>"></div>
                <div class="shortcode-form-group"><label>Sobrenome</label><input type="text" name="sobrenome"
                        value="<?php echo isset($_POST['sobrenome']) ? esc_attr($_POST['sobrenome']) : ''; ?>"></div>
                <div class="shortcode-form-group"><label>CPF</label><input type="text" name="cpf" id="cpf"
                        value="<?php echo isset($_POST['cpf']) ? esc_attr($_POST['cpf']) : ''; ?>"></div>
                <div class="shortcode-form-group"><label>Data de Nascimento</label><input type="date" name="aniversario"
                        value="<?php echo isset($_POST['aniversario']) ? esc_attr($_POST['aniversario']) : ''; ?>"></div>
                <div class="shortcode-form-group"><label>Instagram</label><input type="text" name="instagram"
                        value="<?php echo isset($_POST['instagram']) ? esc_attr($_POST['instagram']) : ''; ?>"></div>
                <div class="shortcode-form-group"><label>Email *</label><input type="email" name="email" required
                        value="<?php echo isset($_POST['email']) ? esc_attr($_POST['email']) : ''; ?>"></div>

                <h3>Endereço Completo</h3>
                <div class="shortcode-form-group"><label>CEP</label><input type="text" name="cep" id="cep"
                        value="<?php echo isset($_POST['cep']) ? esc_attr($_POST['cep']) : ''; ?>"></div>
                <div class="shortcode-form-group"><label>Rua</label><input type="text" name="rua" id="rua"
                        value="<?php echo isset($_POST['rua']) ? esc_attr($_POST['rua']) : ''; ?>"></div>
                <div class="shortcode-form-group"><label>Número</label><input type="text" name="numero"
                        value="<?php echo isset($_POST['numero']) ? esc_attr($_POST['numero']) : ''; ?>"></div>
                <div class="shortcode-form-group"><label>Complemento</label><input type="text" name="complemento"
                        value="<?php echo isset($_POST['complemento']) ? esc_attr($_POST['complemento']) : ''; ?>"></div>
                <div class="shortcode-form-group"><label>Bairro</label><input type="text" name="bairro" id="bairro"
                        value="<?php echo isset($_POST['bairro']) ? esc_attr($_POST['bairro']) : ''; ?>"></div>
                <div class="shortcode-form-group"><label>Cidade</label><input type="text" name="cidade" id="cidade"
                        value="<?php echo isset($_POST['cidade']) ? esc_attr($_POST['cidade']) : ''; ?>"></div>
                <div class="shortcode-form-group"><label>Estado</label><input type="text" name="estado" id="estado"
                        value="<?php echo isset($_POST['estado']) ? esc_attr($_POST['estado']) : ''; ?>"></div>

                <div class="shortcode-form-group">
                    <input type="submit" name="submit_cadastro_usuario" class="shortcode-submit-btn" value="Cadastrar">
                </div>
            </form>
        </div>

        <!-- ABA IMPORTAR -->
        <div id="tab-import" class="cadastro-content">
            <?php echo $import_message; ?>
            <p>Selecione um arquivo CSV para importar usuários em massa.</p>
            <p>
                <a href="?action=download_cadastro_csv_template" class="btn-secondary" target="_blank">Baixar Modelo CSV</a>
            </p>

            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('import_usuario_action', 'import_nonce_field'); ?>

                <div class="shortcode-form-group">
                    <label>Arquivo CSV</label>
                    <input type="file" name="csv_file" accept=".csv" required>
                </div>

                <div class="shortcode-form-group">
                    <input type="submit" name="submit_csv_import" class="shortcode-submit-btn" value="Importar Usuários">
                </div>
            </form>
        </div>
    </div>

    <script>
        function openTab(evt, tabName) {
            var i, content, tablinks;
            content = document.getElementsByClassName("cadastro-content");
            for (i = 0; i < content.length; i++) {
                content[i].style.display = "none";
                content[i].classList.remove("active");
            }
            tablinks = document.getElementsByClassName("cadastro-tab");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }
            document.getElementById(tabName).style.display = "block";
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }

        // Mantém máscaras e auto-preenchimento
        document.addEventListener('DOMContentLoaded', function () {
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
                        fetch('https://viacep.com.br/ws/' + cep + '/json/')
                            .then(r => r.json())
                            .then(data => {
                                if (!data.erro) {
                                    if (document.getElementById('rua')) document.getElementById('rua').value = data.logradouro || '';
                                    if (document.getElementById('bairro')) document.getElementById('bairro').value = data.bairro || '';
                                    if (document.getElementById('cidade')) document.getElementById('cidade').value = data.localidade || '';
                                    if (document.getElementById('estado')) document.getElementById('estado').value = data.uf || '';
                                }
                            })
                            .catch(e => console.error(e));
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
