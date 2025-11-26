<?php

function enqueue_swiper_assets() {
    // Estilos do Swiper
    wp_enqueue_style('swiper-style', 'https://unpkg.com/swiper/swiper-bundle.min.css');
    // Script do Swiper
    wp_enqueue_script('swiper-script', 'https://unpkg.com/swiper/swiper-bundle.min.js', [], null, true);
    // Script personalizado para inicializar o Swiper
    wp_enqueue_script('custom-slider', get_template_directory_uri() . '/js/custom-slider.js', ['swiper-script'], null, true);
}
add_action('wp_enqueue_scripts', 'enqueue_swiper_assets');
