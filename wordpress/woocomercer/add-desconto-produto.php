<?php


function adicionar_script_button_desconto() {
    if (strpos($_SERVER['REQUEST_URI'], '/url da pagina desejada') !== false) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $.ajax({
                url: ajax_object.ajaxurl, // URL do AJAX no WordPress
                type: 'POST',
                data: {
                    action: 'registrar_desconto',
                    desconto: 'valor do desconto',
					product_id: 'Trocar pelo id do produto',
                    security: ajax_object.security // Nonce para segurança
                },
                success: function(response) {
                    console.log('Desconto registrado:', response);
                },
                error: function(xhr, status, error) {
                    console.error('Erro ao registrar o desconto:', error);
                }
            });
        });
        </script>
        <?php
    }
}
add_action('wp_footer', 'adicionar_script_button_desconto');



add_action('wp_footer', 'adicionar_script_button_desconto');

// Função para registrar o desconto via AJAX
add_action('wp_ajax_registrar_desconto', 'registrar_desconto');
add_action('wp_ajax_nopriv_registrar_desconto', 'registrar_desconto');

add_action('wp_ajax_registrar_desconto', 'registrar_desconto');
add_action('wp_ajax_nopriv_registrar_desconto', 'registrar_desconto');

function registrar_desconto() {
    check_ajax_referer('ajax-nonce', 'security');

    if (isset($_POST['desconto']) && isset($_POST['product_id'])) {
        $user_id = get_current_user_id();
        $desconto = sanitize_text_field($_POST['desconto']);
        $product_id = sanitize_text_field($_POST['product_id']);

        // Concatena o ID do produto ao meta_key
        $meta_key = 'desconto_escolhido_' . $product_id;

        // Salva a informação como meta dado de usuário
        update_user_meta($user_id, $meta_key, $desconto);

        wp_send_json_success('Desconto registrado');
    } else {
        wp_send_json_error('Desconto ou ID do produto não recebidos');
    }
}


add_action('woocommerce_before_calculate_totals', 'aplicar_desconto_personalizado');

function aplicar_desconto_personalizado($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    $user_id = get_current_user_id();

    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        $product_id = $cart_item['product_id'];

        // Recupera o desconto específico para este produto
        $meta_key = 'desconto_escolhido_' . $product_id;
        $desconto = get_user_meta($user_id, $meta_key, true);

        if ($desconto) {
            $preco_original = $cart_item['data']->get_regular_price(); // Obtém o preço original do produto

            // Se o produto tiver preço de venda, usa o preço de venda
            if ($cart_item['data']->is_on_sale()) {
                $preco_original = $cart_item['data']->get_sale_price();
            }

            // Aplica o desconto no preço original
            $novo_preco = $preco_original - $desconto;

            // Define o novo preço para o produto no carrinho
            $cart_item['data']->set_price($novo_preco);
        }
    }
}

function enqueue_custom_scripts() {
    wp_enqueue_script('custom-script', get_template_directory_uri() . '/js/custom-script.js', array('jquery'), null, true);

    wp_localize_script('custom-script', 'ajax_object', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'security' => wp_create_nonce('ajax-nonce')
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');
