<?php
// Snippet Name: Shortcode Minha Conta
// Description: Shortcode [minha-conta] para exibir e editar dados do usuário
// Code:

add_shortcode('minha-conta', 'render_minha_conta_shortcode');

function minha_conta_convert_date_to_iso($date)
{
    if (empty($date)) {
        return '';
    }

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

    return $date;
}

function minha_conta_convert_date_to_br($date)
{
    if (empty($date)) {
        return '';
    }

    // Tenta formato ISO (yyyy-mm-dd)
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if ($d && $d->format('Y-m-d') === $date) {
        return $d->format('d/m/Y');
    }

    // Tenta formato BR (dd/mm/yyyy) para validar se já está ok
    $d = DateTime::createFromFormat('d/m/Y', $date);
    if ($d && $d->format('d/m/Y') === $date) {
        return $date;
    }

    return $date;
}


function render_minha_conta_shortcode()
{
    // 1. Verificar Login
    if (!is_user_logged_in()) {
        return '<p style="color: #fff; text-align: center;">Você precisa estar logado para ver esta página.</p>';
    }

    $user_id = get_current_user_id();
    $message = '';

    // 2. Processar Salvamento (Formulário enviado)
    if (isset($_POST['mc_submit']) && wp_verify_nonce($_POST['mc_nonce'], 'save_minha_conta')) {

        // Dados Pessoais
        if (isset($_POST['first_name']))
            update_user_meta($user_id, 'first_name', sanitize_text_field($_POST['first_name']));
        if (isset($_POST['last_name']))
            update_user_meta($user_id, 'last_name', sanitize_text_field($_POST['last_name']));
        if (isset($_POST['cpf']))
            update_user_meta($user_id, 'cpf', sanitize_text_field($_POST['cpf']));
        if (isset($_POST['aniversario'])) {
            $data_iso = minha_conta_convert_date_to_iso(sanitize_text_field($_POST['aniversario']));
            update_user_meta($user_id, 'aniversario', $data_iso);
        }

        if (isset($_POST['instagram']))
            update_user_meta($user_id, 'instagram', sanitize_text_field($_POST['instagram']));

        // Endereço
        if (isset($_POST['cep']))
            update_user_meta($user_id, 'cep', sanitize_text_field($_POST['cep']));
        if (isset($_POST['rua']))
            update_user_meta($user_id, 'rua', sanitize_text_field($_POST['rua']));
        if (isset($_POST['numero']))
            update_user_meta($user_id, 'numero', sanitize_text_field($_POST['numero']));
        if (isset($_POST['complemento']))
            update_user_meta($user_id, 'complemento', sanitize_text_field($_POST['complemento']));
        if (isset($_POST['bairro']))
            update_user_meta($user_id, 'bairro', sanitize_text_field($_POST['bairro']));
        if (isset($_POST['cidade']))
            update_user_meta($user_id, 'cidade', sanitize_text_field($_POST['cidade']));
        if (isset($_POST['estado']))
            update_user_meta($user_id, 'estado', sanitize_text_field($_POST['estado']));

        $message = '<div class="mc-alert mc-success">Dados atualizados com sucesso!</div>';
    }

    // 3. Recuperar Dados (Recarrega após salvar para mostrar atualizado)
    $user_data = get_userdata($user_id);

    $first_name = get_user_meta($user_id, 'first_name', true);
    $last_name = get_user_meta($user_id, 'last_name', true);
    $nickname = $user_data->user_email;
    $cpf = get_user_meta($user_id, 'cpf', true);
    $aniversario = get_user_meta($user_id, 'aniversario', true);
    $aniversario = minha_conta_convert_date_to_br($aniversario);

    $instagram = get_user_meta($user_id, 'instagram', true);

    $cep = get_user_meta($user_id, 'cep', true);
    $rua = get_user_meta($user_id, 'rua', true);
    $numero = get_user_meta($user_id, 'numero', true);
    $complemento = get_user_meta($user_id, 'complemento', true);
    $bairro = get_user_meta($user_id, 'bairro', true);
    $cidade = get_user_meta($user_id, 'cidade', true);
    $estado = get_user_meta($user_id, 'estado', true);

    // Foto de Perfil
    $avatar_id = get_user_meta($user_id, 'local_user_avatar_attachment_id', true);
    $avatar_url = '';
    if ($avatar_id) {
        $avatar_url = wp_get_attachment_image_url($avatar_id, 'medium');
    }
    if (empty($avatar_url)) {
        $avatar_url = get_avatar_url($user_id);
    }

    ob_start();
    ?>
    <style>
        /* Base Container */
        .mc-container,
        .mc-container * {
            box-sizing: border-box;
        }

        .mc-container {
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            max-width: 800px;
            margin: 0px auto;
            background-color: #121212;
            color: #e0e0e0;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            border: 1px solid #2a2a2a;
        }

        /* Header */
        .mc-header {
            background: linear-gradient(180deg, #1f1f1f 0%, #161616 100%);
            padding: 40px 20px;
            text-align: center;
            border-bottom: 1px solid #2a2a2a;
        }

        .mc-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #FDC110;
            /* Distinctive purple/blue */
            box-shadow: 0 0 20px rgba(108, 92, 231, 0.2);
            margin-bottom: 15px;
        }

        .mc-name {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
            color: #fff;
        }

        .mc-email {
            font-size: 0.95rem;
            color: #888;
            margin-top: 5px;
        }

        /* Body & Grid */
        .mc-body {
            padding: 40px;
        }

        .mc-section-title {
            font-size: 1.1rem;
            color: #FDC110;
            border-bottom: 2px solid #2a2a2a;
            padding-bottom: 10px;
            margin-bottom: 25px;
            margin-top: 0;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 600;
        }

        .mc-section-title:not(:first-child) {
            margin-top: 40px;
        }

        .mc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
        }

        /* Input Fields styling */
        .mc-field-group {
            display: flex;
            flex-direction: column;
        }

        .mc-field-label {
            font-size: 0.75rem;
            color: #666;
            margin-bottom: 6px;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .mc-input {
            background-color: transparent;
            border: 1px solid transparent;
            /* Invisible border by default */
            border-bottom: 1px solid #333;
            /* Subtle line */
            color: #fff;
            padding: 10px 0;
            font-size: 1.1rem;
            width: 100%;
            font-family: inherit;
            transition: all 0.3s ease;
            outline: none;
            border-radius: 4px;
        }

        /* The "Interactive" feel */
        .mc-input:hover {
            background-color: #1a1a1a;
            border-color: #444;
            padding-left: 10px;
            /* Slight shift to indicate interactivity */
        }

        .mc-input:focus {
            background-color: #222;
            border-color: #FDC110;
            padding-left: 10px;
            box-shadow: 0 0 0 3px rgba(108, 92, 231, 0.1);
        }

        .mc-input::placeholder {
            color: #444;
        }

        /* Footer & Button */
        .mc-footer {
            background-color: #161616;
            padding: 20px 40px;
            text-align: right;
            /* Button on the right */
            border-top: 1px solid #2a2a2a;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }

        .mc-btn-save {
            background-color: #FDC110;
            color: #fff;
            border: none;
            padding: 12px 30px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.1s;
            box-shadow: 0 4px 10px rgba(108, 92, 231, 0.3);
        }

        .mc-btn-save:hover {
            background-color: #FDC110;
            transform: translateY(-1px);
        }

        .mc-btn-save:active {
            transform: translateY(1px);
        }

        /* Alerts */
        .mc-alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            text-align: center;
            font-weight: 500;
        }

        .mc-success {
            background-color: rgba(46, 213, 115, 0.15);
            color: #2ed573;
            border: 1px solid rgba(46, 213, 115, 0.2);
        }

        /* Responsive */
        @media (max-width: 600px) {
            .mc-container {
                margin: 15px;
            }

            .mc-body {
                padding: 25px;
            }

            .mc-grid {
                grid-template-columns: 1fr;
            }

            .mc-footer {
                justify-content: center;
            }

            .mc-btn-save {
                width: 100%;
            }
        }
    </style>

    <div class="mc-container">

        <!-- Header Statico -->
        <div class="mc-header">
            <img src="<?php echo esc_url($avatar_url); ?>" alt="Avatar" class="mc-avatar">
            <h2 class="mc-name"><?php echo esc_html($first_name . ' ' . $last_name); ?></h2>
            <p class="mc-email"><?php echo esc_html($nickname); ?></p>
        </div>

        <!-- Form de Edição -->
        <form method="post" action="">
            <?php wp_nonce_field('save_minha_conta', 'mc_nonce'); ?>

            <div class="mc-body">

                <?php echo $message; ?>

                <!-- Dados Pessoais -->
                <h3 class="mc-section-title">Dados Pessoais</h3>
                <div class="mc-grid">
                    <div class="mc-field-group">
                        <label class="mc-field-label">Nome</label>
                        <input type="text" name="first_name" class="mc-input" value="<?php echo esc_attr($first_name); ?>"
                            placeholder="Seu nome">
                    </div>
                    <div class="mc-field-group">
                        <label class="mc-field-label">Sobrenome</label>
                        <input type="text" name="last_name" class="mc-input" value="<?php echo esc_attr($last_name); ?>"
                            placeholder="Seu sobrenome">
                    </div>
                    <div class="mc-field-group">
                        <label class="mc-field-label">CPF</label>
                        <input type="text" name="cpf" id="mc_cpf" class="mc-input" value="<?php echo esc_attr($cpf); ?>"
                            placeholder="000.000.000-00">
                    </div>
                    <div class="mc-field-group">
                        <label class="mc-field-label">Data de Nascimento</label>
                        <input type="text" name="aniversario" id="mc_aniversario" class="mc-input"
                            value="<?php echo esc_attr($aniversario); ?>" placeholder="DD/MM/AAAA">
                    </div>
                    <div class="mc-field-group">
                        <label class="mc-field-label">Instagram</label>
                        <input type="text" name="instagram" class="mc-input" value="<?php echo esc_attr($instagram); ?>"
                            placeholder="@seu.insta">
                    </div>
                </div>

                <!-- Endereço -->
                <h3 class="mc-section-title">Endereço</h3>
                <div class="mc-grid">
                    <div class="mc-field-group">
                        <label class="mc-field-label">CEP</label>
                        <input type="text" name="cep" id="mc_cep" class="mc-input" value="<?php echo esc_attr($cep); ?>"
                            placeholder="00000-000">
                    </div>
                    <div class="mc-field-group">
                        <label class="mc-field-label">Rua / Logradouro</label>
                        <input type="text" name="rua" class="mc-input" value="<?php echo esc_attr($rua); ?>"
                            placeholder="Av. Principal">
                    </div>
                    <div class="mc-field-group">
                        <label class="mc-field-label">Número</label>
                        <input type="text" name="numero" class="mc-input" value="<?php echo esc_attr($numero); ?>"
                            placeholder="123">
                    </div>
                    <div class="mc-field-group">
                        <label class="mc-field-label">Complemento</label>
                        <input type="text" name="complemento" class="mc-input" value="<?php echo esc_attr($complemento); ?>"
                            placeholder="Apto 101">
                    </div>
                    <div class="mc-field-group">
                        <label class="mc-field-label">Bairro</label>
                        <input type="text" name="bairro" class="mc-input" value="<?php echo esc_attr($bairro); ?>"
                            placeholder="Centro">
                    </div>
                    <div class="mc-field-group">
                        <label class="mc-field-label">Cidade</label>
                        <input type="text" name="cidade" class="mc-input" value="<?php echo esc_attr($cidade); ?>"
                            placeholder="São Paulo">
                    </div>
                    <div class="mc-field-group">
                        <label class="mc-field-label">Estado (UF)</label>
                        <input type="text" name="estado" class="mc-input" value="<?php echo esc_attr($estado); ?>"
                            placeholder="SP" maxlength="2">
                    </div>
                </div>
            </div>

            <div class="mc-footer">
                <button type="submit" name="mc_submit" class="mc-btn-save">Salvar Alterações</button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const cpfInput = document.getElementById('mc_cpf');
            const dataInput = document.getElementById('mc_aniversario');
            const cepInput = document.getElementById('mc_cep');

            const applyMask = (input, maskFunc) => {
                input.addEventListener('input', (e) => {
                    let value = e.target.value;
                    e.target.value = maskFunc(value);
                });
            };

            // Máscara CPF: 000.000.000-00
            const maskCPF = (v) => {
                v = v.replace(/\D/g, "");
                if (v.length > 11) v = v.substring(0, 11);
                v = v.replace(/(\d{3})(\d)/, "$1.$2");
                v = v.replace(/(\d{3})(\d)/, "$1.$2");
                v = v.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
                return v;
            };

            // Máscara Data: 00/00/0000
            const maskDate = (v) => {
                v = v.replace(/\D/g, "");
                if (v.length > 8) v = v.substring(0, 8);
                v = v.replace(/(\d{2})(\d)/, "$1/$2");
                v = v.replace(/(\d{2})(\d)/, "$1/$2");
                return v;
            };

            // Máscara CEP: 00000-000
            const maskCEP = (v) => {
                v = v.replace(/\D/g, "");
                if (v.length > 8) v = v.substring(0, 8);
                v = v.replace(/(\d{5})(\d)/, "$1-$2");
                return v;
            };

            if (cpfInput) applyMask(cpfInput, maskCPF);
            if (dataInput) applyMask(dataInput, maskDate);
            if (cepInput) applyMask(cepInput, maskCEP);
        });
    </script>

    <?php
    return ob_get_clean();
}
