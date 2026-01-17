<?php
if (!defined('ABSPATH')) {
    exit;
}

class System_Cursos_User_Fields
{
    /**
     * class-user-fields.php
     *
     * Adiciona e gerencia campos personalizados para os usuários (CPF, endereço, redes sociais, etc).
     * Responsável por renderizar e salvar esses campos formulários de registro e edição de perfil, além de exibir na listagem de usuários.
     *
     * @package SistemaCursos
     * @version 1.0.8
     */
    public function __construct()
    {
        // Registro (Frontend)
        add_action('register_form', [$this, 'add_registration_fields']);
        add_filter('registration_errors', [$this, 'validate_registration_fields'], 10, 3);
        add_action('user_register', [$this, 'save_registration_fields']);

        // Edição de Perfil (Admin e Frontend)
        add_action('show_user_profile', [$this, 'add_profile_fields']);
        add_action('edit_user_profile', [$this, 'add_profile_fields']);
        add_action('personal_options_update', [$this, 'save_profile_fields']);
        add_action('edit_user_profile_update', [$this, 'save_profile_fields']);

        // Colunas na Listagem de Usuários
        add_filter('manage_users_columns', [$this, 'add_user_columns']);
        add_filter('manage_users_custom_column', [$this, 'render_user_columns'], 10, 3);
    }

    // =====================================================
    // HELPERS
    // =====================================================

    private function convert_date_to_iso($date)
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

    // =====================================================
    // REGISTRO
    // =====================================================

    public function add_registration_fields()
    {
        $fields = ['cpf', 'aniversario', 'instagram', 'cep', 'rua', 'numero', 'complemento', 'bairro', 'cidade', 'estado'];
        $values = [];
        foreach ($fields as $field) {
            $values[$field] = isset($_POST[$field]) ? sanitize_text_field($_POST[$field]) : '';
        }
        ?>
        <p>
            <label for="cpf">CPF<br />
                <input type="text" name="cpf" id="cpf" class="input mask-cpf" value="<?php echo esc_attr($values['cpf']); ?>"
                    size="25" placeholder="000.000.000-00" />
            </label>
        </p>

        <p>
            <label for="aniversario">Data de Aniversário<br />
                <input type="date" name="aniversario" id="aniversario" class="input"
                    value="<?php echo esc_attr($values['aniversario']); ?>" size="25" />
            </label>
        </p>

        <p>
            <label for="instagram">Instagram<br />
                <input type="text" name="instagram" id="instagram" class="input"
                    value="<?php echo esc_attr($values['instagram']); ?>" size="25" placeholder="@seuusuario" />
            </label>
        </p>

        <p>
            <label for="cep">CEP<br />
                <input type="text" name="cep" id="cep" class="input mask-cep" value="<?php echo esc_attr($values['cep']); ?>"
                    size="25" placeholder="00000-000" />
            </label>
        </p>

        <p>
            <label for="rua">Rua/Logradouro<br />
                <input type="text" name="rua" id="rua" class="input" value="<?php echo esc_attr($values['rua']); ?>"
                    size="25" />
            </label>
        </p>

        <p>
            <label for="numero">Número<br />
                <input type="text" name="numero" id="numero" class="input" value="<?php echo esc_attr($values['numero']); ?>"
                    size="25" />
            </label>
        </p>

        <p>
            <label for="complemento">Complemento<br />
                <input type="text" name="complemento" id="complemento" class="input"
                    value="<?php echo esc_attr($values['complemento']); ?>" size="25" />
            </label>
        </p>

        <p>
            <label for="bairro">Bairro<br />
                <input type="text" name="bairro" id="bairro" class="input" value="<?php echo esc_attr($values['bairro']); ?>"
                    size="25" />
            </label>
        </p>

        <p>
            <label for="cidade">Cidade<br />
                <input type="text" name="cidade" id="cidade" class="input" value="<?php echo esc_attr($values['cidade']); ?>"
                    size="25" />
            </label>
        </p>

        <p>
            <label for="estado">Estado<br />
                <select name="estado" id="estado" class="input">
                    <option value="">Selecione...</option>
                    <?php
                    $estados = $this->get_estados();
                    foreach ($estados as $sigla => $nome) {
                        $selected = ($values['estado'] === $sigla) ? 'selected' : '';
                        echo '<option value="' . esc_attr($sigla) . '" ' . $selected . '>' . esc_html($nome) . '</option>';
                    }
                    ?>
                </select>
            </label>
        </p>
        <?php
    }

    public function validate_registration_fields($errors, $sanitized_user_login, $user_email)
    {
        if (isset($_POST['cpf']) && !empty($_POST['cpf'])) {
            $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
            if (strlen($cpf) !== 11) {
                $errors->add('cpf_error', __('<strong>ERRO</strong>: CPF inválido.', 'textdomain'));
            }
        }
        return $errors;
    }

    public function save_registration_fields($user_id)
    {
        $campos = ['cpf', 'aniversario', 'instagram', 'cep', 'rua', 'numero', 'complemento', 'bairro', 'cidade', 'estado'];

        foreach ($campos as $campo) {
            if (isset($_POST[$campo])) {
                $value = sanitize_text_field($_POST[$campo]);
                if ($campo === 'aniversario') {
                    $value = $this->convert_date_to_iso($value);
                }
                update_user_meta($user_id, $campo, $value);
            }
        }
    }

    // =====================================================
    // EDIÇÃO DE PERFIL
    // =====================================================

    public function add_profile_fields($user)
    {
        $cpf = get_user_meta($user->ID, 'cpf', true);
        $aniversario = get_user_meta($user->ID, 'aniversario', true);

        // Ensure ISO format for input[type="date"]
        if ($aniversario) {
            $aniversario = $this->convert_date_to_iso($aniversario);
        }

        $instagram = get_user_meta($user->ID, 'instagram', true);
        $cep = get_user_meta($user->ID, 'cep', true);
        $rua = get_user_meta($user->ID, 'rua', true);
        $numero = get_user_meta($user->ID, 'numero', true);
        $complemento = get_user_meta($user->ID, 'complemento', true);
        $bairro = get_user_meta($user->ID, 'bairro', true);
        $cidade = get_user_meta($user->ID, 'cidade', true);
        $estado = get_user_meta($user->ID, 'estado', true);
        ?>
        <h3>Informações Pessoais</h3>
        <table class="form-table">
            <tr>
                <th><label for="cpf">CPF</label></th>
                <td><input type="text" name="cpf" id="cpf" value="<?php echo esc_attr($cpf); ?>" class="regular-text mask-cpf"
                        placeholder="000.000.000-00" /></td>
            </tr>
            <tr>
                <th><label for="aniversario">Data de Aniversário</label></th>
                <td><input type="date" name="aniversario" id="aniversario" value="<?php echo esc_attr($aniversario); ?>"
                        class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="instagram">Instagram</label></th>
                <td><input type="text" name="instagram" id="instagram" value="<?php echo esc_attr($instagram); ?>"
                        class="regular-text" placeholder="@seuusuario" /></td>
            </tr>
            <!-- Grupos de Alunos -->
            <?php if (current_user_can('manage_options')): ?>
                <tr>
                    <th><label>Grupos de Acesso</label></th>
                    <td>
                        <?php
                        $user_grupos = get_user_meta($user->ID, '_aluno_grupos', true);
                        if (!is_array($user_grupos)) {
                            $user_grupos = [];
                        }

                        $grupos = get_posts([
                            'post_type' => 'grupo',
                            'posts_per_page' => -1,
                            'orderby' => 'title',
                            'order' => 'ASC'
                        ]);

                        if (!empty($grupos)):
                            ?>
                            <div
                                style="max-height: 150px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff; display: inline-block; min-width: 300px;">
                                <?php foreach ($grupos as $grupo):
                                    $checked = in_array($grupo->ID, $user_grupos) ? 'checked' : '';
                                    ?>
                                    <label style="display:block; margin-bottom: 5px;">
                                        <input type="checkbox" name="aluno_grupos[]" value="<?php echo esc_attr($grupo->ID); ?>" <?php echo $checked; ?>>
                                        <?php echo esc_html($grupo->post_title); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <p class="description">Selecione os grupos aos quais este aluno pertence.</p>
                        <?php else: ?>
                            <p class="description">Nenhum grupo cadastrado.</p>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endif; ?>
        </table>

        <h3>Endereço</h3>
        <table class="form-table">
            <tr>
                <th><label for="cep">CEP</label></th>
                <td><input type="text" name="cep" id="cep" value="<?php echo esc_attr($cep); ?>" class="regular-text mask-cep"
                        placeholder="00000-000" /></td>
            </tr>
            <tr>
                <th><label for="rua">Rua/Logradouro</label></th>
                <td><input type="text" name="rua" id="rua" value="<?php echo esc_attr($rua); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="numero">Número</label></th>
                <td><input type="text" name="numero" id="numero" value="<?php echo esc_attr($numero); ?>" class="small-text" />
                </td>
            </tr>
            <tr>
                <th><label for="complemento">Complemento</label></th>
                <td><input type="text" name="complemento" id="complemento" value="<?php echo esc_attr($complemento); ?>"
                        class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="bairro">Bairro</label></th>
                <td><input type="text" name="bairro" id="bairro" value="<?php echo esc_attr($bairro); ?>"
                        class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="cidade">Cidade</label></th>
                <td><input type="text" name="cidade" id="cidade" value="<?php echo esc_attr($cidade); ?>"
                        class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="estado">Estado</label></th>
                <td>
                    <select name="estado" id="estado" class="regular-text">
                        <option value="">Selecione...</option>
                        <?php
                        $estados = $this->get_estados();
                        foreach ($estados as $sigla => $nome) {
                            $selected = ($estado === $sigla) ? 'selected' : '';
                            echo '<option value="' . esc_attr($sigla) . '" ' . $selected . '>' . esc_html($nome) . '</option>';
                        }
                        ?>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_profile_fields($user_id)
    {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }

        $campos = ['cpf', 'aniversario', 'instagram', 'cep', 'rua', 'numero', 'complemento', 'bairro', 'cidade', 'estado'];

        foreach ($campos as $campo) {
            if (isset($_POST[$campo])) {
                $value = sanitize_text_field($_POST[$campo]);
                if ($campo === 'aniversario') {
                    $value = $this->convert_date_to_iso($value);
                }
                update_user_meta($user_id, $campo, $value);
            }
        }
    }

    // =====================================================
    // LISTAGEM DE USUÁRIOS
    // =====================================================

    public function add_user_columns($columns)
    {
        $columns['cpf'] = 'CPF';
        $columns['cidade_estado'] = 'Cidade/Estado';
        return $columns;
    }

    public function render_user_columns($value, $column_name, $user_id)
    {
        switch ($column_name) {
            case 'cpf':
                return get_user_meta($user_id, 'cpf', true);
            case 'cidade_estado':
                $cidade = get_user_meta($user_id, 'cidade', true);
                $estado = get_user_meta($user_id, 'estado', true);
                if ($cidade && $estado) {
                    return $cidade . ' - ' . $estado;
                }
                return '-';
        }
        return $value;
    }

    private function get_estados()
    {
        return [
            'AC' => 'Acre',
            'AL' => 'Alagoas',
            'AP' => 'Amapá',
            'AM' => 'Amazonas',
            'BA' => 'Bahia',
            'CE' => 'Ceará',
            'DF' => 'Distrito Federal',
            'ES' => 'Espírito Santo',
            'GO' => 'Goiás',
            'MA' => 'Maranhão',
            'MT' => 'Mato Grosso',
            'MS' => 'Mato Grosso do Sul',
            'MG' => 'Minas Gerais',
            'PA' => 'Pará',
            'PB' => 'Paraíba',
            'PR' => 'Paraná',
            'PE' => 'Pernambuco',
            'PI' => 'Piauí',
            'RJ' => 'Rio de Janeiro',
            'RN' => 'Rio Grande do Norte',
            'RS' => 'Rio Grande do Sul',
            'RO' => 'Rondônia',
            'RR' => 'Roraima',
            'SC' => 'Santa Catarina',
            'SP' => 'São Paulo',
            'SE' => 'Sergipe',
            'TO' => 'Tocantins'
        ];
    }
}
