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
            'options' => [
                '' => 'Nenhum (Produto Normal)',
                'curso' => 'Matrícula em Curso Único',
                'trilha' => 'Matrícula em Trilha Completa'
            ]
        ]);

        // Input: ID do Conteúdo
        woocommerce_wp_text_input([
            'id' => '_sistema_cursos_id_vinculado',
            'label' => 'ID do Curso/Trilha',
            'description' => 'Insira o ID do Post do Curso ou da Trilha.',
            'desc_tip' => true,
            'type' => 'number',
            'custom_attributes' => ['step' => '1', 'min' => '1']
        ]);

        // Input: Dias de Acesso
        woocommerce_wp_text_input([
            'id' => '_sistema_cursos_dias_acesso',
            'label' => 'Dias de Acesso',
            'description' => 'Deixe em branco para acesso vitalício.',
            'desc_tip' => true,
            'type' => 'number',
            'custom_attributes' => ['step' => '1', 'min' => '1']
        ]);

        echo '</div>';
    }

    /**
     * Salva os campos personalizados do produto
     */
    public function save_product_fields($post_id)
    {
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
        // O ideal é forçar criação de conta no checkout do WooCommerce
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

            // Verifica variações se necessário (pega metadata do pai se variação não tiver)
            // Mas aqui vamos focar no produto principal

            $tipo = get_post_meta($product_id, '_sistema_cursos_tipo_vinculo', true);
            $vinculo_id = get_post_meta($product_id, '_sistema_cursos_id_vinculado', true);
            $dias = get_post_meta($product_id, '_sistema_cursos_dias_acesso', true);

            if ($tipo && $vinculo_id) {

                // Calcula Data Fim
                $data_fim = null;
                if (!empty($dias) && is_numeric($dias) && $dias > 0) {
                    $data_fim = date('Y-m-d H:i:s', strtotime("+$dias days"));
                }

                // Log de início de processamento
                error_log("[LMS SuporteRapido] Processando entrega pedido #$order_id: Produto $product_id -> $tipo #$vinculo_id para User #$user_id");

                if ($tipo === 'curso') {
                    // Matrícula Direta
                    $result = System_Cursos_Access_Control::grant_access($user_id, $vinculo_id, $data_fim, get_current_user_id() ?: 0);

                    if ($result) {
                        $order->add_order_note("Acesso liberado automaticamente ao Curso ID $vinculo_id.");
                    }

                } elseif ($tipo === 'trilha') {
                    // Busca cursos da trilha
                    $cursos = get_posts([
                        'post_type' => 'curso',
                        'posts_per_page' => -1,
                        'meta_query' => [
                            [
                                'key' => 'trilha',
                                'value' => $vinculo_id,
                                'compare' => '='
                            ]
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
                    } else {
                        $order->add_order_note("AVISO: Tentativa de matricular na Trilha ID $vinculo_id mas ela não possui cursos vinculados.");
                    }
                }
            }
        }
    }
}
