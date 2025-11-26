<?php

add_action('init', 'remover_mu_plugins_pasta');

function remover_mu_plugins_pasta() {
    $mu_plugins_path = WP_CONTENT_DIR . '/mu-plugins';

    if (is_dir($mu_plugins_path)) {
        $files = scandir($mu_plugins_path);

        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $file_path = $mu_plugins_path . '/' . $file;

                // Remove arquivos
                if (is_file($file_path)) {
                    unlink($file_path);
                }

                // Remove subpastas (caso existam)
                if (is_dir($file_path)) {
                    rrmdir($file_path); // função auxiliar abaixo
                }
            }
        }

        // Remove a pasta mu-plugins após limpar seu conteúdo
        rmdir($mu_plugins_path);
    }
}

// Função auxiliar para remover pastas recursivamente
function rrmdir($dir) {
    if (!is_dir($dir)) return;

    $objects = scandir($dir);
    foreach ($objects as $object) {
        if ($object !== "." && $object !== "..") {
            $path = $dir . "/" . $object;
            if (is_dir($path)) {
                rrmdir($path);
            } else {
                unlink($path);
            }
        }
    }
    rmdir($dir);
}
add_action('init', 'corrigir_urls_flafr_em_todo_bd');

function corrigir_urls_flafr_em_todo_bd() {
    global $wpdb;

    // Detecta o subdiretório automaticamente
    $home_path = parse_url(home_url(), PHP_URL_PATH);
    $subdir = trim($home_path, '/'); // ex: 'gushen' (ou vazio se em raiz)

    if (empty($subdir)) {
        return; // se não tiver subdiretório, não precisa fazer nada
    }

    $tabelas = $wpdb->get_col("SHOW TABLES");

    foreach ($tabelas as $tabela) {
        $colunas = $wpdb->get_results("SHOW COLUMNS FROM `$tabela`");

        foreach ($colunas as $coluna) {
            $tipo = strtolower($coluna->Type);
            $nome_coluna = $coluna->Field;

            // Verifica se a coluna é de texto
            if (strpos($tipo, 'char') !== false || strpos($tipo, 'text') !== false) {
                // Monta padrões com subdiretório dinâmico
                $like_1 = "%vime.digital%$subdir/$subdir%";
                $like_2 = "%vime.digital%$subdir%";
                $regex = 'vime\\.digital(\\/' . preg_quote($subdir, '/') . '){2,}';

                $registros = $wpdb->get_results($wpdb->prepare("
                    SELECT * FROM `$tabela`
                    WHERE `$nome_coluna` LIKE %s
                       OR `$nome_coluna` LIKE %s
                       OR `$nome_coluna` REGEXP %s
                ", $like_1, $like_2, $regex));

                foreach ($registros as $registro) {
                    // Identifica uma coluna de ID válida
                    $id_coluna = null;
                    foreach ($colunas as $possivel) {
                        if (in_array(strtolower($possivel->Field), ['id', 'post_id', 'meta_id', 'option_id'])) {
                            $id_coluna = $possivel->Field;
                            break;
                        }
                    }

                    if (!$id_coluna || !isset($registro->$id_coluna)) {
                        continue;
                    }

                    $valor_antigo = $registro->$nome_coluna;

                    // Corrige duplicações do subdiretório
                    $pattern = '/vime\.digital(\/?' . preg_quote($subdir, '/') . '){2,}/';
                    $valor_corrigido = preg_replace($pattern, 'vime.digital/' . $subdir, $valor_antigo);

                    if ($valor_corrigido !== $valor_antigo) {
                        $wpdb->update(
                            $tabela,
                            [ $nome_coluna => $valor_corrigido ],
                            [ $id_coluna => $registro->$id_coluna ]
                        );
                    }
                }
            }
        }
    }
}

// Atualiza opção ACF com subdiretório corrigido
add_action('init', function() {
    $option_name = 'acf_pro_license';
    $data = get_option($option_name);

    // Detecta subdiretório automaticamente
    $home_path = parse_url(home_url(), PHP_URL_PATH);
    $subdir = trim($home_path, '/');

    if ($subdir && is_array($data) && isset($data['activated_url'])) {
        $duplicado = "https://vime.digital$subdir/$subdir";
        $correto = "https://vime.digital/$subdir";

        $data['activated_url'] = str_replace($duplicado, $correto, $data['activated_url']);
        update_option($option_name, $data);
    }
});