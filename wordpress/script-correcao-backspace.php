<?php 

function script_correcao_backspace() {
    ?>
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                // Seleciona todos os inputs que aceitam texto
                let textInputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], input[type="number"], input[type="password"]');

                textInputs.forEach(input => {
                    input.addEventListener("keydown", (event) => {
                        if (event.key === "Backspace") {
                            event.preventDefault(); // Impede o comportamento padrão
                            input.value = input.value.slice(0, -1); // Remove o último caractere
                        }
                    });
                });
            });

        </script>
    <?php
}

add_action('wp_footer', 'script_correcao_backspace');