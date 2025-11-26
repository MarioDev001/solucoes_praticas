
<?php
// Este script enviar files do tipo img e cria uma api que receber esses dados
// JS - Coleta os files, coloca em uma array, e encaminha para a api. envia dados para referenciar as imagens a um post.
// PHP - Cria uma api que vai receber uma array de imagens, salva na biblioteca de midia, e em seguida insere em um post.

function adicionar_script_personalizado() {
    if (strpos($_SERVER['REQUEST_URI'], '/garantias-mundo-fone/') !== false) {
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
				
				function fileUpload(files, name) {
					var uploadUrl = '<?php echo esc_url( admin_url( 'admin-post.php?action=upload_image' ) ); ?>';
					 const formData = new FormData();

					for (let i = 0; i < files.length; i++) {
						formData.append('images[]', files[i]);
					}
									
					formData.append('idPost', window.sessionStorage.getItem('idResponseForm'));					
					formData.append('name', name);
					
					const options = {
						method: 'POST',
						body: formData
					};

					fetch(uploadUrl, options)
					.then(response => response.json())
					.then(data => {
						window.sessionStorage.setItem(name, true)
					})
					.catch(error => {
						console.error('Erro:', error);
					});
					
					
				}
				
			})

        </script>
		
        <?php
    }
}


add_action( 'admin_post_nopriv_upload_image', 'upload_image' );
add_action( 'admin_post_upload_image', 'upload_image' );

function upload_image() {
    if (!empty($_FILES['images'])) {
        $post_id = intval($_POST['idPost']);
        $meta_key = sanitize_text_field($_POST['name']);

        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            $file_name = $_FILES['images']['name'][$key];
            $file_tmp = $_FILES['images']['tmp_name'][$key];

            // Carregar o arquivo na biblioteca de mídia
            $upload = wp_upload_bits($file_name, null, file_get_contents($file_tmp));
            if (!$upload['error']) {
                $file_path = $upload['file'];

                // Preparar os dados do anexo
                $attachment = array(
                    'post_mime_type' => $upload['type'],
                    'post_title'     => sanitize_file_name($file_name),
                    'post_content'   => '',
                    'post_status'    => 'inherit'
                );

                // Inserir o anexo na biblioteca de mídia
                $attach_id = wp_insert_attachment($attachment, $file_path, $post_id);
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
                wp_update_attachment_metadata($attach_id, $attach_data);

                // Atualizar o meta do post com o ID do anexo
                $existing_values = get_post_meta($post_id, $meta_key, true);
                if ($existing_values) {
                    $existing_values .= ',' . $attach_id;
                    update_post_meta($post_id, $meta_key, $existing_values);
                } else {
                    update_post_meta($post_id, $meta_key, $attach_id);
                }
            }
        }

        // Responda com um JSON para o JavaScript
        wp_send_json_success('Imagens carregadas com sucesso.');
    } else {
        wp_send_json_error('Nenhuma imagem recebida.');
    }

    wp_die();
}


