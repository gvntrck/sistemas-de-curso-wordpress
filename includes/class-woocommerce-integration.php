<?php
if (!defined('ABSPATH')) {
    exit;
}

class System_Cursos_WooCommerce
{

    public function __construct()
    {
        // Adicionar campos na aba General do produto
        add_action('woocommerce_product_options_general_product_data', [$this, 'add_product_fields']);

        // Salvar campos
        add_action('woocommerce_process_product_meta', [$this, 'save_product_fields']);

        // Liberar acesso quando pedido completado
        add_action('woocommerce_order_status_completed', [$this, 'grant_access_on_purchase']);

        // Opcional: Liberar acesso quando pagamento processado (status processing)
        // add_action('woocommerce_order_status_processing', [$this, 'grant_access_on_purchase']);
    }

    /**
     * Adiciona campos na aba Geral dos dados do produto
     */
    /**
     * Adiciona campos na aba Geral dos dados do produto
     */
    public function add_product_fields()
    {
        echo '<div class="options_group">';

        // Título da Seção
        echo '<p class="form-field"><strong>Integração LMS SuporteRapido</strong></p>';

        // Select: Tipo de Vínculo
        woocommerce_wp_select([
            'id' => '_sistema_cursos_tipo_vinculo',
            'label' => 'Tipo de Entrega',
            'description' => 'Selecione o que será entregue ao comprar este produto.',
            'desc_tip' => true,
            'class' => 'select short',
            'options' => [
                '' => 'Nenhum (Produto Normal)',
                'curso' => 'Matrícula em Curso Único',
                'trilha' => 'Matrícula em Trilha Completa',
                'grupo' => 'Acesso a Grupo de Alunos'
            ]
        ]);

        // Carregar Posts para os Selects
        $cursos = get_posts(['post_type' => 'curso', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);
        $trilhas = get_posts(['post_type' => 'trilha', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);
        $grupos = get_posts(['post_type' => 'grupo', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);

        // Preparar Opções
        $options_cursos = ['' => '-- Selecione o Curso --'];
        foreach ($cursos as $c)
            $options_cursos[$c->ID] = $c->post_title . " (ID: $c->ID)";

        $options_trilhas = ['' => '-- Selecione a Trilha --'];
        foreach ($trilhas as $t)
            $options_trilhas[$t->ID] = $t->post_title . " (ID: $t->ID)";

        $options_grupos = ['' => '-- Selecione o Grupo --'];
        foreach ($grupos as $g)
            $options_grupos[$g->ID] = $g->post_title . " (ID: $g->ID)";

        // Recuperar valor atual salvo para preencher o campo correto
        global $post;
        $current_value = get_post_meta($post->ID, '_sistema_cursos_id_vinculado', true);

        // Select: Curso
        // Nota: Usamos nomes de ID diferentes para o HTML mas salvamos no mesmo meta via save_product_fields
        woocommerce_wp_select([
            'id' => '_sistema_cursos_id_select_curso',
            'label' => 'Curso',
            'value' => $current_value,
            'options' => $options_cursos,
            'wrapper_class' => 'show_if_curso field_vinculo'
        ]);

        // Select: Trilha
        woocommerce_wp_select([
            'id' => '_sistema_cursos_id_select_trilha',
            'label' => 'Trilha',
            'value' => $current_value,
            'options' => $options_trilhas,
            'wrapper_class' => 'show_if_trilha field_vinculo'
        ]);

        // Select: Grupo
        woocommerce_wp_select([
            'id' => '_sistema_cursos_id_select_grupo',
            'label' => 'Grupo',
            'value' => $current_value,
            'options' => $options_grupos,
            'wrapper_class' => 'show_if_grupo field_vinculo'
        ]);

        // Input Hidden para armazenar o ID final (compatibilidade)
        // O JS vai atualizar este campo oculto baseado na seleção
        echo '<input type="hidden" name="_sistema_cursos_id_vinculado" id="_sistema_cursos_id_vinculado" value="' . esc_attr($current_value) . '">';


        // Input: Dias de Acesso
        woocommerce_wp_text_input([
            'id' => '_sistema_cursos_dias_acesso',
            'label' => 'Dias de Acesso',
            'description' => 'Deixe em branco para acesso vitalício.',
            'desc_tip' => true,
            'type' => 'number',
            'custom_attributes' => ['step' => '1', 'min' => '1']
        ]);

        ?>
        <script>
            jQuery(document).ready(function ($) {
                function toggleLMSFields() {
                    var tipo = $('#_sistema_cursos_tipo_vinculo').val();
                    $('.field_vinculo').hide();

                    if (tipo === 'curso') {
                        $('.show_if_curso').show();
                    } else if (tipo === 'trilha') {
                        $('.show_if_trilha').show();
                    } else if (tipo === 'grupo') {
                        $('.show_if_grupo').show();
                    }
                }

                // Initial run
                toggleLMSFields();

                // On change
                $('#_sistema_cursos_tipo_vinculo').change(function () {
                    toggleLMSFields();
                    // Limpar selects ao mudar tipo para evitar conflito
                    $('#_sistema_cursos_id_select_curso').val('');
                    $('#_sistema_cursos_id_select_trilha').val('');
                    $('#_sistema_cursos_id_select_grupo').val('');
                    $('#_sistema_cursos_id_vinculado').val('');
                });

                // Update hidden field when a specific select changes
                $('#_sistema_cursos_id_select_curso, #_sistema_cursos_id_select_trilha, #_sistema_cursos_id_select_grupo').change(function () {
                    var val = $(this).val();
                    if (val) {
                        $('#_sistema_cursos_id_vinculado').val(val);
                    }
                });
            });
        </script>
        <?php

        echo '</div>';
    }

    /**
     * Salva os campos personalizados do produto
     */
    public function save_product_fields($post_id)
    {
        // Se temos um dos selects preenchidos, usamos ele para popular o ID vinculado
        // Mas como usamos JS para atualizar o hidden field, basta salvar o hidden field ou processar lógica aqui.
        // O campo hidden `_sistema_cursos_id_vinculado` é o que importa.

        $fields = [
            '_sistema_cursos_tipo_vinculo',
            '_sistema_cursos_id_vinculado',
            '_sistema_cursos_dias_acesso'
        ];

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    }

    /**
     * Libera o acesso quando o pedido é pago (Status: Completed)
     */
    public function grant_access_on_purchase($order_id)
    {
        if (!$order_id)
            return;

        $order = wc_get_order($order_id);
        if (!$order)
            return;

        $user_id = $order->get_user_id();

        // Se não tiver user_id (compra como visitante), tenta buscar pelo email ou não faz nada
        if (!$user_id) {
            $user = get_user_by('email', $order->get_billing_email());
            if ($user) {
                $user_id = $user->ID;
            } else {
                return; // Não tem usuário para matricular
            }
        }

        // Percorre itens do pedido
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();

            $tipo = get_post_meta($product_id, '_sistema_cursos_tipo_vinculo', true);
            $vinculo_id = get_post_meta($product_id, '_sistema_cursos_id_vinculado', true);
            $dias = get_post_meta($product_id, '_sistema_cursos_dias_acesso', true);

            if ($tipo && $vinculo_id) {

                // Calcula Data Fim
                $data_fim = null;
                if (!empty($dias) && is_numeric($dias) && $dias > 0) {
                    $data_fim = date('Y-m-d H:i:s', strtotime("+$dias days"));
                }

                // Log processamento
                error_log("[LMS SuporteRapido] Processando entrega pedido #$order_id: $tipo #$vinculo_id para User #$user_id");

                if ($tipo === 'curso') {
                    // Matrícula Direta
                    $result = System_Cursos_Access_Control::grant_access($user_id, $vinculo_id, $data_fim, get_current_user_id() ?: 0);

                    if ($result) {
                        $order->add_order_note("Acesso liberado automaticamente ao Curso ID $vinculo_id.");
                    }

                } elseif ($tipo === 'trilha') {
                    // Busca cursos da trilha e matricula
                    $cursos = get_posts([
                        'post_type' => 'curso',
                        'posts_per_page' => -1,
                        'meta_query' => [
                            ['key' => 'trilha', 'value' => $vinculo_id, 'compare' => '=']
                        ],
                        'fields' => 'ids'
                    ]);

                    if (!empty($cursos)) {
                        $count = 0;
                        foreach ($cursos as $curso_id) {
                            System_Cursos_Access_Control::grant_access($user_id, $curso_id, $data_fim, get_current_user_id() ?: 0);
                            $count++;
                        }
                        $order->add_order_note("Acesso liberado automaticamente para Trilha ID $vinculo_id ($count cursos).");
                    }

                } elseif ($tipo === 'grupo') {
                    // Adicionar ao Grupo
                    $current_groups = get_user_meta($user_id, '_aluno_grupos', true);
                    if (!is_array($current_groups)) {
                        $current_groups = [];
                    }

                    if (!in_array($vinculo_id, $current_groups)) {
                        $current_groups[] = (int) $vinculo_id;
                        update_user_meta($user_id, '_aluno_grupos', $current_groups);
                        $order->add_order_note("Usuário adicionado ao Grupo ID $vinculo_id com sucesso.");
                    } else {
                        $order->add_order_note("Usuário já estava no Grupo ID $vinculo_id. Nenhuma ação tomada.");
                    }
                }
            }
        }
    }
}
