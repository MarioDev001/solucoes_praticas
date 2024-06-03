<?php 

function adicionar_script_personalizado() {
    if (strpos($_SERVER['REQUEST_URI'], '/pagina-desejada') !== false) {
        ?>
        <?php
    }
}

add_action('wp_footer', 'adicionar_script_personalizado');