<?php 

// altere o nome da func para não ter erro de duplicidade
function script_injet_page() {
    if (strpos($_SERVER['REQUEST_URI'], '/url') !== false) {
        ?>
            <script>

            </script>
        <?php
    }
}

add_action('wp_footer', 'script_injet_page');