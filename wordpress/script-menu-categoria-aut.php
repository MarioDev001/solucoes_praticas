<?php

function listar_menu_items_por_term($term_id, $tt_id) {
    global $wpdb;

    // ID do menu específico (term_taxonomy_id) que você quer usar
    $menu_term_taxonomy_id = // id do menu, verificar na pagina do adm menu e ver pelo inspecionar o id;

    // 1. Pega os object_id da wp_term_relationships
    $object_ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT object_id FROM {$wpdb->prefix}term_relationships WHERE term_taxonomy_id = %d",
            $menu_term_taxonomy_id
        )
    );

    if (empty($object_ids)) {
        return;
    }

    foreach ($object_ids as $object_id) {
        // 2. Verifica se o postmeta `_menu_item_object` do object_id é igual a 'product_cat'
        $menu_item_object = get_post_meta($object_id, '_menu_item_object', true);

        if ($menu_item_object === 'product_cat') {
            $menu_item_object_id = get_post_meta($object_id, '_menu_item_object_id', true); // ID da categoria

            // 3. Verifica se a categoria tem pai
            $categoria_ascendente = 0;

            $term = get_term($menu_item_object_id, 'product_cat');

            if ($term && !is_wp_error($term) && $term->parent && $term->parent != 0 && $term->parent != $menu_item_object_id) {
                $categoria_ascendente = $term->parent;

                // 4. Busca o post_id do menu que representa essa categoria pai
                $parent_post_id = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT post_id FROM {$wpdb->prefix}postmeta 
                         WHERE meta_key = '_menu_item_object_id' AND meta_value = %d",
                        $categoria_ascendente
                    )
                );

                // 5. Se encontrou o menu pai, atualiza o parent do menu atual
                if ($parent_post_id) {
                    update_post_meta($object_id, '_menu_item_menu_item_parent', $parent_post_id);
                }
            } else {
                // Se não tem pai válido, define o parent como 0
                update_post_meta($object_id, '_menu_item_menu_item_parent', 0);
            }
        }
    }
}
add_action('created_product_cat', 'listar_menu_items_por_term', 10, 2);
add_action('edited_product_cat', 'listar_menu_items_por_term', 10, 2);

//add_action('init', 'listar_menu_items_por_term');
