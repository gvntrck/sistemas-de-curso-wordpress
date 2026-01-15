<?php
/**
 * Snippet: Campos Personalizados de Usuário
 * Descrição: Adiciona campos personalizados no cadastro e edição de usuários do WordPress
 * Campos: CPF, Aniversário, Instagram, Endereço (CEP, Rua, Número, Complemento, Bairro, Cidade, Estado)
 * Meta keys: cpf, aniversario, instagram, cep, rua, numero, complemento, bairro, cidade, estado
 * Versão: 1.0.0
 * 
 * Para usar no WPCode: Copie todo o conteúdo e cole como snippet PHP
 */

// =====================================================
// ADICIONA CAMPOS NO FORMULÁRIO DE REGISTRO
// =====================================================

add_action('register_form', 'sdc_campos_registro_usuario');
function sdc_campos_registro_usuario()
{
    $cpf = isset($_POST['cpf']) ? sanitize_text_field($_POST['cpf']) : '';
    $aniversario = isset($_POST['aniversario']) ? sanitize_text_field($_POST['aniversario']) : '';
    $instagram = isset($_POST['instagram']) ? sanitize_text_field($_POST['instagram']) : '';
    $cep = isset($_POST['cep']) ? sanitize_text_field($_POST['cep']) : '';
    $rua = isset($_POST['rua']) ? sanitize_text_field($_POST['rua']) : '';
    $numero = isset($_POST['numero']) ? sanitize_text_field($_POST['numero']) : '';
    $complemento = isset($_POST['complemento']) ? sanitize_text_field($_POST['complemento']) : '';
    $bairro = isset($_POST['bairro']) ? sanitize_text_field($_POST['bairro']) : '';
    $cidade = isset($_POST['cidade']) ? sanitize_text_field($_POST['cidade']) : '';
    $estado = isset($_POST['estado']) ? sanitize_text_field($_POST['estado']) : '';
    ?>

    <p>
        <label for="cpf"><?php _e('CPF', 'textdomain'); ?><br />
            <input type="text" name="cpf" id="cpf" class="input" value="<?php echo esc_attr($cpf); ?>" size="25"
                placeholder="000.000.000-00" />
        </label>
    </p>

    <p>
        <label for="aniversario"><?php _e('Data de Aniversário', 'textdomain'); ?><br />
            <input type="date" name="aniversario" id="aniversario" class="input"
                value="<?php echo esc_attr($aniversario); ?>" size="25" />
        </label>
    </p>

    <p>
        <label for="instagram"><?php _e('Instagram', 'textdomain'); ?><br />
            <input type="text" name="instagram" id="instagram" class="input" value="<?php echo esc_attr($instagram); ?>"
                size="25" placeholder="@seuusuario" />
        </label>
    </p>

    <p>
        <label for="cep"><?php _e('CEP', 'textdomain'); ?><br />
            <input type="text" name="cep" id="cep" class="input" value="<?php echo esc_attr($cep); ?>" size="25"
                placeholder="00000-000" />
        </label>
    </p>

    <p>
        <label for="rua"><?php _e('Rua/Logradouro', 'textdomain'); ?><br />
            <input type="text" name="rua" id="rua" class="input" value="<?php echo esc_attr($rua); ?>" size="25" />
        </label>
    </p>

    <p>
        <label for="numero"><?php _e('Número', 'textdomain'); ?><br />
            <input type="text" name="numero" id="numero" class="input" value="<?php echo esc_attr($numero); ?>" size="25" />
        </label>
    </p>

    <p>
        <label for="complemento"><?php _e('Complemento', 'textdomain'); ?><br />
            <input type="text" name="complemento" id="complemento" class="input"
                value="<?php echo esc_attr($complemento); ?>" size="25" />
        </label>
    </p>

    <p>
        <label for="bairro"><?php _e('Bairro', 'textdomain'); ?><br />
            <input type="text" name="bairro" id="bairro" class="input" value="<?php echo esc_attr($bairro); ?>" size="25" />
        </label>
    </p>

    <p>
        <label for="cidade"><?php _e('Cidade', 'textdomain'); ?><br />
            <input type="text" name="cidade" id="cidade" class="input" value="<?php echo esc_attr($cidade); ?>" size="25" />
        </label>
    </p>

    <p>
        <label for="estado"><?php _e('Estado', 'textdomain'); ?><br />
            <select name="estado" id="estado" class="input">
                <option value=""><?php _e('Selecione...', 'textdomain'); ?></option>
                <?php
                $estados = array(
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
                );
                foreach ($estados as $sigla => $nome) {
                    $selected = ($estado === $sigla) ? 'selected' : '';
                    echo '<option value="' . esc_attr($sigla) . '" ' . $selected . '>' . esc_html($nome) . '</option>';
                }
                ?>
            </select>
        </label>
    </p>

    <?php
}

// =====================================================
// VALIDA CAMPOS NO REGISTRO
// =====================================================

add_filter('registration_errors', 'sdc_valida_campos_registro', 10, 3);
function sdc_valida_campos_registro($errors, $sanitized_user_login, $user_email)
{
    if (isset($_POST['cpf']) && !empty($_POST['cpf'])) {
        $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
        if (strlen($cpf) !== 11) {
            $errors->add('cpf_error', __('<strong>ERRO</strong>: CPF inválido.', 'textdomain'));
        }
    }

    return $errors;
}

// =====================================================
// SALVA CAMPOS NO REGISTRO
// =====================================================

add_action('user_register', 'sdc_salva_campos_registro');
function sdc_salva_campos_registro($user_id)
{
    $campos = array('cpf', 'aniversario', 'instagram', 'cep', 'rua', 'numero', 'complemento', 'bairro', 'cidade', 'estado');

    foreach ($campos as $campo) {
        if (isset($_POST[$campo])) {
            update_user_meta($user_id, $campo, sanitize_text_field($_POST[$campo]));
        }
    }
}

// =====================================================
// ADICIONA CAMPOS NA EDIÇÃO DE PERFIL (ADMIN E FRONT)
// =====================================================

add_action('show_user_profile', 'sdc_campos_edicao_perfil');
add_action('edit_user_profile', 'sdc_campos_edicao_perfil');
function sdc_campos_edicao_perfil($user)
{
    $cpf = get_user_meta($user->ID, 'cpf', true);
    $aniversario = get_user_meta($user->ID, 'aniversario', true);

    // Converte formato d/m/Y para Y-m-d para o input html5
    if ($aniversario && strpos($aniversario, '/') !== false) {
        $date = DateTime::createFromFormat('d/m/Y', $aniversario);
        if ($date) {
            $aniversario = $date->format('Y-m-d');
        }
    }
    $instagram = get_user_meta($user->ID, 'instagram', true);
    $cep = get_user_meta($user->ID, 'cep', true);
    $rua = get_user_meta($user->ID, 'rua', true);
    $numero = get_user_meta($user->ID, 'numero', true);
    $complemento = get_user_meta($user->ID, 'complemento', true);
    $bairro = get_user_meta($user->ID, 'bairro', true);
    $cidade = get_user_meta($user->ID, 'cidade', true);
    $estado = get_user_meta($user->ID, 'estado', true);

    $estados = array(
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
    );
    ?>

    <h3><?php _e('Informações Pessoais', 'textdomain'); ?></h3>

    <table class="form-table">
        <tr>
            <th><label for="cpf"><?php _e('CPF', 'textdomain'); ?></label></th>
            <td>
                <input type="text" name="cpf" id="cpf" value="<?php echo esc_attr($cpf); ?>" class="regular-text"
                    placeholder="000.000.000-00" />
            </td>
        </tr>

        <tr>
            <th><label for="aniversario"><?php _e('Data de Aniversário', 'textdomain'); ?></label></th>
            <td>
                <input type="date" name="aniversario" id="aniversario" value="<?php echo esc_attr($aniversario); ?>"
                    class="regular-text" />
            </td>
        </tr>

        <tr>
            <th><label for="instagram"><?php _e('Instagram', 'textdomain'); ?></label></th>
            <td>
                <input type="text" name="instagram" id="instagram" value="<?php echo esc_attr($instagram); ?>"
                    class="regular-text" placeholder="@seuusuario" />
            </td>
        </tr>
    </table>

    <h3><?php _e('Endereço', 'textdomain'); ?></h3>

    <table class="form-table">
        <tr>
            <th><label for="cep"><?php _e('CEP', 'textdomain'); ?></label></th>
            <td>
                <input type="text" name="cep" id="cep" value="<?php echo esc_attr($cep); ?>" class="regular-text"
                    placeholder="00000-000" />
            </td>
        </tr>

        <tr>
            <th><label for="rua"><?php _e('Rua/Logradouro', 'textdomain'); ?></label></th>
            <td>
                <input type="text" name="rua" id="rua" value="<?php echo esc_attr($rua); ?>" class="regular-text" />
            </td>
        </tr>

        <tr>
            <th><label for="numero"><?php _e('Número', 'textdomain'); ?></label></th>
            <td>
                <input type="text" name="numero" id="numero" value="<?php echo esc_attr($numero); ?>" class="small-text" />
            </td>
        </tr>

        <tr>
            <th><label for="complemento"><?php _e('Complemento', 'textdomain'); ?></label></th>
            <td>
                <input type="text" name="complemento" id="complemento" value="<?php echo esc_attr($complemento); ?>"
                    class="regular-text" />
            </td>
        </tr>

        <tr>
            <th><label for="bairro"><?php _e('Bairro', 'textdomain'); ?></label></th>
            <td>
                <input type="text" name="bairro" id="bairro" value="<?php echo esc_attr($bairro); ?>"
                    class="regular-text" />
            </td>
        </tr>

        <tr>
            <th><label for="cidade"><?php _e('Cidade', 'textdomain'); ?></label></th>
            <td>
                <input type="text" name="cidade" id="cidade" value="<?php echo esc_attr($cidade); ?>"
                    class="regular-text" />
            </td>
        </tr>

        <tr>
            <th><label for="estado"><?php _e('Estado', 'textdomain'); ?></label></th>
            <td>
                <select name="estado" id="estado" class="regular-text">
                    <option value=""><?php _e('Selecione...', 'textdomain'); ?></option>
                    <?php
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

// =====================================================
// SALVA CAMPOS NA EDIÇÃO DE PERFIL
// =====================================================

add_action('personal_options_update', 'sdc_salva_campos_edicao_perfil');
add_action('edit_user_profile_update', 'sdc_salva_campos_edicao_perfil');
function sdc_salva_campos_edicao_perfil($user_id)
{
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    $campos = array('cpf', 'aniversario', 'instagram', 'cep', 'rua', 'numero', 'complemento', 'bairro', 'cidade', 'estado');

    foreach ($campos as $campo) {
        if (isset($_POST[$campo])) {
            update_user_meta($user_id, $campo, sanitize_text_field($_POST[$campo]));
        }
    }
}

// =====================================================
// ADICIONA COLUNAS NA LISTAGEM DE USUÁRIOS (ADMIN)
// =====================================================

add_filter('manage_users_columns', 'sdc_colunas_usuarios');
function sdc_colunas_usuarios($columns)
{
    $columns['cpf'] = __('CPF', 'textdomain');
    $columns['cidade_estado'] = __('Cidade/Estado', 'textdomain');
    return $columns;
}

add_filter('manage_users_custom_column', 'sdc_conteudo_colunas_usuarios', 10, 3);
function sdc_conteudo_colunas_usuarios($value, $column_name, $user_id)
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

// =====================================================
// SCRIPT PARA BUSCA AUTOMÁTICA DE CEP (VIA CEP)
// =====================================================

add_action('admin_footer', 'sdc_script_busca_cep');
add_action('wp_footer', 'sdc_script_busca_cep');
function sdc_script_busca_cep()
{
    if (!is_admin() && !is_page('minha-conta')) {
        return;
    }
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var cepInput = document.getElementById('cep');
            if (cepInput) {
                cepInput.addEventListener('blur', function () {
                    var cep = this.value.replace(/\D/g, '');
                    if (cep.length === 8) {
                        fetch('https://viacep.com.br/ws/' + cep + '/json/')
                            .then(response => response.json())
                            .then(data => {
                                if (!data.erro) {
                                    var ruaInput = document.getElementById('rua');
                                    var bairroInput = document.getElementById('bairro');
                                    var cidadeInput = document.getElementById('cidade');
                                    var estadoInput = document.getElementById('estado');

                                    if (ruaInput) ruaInput.value = data.logradouro || '';
                                    if (bairroInput) bairroInput.value = data.bairro || '';
                                    if (cidadeInput) cidadeInput.value = data.localidade || '';
                                    if (estadoInput) estadoInput.value = data.uf || '';
                                }
                            })
                            .catch(error => console.log('Erro ao buscar CEP:', error));
                    }
                });
            }
        });
    </script>
    <?php
}
