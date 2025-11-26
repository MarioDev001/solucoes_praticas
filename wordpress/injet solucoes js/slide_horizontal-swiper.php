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
    #verticalcolumnonecode .jet-listing-grid.jet-listing {
        height: 600px; 
		padding: 100px 0;
    }

	.swiper-wrapper{
		flex-wrap: nowrap;
		
	}
	@media(max-width: 500px){
		#verticalcolumnonecode .jet-listing-grid.jet-listing {
	 
			padding: 70px 0;
		}
		.jet-listing-grid__item .container{
			flex-wrap: nowrap;
		}
	}

</style>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            let swiperContainer = document.querySelector('#verticalcolumnonecode .jet-listing-grid.jet-listing');  
            let swiperSlideWrapper = document.querySelector('#verticalcolumnonecode .jet-listing-grid.jet-listing .jet-listing-grid__items');
            let swiperSlide = document.querySelectorAll('#verticalcolumnonecode .jet-listing-grid.jet-listing .jet-listing-grid__item');
			let maxSlideHeight = 0;

			// Itera sobre os slides para encontrar o de maior altura
			swiperSlide.forEach((slide) => {
				let slideHeight = slide.offsetHeight; // Obtém a altura do slide
				if (slideHeight > maxSlideHeight) {
					maxSlideHeight = slideHeight; // Atualiza se a altura atual for maior
				}
			});
			let widt = window.innerWidth
			if(widt < 700 && !window.location.href.includes('sobre-nos')){
			   document.querySelector('#verticalcolumnonecode .jet-listing-grid.jet-listing').style.width = (widt - 20) + 'px'
			} else if(widt < 700 && window.location.href.includes('sobre-nos')){
				document.querySelector('#verticalcolumnonecode .jet-listing-grid.jet-listing').style.width = (widt - 30) + 'px'
			}
			console.log(maxSlideHeight)
			if(widt < 700){
				swiperContainer.style.height = (maxSlideHeight + 150) + 'px'
			}
            // Verifica se o swiperContainer existe antes de adicionar as classes
            if (swiperContainer) {
                swiperContainer.classList.add('swiper-container');
                swiperSlideWrapper.classList.add('swiper-wrapper');

                // Adiciona as classes swiper-slide a cada item de slide
                swiperSlide.forEach((ele) => {
                    ele.classList.add('swiper-slide'); 
                });

                // Inicializa o Swiper
                const swiper = new Swiper('.swiper-container', {
					direction: 'vertical',         // Define a rolagem vertical
					initialSlide: 1,               // Inicia no segundo slide
					pagination: {
						el: '.swiper-pagination',
						clickable: true,
					},
					navigation: {
						nextEl: '.swiper-button-next',
						prevEl: '.swiper-button-prev',
					},
					autoplay: {
						delay: 3000,               // Define o intervalo entre slides (3 segundos)
						disableOnInteraction: false, // Continua o autoplay mesmo após interação
					},
					lazy: true,                    // Habilita o carregamento "lazy"
					mousewheel: true,              // Ativa a navegação via scroll do mouse
					on: {
						reachEnd: function () {
							// Duplica os slides existentes
							let slidesHtml = swiperSlideWrapper.innerHTML; // Pega o HTML dos slides
							swiperSlideWrapper.insertAdjacentHTML('beforeend', slidesHtml); // Insere ao final
							swiper.update();        // Atualiza o Swiper para reconhecer os novos slides
						},
						init: function() {
							// Configura o evento para pausar o autoplay ao clicar
							document.querySelector('.swiper-container').addEventListener('mouseenter', () => {
								swiper.autoplay.stop();  // Pausa o autoplay ao passar o mouse sobre o slider
							});
							document.querySelector('.swiper-container').addEventListener('mouseleave', () => {
								swiper.autoplay.start(); // Retoma o autoplay ao tirar o mouse do slider
							});
						}
					}
				});

            }
        });
    </script>
    <?php
}

add_action('wp_footer', 'adicionar_script_carrossel');
