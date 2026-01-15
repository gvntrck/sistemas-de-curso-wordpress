<?php
/*
Snippet Name: Shortcode Cadastro de Usuário
Description: Adiciona o shortcode [cadastro-usuario] para registro de usuários com campos personalizados.
*/

function cadastro_usuario_shortcode()
{
    $output = '';
    $message = '';

    // Verifica se o formulário foi enviado
    if (isset($_POST['submit_cadastro_usuario']) && isset($_POST['cadastro_nonce_field']) && wp_verify_nonce($_POST['cadastro_nonce_field'], 'cadastro_usuario_action')) {

        // Sanitização dos campos
        $nome = sanitize_text_field($_POST['nome']);
        $sobrenome = sanitize_text_field($_POST['sobrenome']);
        $email = sanitize_email($_POST['email']);
        $cpf = sanitize_text_field($_POST['cpf']);
        $aniversario = sanitize_text_field($_POST['aniversario']);
        $instagram = sanitize_text_field($_POST['instagram']);

        // Endereço
        $cep = sanitize_text_field($_POST['cep']);
        $rua = sanitize_text_field($_POST['rua']);
        $numero = sanitize_text_field($_POST['numero']);
        $complemento = sanitize_text_field($_POST['complemento']);
        $bairro = sanitize_text_field($_POST['bairro']);
        $cidade = sanitize_text_field($_POST['cidade']);
        $estado = sanitize_text_field($_POST['estado']);

        // Validação básica
        if (empty($email) || empty($nome)) {
            $message = '<div style="color: red; margin-bottom: 15px;">Por favor, preencha o Nome e o Email.</div>';
        } elseif (email_exists($email)) {
            $message = '<div style="color: red; margin-bottom: 15px;">Este email já está cadastrado.</div>';
        } else {
            // Gerar senha aleatória
            $password = wp_generate_password(12, false);

            // Criar usuário (usa o email como username)
            $user_id = wp_create_user($email, $password, $email);

            if (!is_wp_error($user_id)) {
                // Atualizar Nome e Sobrenome
                wp_update_user([
                    'ID' => $user_id,
                    'first_name' => $nome,
                    'last_name' => $sobrenome,
                ]);

                // Salvar Meta Keys personalizadas
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

                // Mensagem de sucesso
                $message = '<div style="color: green; border: 1px solid green; padding: 10px; margin-bottom: 15px;">Usuário cadastrado com sucesso!</div>';

                // Limpar campos para não reaparecerem no form (opcional, aqui vou deixar o reload limpar naturalmente se o POST n for reenviado, mas como é a mesma pag, eles somem se nao preenchermos os values)
                // Se quiser manter os valores em caso de erro, precisaria preencher os value="" no form.
            } else {
                $message = '<div style="color: red; margin-bottom: 15px;">Erro ao criar usuário: ' . $user_id->get_error_message() . '</div>';
            }
        }
    }

    $output .= $message;

    // Início do buffer para o HTML do formulário
    ob_start();
    ?>
    <style>
        .shortcode-cadastro-form {
            max_width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .shortcode-cadastro-form h3 {
            margin-top: 20px;
            margin-bottom: 15px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
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
        .shortcode-form-group input[type="date"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .shortcode-submit-btn {
            background-color: #0073aa;
            color: white;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            border-radius: 4px;
        }

        .shortcode-submit-btn:hover {
            background-color: #005177;
        }
    </style>

    <form method="post" class="shortcode-cadastro-form">
        <?php wp_nonce_field('cadastro_usuario_action', 'cadastro_nonce_field'); ?>

        <h3>Dados Pessoais</h3>

        <div class="shortcode-form-group">
            <label for="nome">Nome *</label>
            <input type="text" name="nome" id="nome" required
                value="<?php echo isset($_POST['nome']) ? esc_attr($_POST['nome']) : ''; ?>">
        </div>

        <div class="shortcode-form-group">
            <label for="sobrenome">Sobrenome</label>
            <input type="text" name="sobrenome" id="sobrenome"
                value="<?php echo isset($_POST['sobrenome']) ? esc_attr($_POST['sobrenome']) : ''; ?>">
        </div>

        <div class="shortcode-form-group">
            <label for="cpf">CPF</label>
            <input type="text" name="cpf" id="cpf"
                value="<?php echo isset($_POST['cpf']) ? esc_attr($_POST['cpf']) : ''; ?>">
        </div>

        <div class="shortcode-form-group">
            <label for="aniversario">Data de Nascimento</label>
            <input type="date" name="aniversario" id="aniversario"
                value="<?php echo isset($_POST['aniversario']) ? esc_attr($_POST['aniversario']) : ''; ?>">
        </div>

        <div class="shortcode-form-group">
            <label for="instagram">Instagram</label>
            <input type="text" name="instagram" id="instagram"
                value="<?php echo isset($_POST['instagram']) ? esc_attr($_POST['instagram']) : ''; ?>">
        </div>

        <div class="shortcode-form-group">
            <label for="email">Email *</label>
            <input type="email" name="email" id="email" required
                value="<?php echo isset($_POST['email']) ? esc_attr($_POST['email']) : ''; ?>">
        </div>

        <h3>Endereço Completo</h3>

        <div class="shortcode-form-group">
            <label for="cep">CEP</label>
            <input type="text" name="cep" id="cep"
                value="<?php echo isset($_POST['cep']) ? esc_attr($_POST['cep']) : ''; ?>">
        </div>

        <div class="shortcode-form-group">
            <label for="rua">Rua</label>
            <input type="text" name="rua" id="rua"
                value="<?php echo isset($_POST['rua']) ? esc_attr($_POST['rua']) : ''; ?>">
        </div>

        <div class="shortcode-form-group">
            <label for="numero">Número</label>
            <input type="text" name="numero" id="numero"
                value="<?php echo isset($_POST['numero']) ? esc_attr($_POST['numero']) : ''; ?>">
        </div>

        <div class="shortcode-form-group">
            <label for="complemento">Complemento</label>
            <input type="text" name="complemento" id="complemento"
                value="<?php echo isset($_POST['complemento']) ? esc_attr($_POST['complemento']) : ''; ?>">
        </div>

        <div class="shortcode-form-group">
            <label for="bairro">Bairro</label>
            <input type="text" name="bairro" id="bairro"
                value="<?php echo isset($_POST['bairro']) ? esc_attr($_POST['bairro']) : ''; ?>">
        </div>

        <div class="shortcode-form-group">
            <label for="cidade">Cidade</label>
            <input type="text" name="cidade" id="cidade"
                value="<?php echo isset($_POST['cidade']) ? esc_attr($_POST['cidade']) : ''; ?>">
        </div>

        <div class="shortcode-form-group">
            <label for="estado">Estado</label>
            <input type="text" name="estado" id="estado"
                value="<?php echo isset($_POST['estado']) ? esc_attr($_POST['estado']) : ''; ?>">
        </div>

        <div class="shortcode-form-group">
            <input type="submit" name="submit_cadastro_usuario" class="shortcode-submit-btn" value="Cadastrar">
        </div>
    </form>
    <script>
        // CPF mask (format 000.000.000-00)
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

            // CEP auto-fill using ViaCEP API
            var cepInput = document.getElementById('cep');
            if (cepInput) {
                cepInput.addEventListener('blur', function () {
                    var cep = cepInput.value.replace(/\D/g, '').slice(0, 8);
                    if (cep.length === 8) {
                        fetch('https://viacep.com.br/ws/' + cep + '/json/')
                            .then(function (response) { return response.json(); })
                            .then(function (data) {
                                if (!data.erro) {
                                    if (document.getElementById('rua')) document.getElementById('rua').value = data.logradouro || '';
                                    if (document.getElementById('bairro')) document.getElementById('bairro').value = data.bairro || '';
                                    if (document.getElementById('cidade')) document.getElementById('cidade').value = data.localidade || '';
                                    if (document.getElementById('estado')) document.getElementById('estado').value = data.uf || '';
                                }
                            })
                            .catch(function (err) { console.error('Erro ao buscar CEP:', err); });
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
