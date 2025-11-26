<?php 

function adicionar_swiper_carousel() {
    // Adiciona o CSS do Swiper
    wp_enqueue_style( 'swiper-css', "https://unpkg.com/swiper/swiper-bundle.min.css" );

    // Adiciona o JS do Swiper
    wp_enqueue_script( 'swiper-js', "https://unpkg.com/swiper/swiper-bundle.min.js", array(), null, true );
}

add_action( 'wp_enqueue_scripts', 'adicionar_swiper_carousel' );

function adicionar_script_carrossel() {
    ?>
<style>
#slide .swiper-container {
    width: auto;
    height: 380px;
	overflow: hidden;
	padding-top: 5px;

}

#slide .swiper-wrapper {
    flex-direction: column !important;
    height: auto;
	flex-wrap: nowrap;
}

#slide .swiper-slide {
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 24px;
	height: 350px;
}
	@media(max-width: 700px){
		#slide .swiper-container {
			height: 555px; 
		}
	}

</style>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
			let swiperContainer = document.querySelector('#slide .jet-listing-grid');  
			let swiperSlideWrapper = document.querySelector('#slide .jet-listing-grid__items');
			let swiperSlides = document.querySelectorAll('#slide .jet-listing-grid__item');


			// Verifica se os elementos existem antes de modificar
			if (swiperContainer && swiperSlideWrapper && swiperSlides.length > 0) {
				// Adiciona classes necessárias
				swiperContainer.classList.add('swiper-container');
				swiperSlideWrapper.classList.add('swiper-wrapper');

				swiperSlides.forEach((ele) => {
					ele.classList.add('swiper-slide'); 
				});

				// Inicializa o Swiper
				new Swiper(".swiper-container", {
					direction: "vertical", // Slide vertical
					slidesPerView: 1, // Apenas um slide visível por vez
					spaceBetween: 0, // Espaço entre os slides
					loop: true, // Habilita loop infinito
					freeMode: false, // Desativa rolagem livre para que funcione melhor com autoplay
					autoplay: {
						delay: 3000, // Tempo em milissegundos (3 segundos)
						disableOnInteraction: false, // Continua a rotação mesmo se o usuário interagir
					},
					mousewheel: true, // Habilita rolagem com o mouse
				});

			}
		});

    </script>
    <?php
}

add_action('wp_footer', 'adicionar_script_carrossel');