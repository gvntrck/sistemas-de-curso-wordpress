<?php
if (!defined('ABSPATH')) {
    exit;
}

class System_Cursos_Shortcode_Minha_Conta
{
    /**
     * class-shortcode-minha-conta.php
     *
     * Shortcode [minha-conta]
     * Renderiza o painel de perfil do usuário, permitindo a visualização e atualização de dados cadastrais
     * (como nome, CPF e endereço). Também exibe a foto de perfil (avatar).
     *
     * @package SistemaCursos
     * @version 1.0.8
     */
    public function __construct()
    {
        add_shortcode('minha-conta', [$this, 'render_shortcode']);
    }

    public function convert_date_to_iso($date)
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

    public function convert_date_to_br($date)
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

    public function render_shortcode()
    {
        // 1. Verificar Login
        if (!is_user_logged_in()) {
            return System_Cursos_Config::get_message('access_denied');
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
                $data_iso = $this->convert_date_to_iso(sanitize_text_field($_POST['aniversario']));
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

            $message = System_Cursos_Config::get_message('save_success');
        }

        // 3. Recuperar Dados (Recarrega após salvar para mostrar atualizado)
        $user_data = get_userdata($user_id);

        $first_name = get_user_meta($user_id, 'first_name', true);
        $last_name = get_user_meta($user_id, 'last_name', true);
        $nickname = $user_data->user_email;
        $cpf = get_user_meta($user_id, 'cpf', true);
        $aniversario = get_user_meta($user_id, 'aniversario', true);
        $aniversario = $this->convert_date_to_br($aniversario);

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

        <!-- CSS já carregado via assets/css/style.css -->

        <div class="mc-container">

            <!-- Header Statico -->
            <div class="mc-header">
                <img src="<?php echo esc_url($avatar_url); ?>" alt="Avatar" class="mc-avatar">
                <h2 class="mc-name">
                    <?php echo esc_html($first_name . ' ' . $last_name); ?>
                </h2>
                <p class="mc-email">
                    <?php echo esc_html($nickname); ?>
                </p>
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

        <!-- Script mascaras carregado via assets/js/script.js -->

        <?php
        return ob_get_clean();
    }
}
