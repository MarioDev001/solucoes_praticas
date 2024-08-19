<?php
function alterar_arquivo_js_my_order() {
    // Caminho relativo do arquivo JS dentro do plugin
    $caminho_relativo_js = 'rota do plugin a parti do nome de arquivo'; 

    // Obtenha o caminho absoluto do arquivo JS usando WP_PLUGIN_DIR
    $caminho_arquivo_js = WP_PLUGIN_DIR . '/' . $caminho_relativo_js;

    // Novo conteúdo JavaScript a ser inserido
    $novo_conteudo_js = <<<'EOD'
    // script que vai ficar no lugar do arquivo desejado
    EOD;

    // Escreva o novo conteúdo de volta ao arquivo 
    $resultado = file_put_contents($caminho_arquivo_js, $novo_conteudo_js, LOCK_EX);

    // Verifique se a escrita foi bem-sucedida
    if ($resultado === false) {
        error_log('Não foi possível escrever no arquivo.');
        return;
    }

    // Marca a alteração como feita para evitar execuções repetidas
    update_option('arquivo_js_alterado', true);
}

// Usa um gancho apropriado para chamar a função
add_action('after_setup_theme', 'alterar_arquivo_js_my_order');