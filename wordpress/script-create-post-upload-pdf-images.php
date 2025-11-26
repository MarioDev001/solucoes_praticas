<?php

function handle_custom_image_upload($image_url) {
    // Verificando se a URL da imagem é válida
    if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
        echo 'UR da imagem inválida.';
        return;
    }

    // Obtendo o conteúdo da imagem a partir da URL
    $image_content = file_get_contents($image_url);

    // Verificando se foi possível obter o conteúdo da imagem
    if ($image_content === false) {
        echo 'Erro ao acessar a URL da imagem.';
        return;
    }

    // Verificando se o conteúdo é uma imagem válida
    $image_info = getimagesizefromstring($image_content);
    if ($image_info === false) {
        echo 'O conteúdo fornecido não é uma imagem válida.';
        return;
    }

    // Verificando se a extensão corresponde ao tipo de arquivo
    $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
    $extension = pathinfo($image_url, PATHINFO_EXTENSION);
    if (!in_array(strtolower($extension), $allowed_extensions)) {
        echo 'Tipo de arquivo não suportado.';
        return;
    }

    // Criando um nome de arquivo único para a imagem
    $image_name = md5($image_url) . '.' . $extension;

    // Salvando o conteúdo da imagem em um arquivo temporário
    $upload_dir = wp_upload_dir();
    $image_file = $upload_dir['path'] . '/' . $image_name;

    // Salvando o conteúdo da imagem no arquivo temporário
    $saved = file_put_contents($image_file, $image_content);

    if ($saved !== false) {
        // Criando o attachment no WordPress
        $attachment = array(
            'guid'           => $upload_dir['url'] . '/' . $image_name,
            'post_mime_type' => $image_info['mime'],
            'post_title'     => sanitize_file_name(pathinfo($image_name, PATHINFO_FILENAME)),
            'post_content'   => '',
            'post_status'    => 'inherit'
        );

        // Inserindo o attachment no banco de dados do WordPress
        $attach_id = wp_insert_attachment($attachment, $image_file);

        if (!is_wp_error($attach_id)) {
            // Gerando os metadados da imagem
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attach_data = wp_generate_attachment_metadata($attach_id, $image_file);
            wp_update_attachment_metadata($attach_id, $attach_data);
         
            return $attach_id;
        } else {
            return $attach_id->get_error_message();
        }
    } else {
        echo 'Erro ao salvar a imagem no servidor.';
    }
}


function handle_custom_pdf_upload($pdf_url, $name) {
    // Verificando se a URL do PDF é válida
    if (!filter_var($pdf_url, FILTER_VALIDATE_URL)) {
        echo 'URL do PDF inválida.';
        return;
    }

    // Obtendo o conteúdo do PDF a partir da URL
    $pdf_content = file_get_contents($pdf_url);

    // Verificando se foi possível obter o conteúdo do PDF
    if ($pdf_content === false) {
        echo 'Erro ao acessar a URL do PDF.';
        return;
    }

    // Verificando se o conteúdo é um PDF válido
    if (mime_content_type('data://application/pdf;base64,' . base64_encode($pdf_content)) !== 'application/pdf') {
        echo 'O conteúdo fornecido não é um PDF válido.';
        return;
    }

    // Criando um nome de arquivo único para o PDF
    $pdf_name = $name . '.pdf';

    // Salvando o conteúdo do PDF em um arquivo temporário
    $upload_dir = wp_upload_dir();
    $pdf_file = $upload_dir['path'] . '/' . $pdf_name;

    // Salvando o conteúdo do PDF no arquivo temporário
    $saved = file_put_contents($pdf_file, $pdf_content);

    if ($saved !== false) {
        // Criando o attachment no WordPress
        $attachment = array(
            'guid'           => $upload_dir['url'] . '/' . $pdf_name,
            'post_mime_type' => 'application/pdf',
            'post_title'     => sanitize_file_name(pathinfo($pdf_name, PATHINFO_FILENAME)),
            'post_content'   => '',
            'post_status'    => 'inherit'
        );

        // Inserindo o attachment no banco de dados do WordPress
        $attach_id = wp_insert_attachment($attachment, $pdf_file);

        if (!is_wp_error($attach_id)) {
            // Não é necessário gerar metadados para PDFs, então simplesmente retorne o ID do attachment
            return $attach_id;
        } else {
            return $attach_id->get_error_message();
        }
    } else {
        echo 'Erro ao salvar o PDF no servidor.';
    }
}


function atualizar_meta_key_noticias($json_string) {
    // Verifica se a função get_post existe
    if (!function_exists('get_post')) {
        return;
    }

    // Decodifica a string JSON para um array PHP
    $objetos = json_decode($json_string, true);

    // Verifica se a decodificação foi bem-sucedida
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('Erro ao decodificar a string JSON: ' . json_last_error_msg());
        return;
    }

    foreach ($objetos as $objeto) {
        // Obtém o título do post
        $title = sanitize_text_field($objeto['title']);
        $noticiaNovaId = sanitize_text_field($objeto['noticiaNovaId']);
		$url = sanitize_text_field($objeto['url']);
		$url_pdf = sanitize_text_field($objeto['url_pdf']);
		$url_name = sanitize_text_field($objeto['url_name']);
        
        // Obtém o ID do post pelo título
        $post = get_page_by_title($title, OBJECT, 'revista');
        
        // Verifica se o post foi encontrado
        if ($post) {
            $post_id = $post->ID;
            
            // Verifica se o metadado 'capa' existe
            $capa = get_post_meta($post_id, 'capa', true);
			if (empty($capa) && !empty($url)) {
				$id_da_image = handle_custom_image_upload($url);
				
				update_post_meta($post_id, 'capa', $id_da_image);
                update_post_meta($post_id, '_capa', 'field_65d4fa185f3f7');
			}
			
			if(empty(get_post_meta($post_id, 'atualizacao', true)) && !empty($url_pdf)){
				$id_do_pdf = handle_custom_pdf_upload($url_pdf, $url_name);
				
				update_post_meta($post_id, 'atualizacao', 'ok');
				update_post_meta($post_id, 'pdf_da_revista', $id_do_pdf);
			}
			
			
        } else {
            // Caso o post não seja encontrado, exibe uma mensagem de erro
            error_log("Post com o título '{$title}' não encontrado.");
        }
    }
}


$json_string = '';

// Chama a função de atualização com o seu JSON
atualizar_meta_key_noticias($json_string);

