<?php 

function add_google_charts_script() {
    // Adiciona o script do Google Charts ao head
    wp_enqueue_script(
        'google-charts', // Nome do script
        'https://cdn.jsdelivr.net/npm/chart.js', // URL do script
        array(), // Dependências (vazio neste caso)
        null,    // Versão (null para não colocar versão)
        false    // Coloca no head (false) em vez do footer
    );
}
add_action('wp_enqueue_scripts', 'add_google_charts_script');

function adicionar_script_my_aacount() {
    if (strpos($_SERVER['REQUEST_URI'], '/minha-conta') !== false) {
        $current_user = wp_get_current_user();
        // Verifica se o usuário tem as funções 'administrator' ou 'colab'
        if (in_array('administrator', (array)$current_user->roles) || in_array('colab', (array)$current_user->roles)) {
            
            global $wpdb;
            $current_user_id = get_current_user_id();

            $products_array = array();

            $products = $wpdb->get_results(
				$wpdb->prepare(
					"
					SELECT p.ID, p.post_title
					FROM {$wpdb->prefix}posts AS p
					INNER JOIN {$wpdb->prefix}postmeta AS pm ON p.ID = pm.post_id
					WHERE pm.meta_key = 'autor'
					AND pm.meta_value = %d
					AND p.post_type = 'product'
					AND p.post_status = 'publish'
					",
					$current_user_id
				)
			);
			

            $date_30_days_ago = date('Y-m-d H:i:s', strtotime('-30 days'));

            if ( !empty( $products ) ) {
                foreach ( $products as $product ) {
                    $product_link = get_permalink($product->ID);
                    $product_obj = wc_get_product($product->ID);
                    $price = $product_obj->get_price();

                    $total_sales = $wpdb->get_var(
                        $wpdb->prepare(
                            "
                            SELECT SUM(qty.meta_value) 
                            FROM {$wpdb->prefix}woocommerce_order_items AS oi
                            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS qty ON oi.order_item_id = qty.order_item_id
                            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oim ON oi.order_item_id = oim.order_item_id
                            INNER JOIN {$wpdb->prefix}posts AS o ON o.ID = oi.order_id
                            WHERE oim.meta_key = '_product_id'
                            AND oim.meta_value = %d
                            AND qty.meta_key = '_qty' 
                            AND o.post_type = 'shop_order'
                            AND o.post_status IN ('wc-completed')
                            ",
                            $product->ID
                        )
                    );

                    $total_sales_revenue = $total_sales * $price; // Cálculo do total de receitas

                    $sales_last_30_days = $wpdb->get_var(
                        $wpdb->prepare(
                            "
                            SELECT SUM(oim_qty.meta_value)
                            FROM {$wpdb->prefix}woocommerce_order_items AS oi
                            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oim ON oi.order_item_id = oim.order_item_id
                            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS oim_qty ON oi.order_item_id = oim_qty.order_item_id
                            INNER JOIN {$wpdb->prefix}posts AS o ON o.ID = oi.order_id
                            WHERE oim.meta_key = '_product_id'
                            AND oim.meta_value = %d
                            AND oim_qty.meta_key = '_qty'
                            AND o.post_type = 'shop_order'
                            AND o.post_status IN ('wc-completed', 'wc-processing')
                            AND o.post_date >= %s
                            ",
                            $product->ID,
                            $date_30_days_ago
                        )
                    );

                    $sales_last_30_days_revenue = $sales_last_30_days * $price; // Cálculo do total de receitas dos últimos 30 dias

                    $products_array[] = array(
                        'ID' => $product->ID,
                        'title' => $product->post_title,
                        'total_sales' => $total_sales,
                        'sales_last_30_days' => $sales_last_30_days,
                        'link_product' => $product_link,
                        'price' => $price,
                        'total_sales_revenue' => $total_sales_revenue,
                        'sales_last_30_days_revenue' => $sales_last_30_days_revenue
                    );
                }
            }

		
		$sales_totals = array();
		$total_sales_count = 0;

		if (!empty($products)) {
			// Obter o total de vendas dos produtos do usuário nos últimos 6 meses
			$date_now = new DateTime();
			$date_now->modify('-6 months'); // Subtrai 6 meses
			$start_date = $date_now->format('Y-m-d H:i:s');

			// Obter vendas do usuário nos últimos 6 meses
			$sales_query = $wpdb->prepare("
				SELECT order_item_meta.meta_value AS product_id, 
					   DATE_FORMAT(orders.post_date, '%Y-%m') AS month,
					   SUM(qty.meta_value) AS total_sales 
				FROM {$wpdb->prefix}woocommerce_order_items AS order_items
				INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
				INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS qty ON order_items.order_item_id = qty.order_item_id 
				INNER JOIN {$wpdb->prefix}posts AS orders ON orders.ID = order_items.order_id
				INNER JOIN {$wpdb->prefix}postmeta AS pm ON order_item_meta.meta_value = pm.post_id
				WHERE order_item_meta.meta_key = '_product_id'
				AND qty.meta_key = '_qty' 
				AND pm.meta_key = 'autor' 
				AND pm.meta_value = %d
				AND orders.post_date >= %s
				GROUP BY month, order_item_meta.meta_value
			", $current_user_id, $start_date);

			// Executa a consulta
			$sales_data = $wpdb->get_results($sales_query);

			// Verifica se a consulta retornou dados
			if (empty($sales_data)) {
				// Para depuração: Mostra a consulta e dados de filtro
				error_log("Consulta sem resultados: " . $sales_query);
				error_log("Usuário: " . $current_user_id);
				error_log("Data Inicial: " . $start_date);
			}

			// Preenche a array de totais de vendas por mês e produto
			$sales_totals = [];
			foreach ($sales_data as $sale) {
				// Verifica se o mês já existe no array
				if (!isset($sales_totals[$sale->product_id])) {
					$sales_totals[$sale->product_id] = [];
				}

				// Armazena as vendas totais para cada produto por mês
				$sales_totals[$sale->product_id][$sale->month] = $sale->total_sales;

				// Soma total das vendas de todos os produtos
				$total_sales_count += $sale->total_sales;
			}

		}


        ?>
        <script> 
            document.addEventListener("DOMContentLoaded", () => {
                let menuLateral = document.querySelector('.woocommerce-MyAccount-navigation');
                let divContent = document.querySelector('.woocommerce-MyAccount-content .woocommerce-MyAccount-content-wrapper');
                
                // Converte os dados do PHP para JSON
                let productsArray = <?php echo json_encode($products_array); ?>;
                let currentUserId = '<?php echo $current_user_id; ?>';
				let vendas_totais = <?php echo json_encode($sales_totals); ?>;
				let total_vendas = <?php echo json_encode($total_sales_count); ?>; 
				
				
                console.log(productsArray, currentUserId, vendas_totais, total_vendas)
				
				
				function mesesAnteriores(dadosMes) {
					var dataAtual = new Date();
					var meses = [];

					// Array com os nomes dos meses em português
					var nomesMeses = [
						"Janeiro", "Fevereiro", "Março", "Abril", 
						"Maio", "Junho", "Julho", "Agosto", 
						"Setembro", "Outubro", "Novembro", "Dezembro"
					];

					// Loop para pegar os últimos 6 meses
					for (var i = 0; i < 6; i++) {
						var mes = new Date(dataAtual.getFullYear(), dataAtual.getMonth() - i, 1);
						var mesNumero = (mes.getMonth() + 1).toString().padStart(2, '0');
						var ano = mes.getFullYear();

						// Obter o nome do mês
						var mesNome = nomesMeses[mes.getMonth()];

						let consulta = ano + '-' + mesNumero;
						let valor = isNaN(Number(dadosMes[consulta])) ? 0 : Number(dadosMes[consulta]); 
						

						meses.push({mesNome: mesNome, valor:valor}); 
						
					}

					meses.reverse();
					return meses; // Retorna a lista de meses com seus números, nomes e valores
				}
				
				function obterUltimosSeisMeses() {
					const nomesMeses = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];
					let data = new Date();
					let meses = [];

					for (let i = 0; i < 6; i++) {
						// Obter o nome do mês atual
						let nomeMes = nomesMeses[data.getMonth()];
						meses.push(nomeMes);

						// Subtrair 1 mês da data atual
						data.setMonth(data.getMonth() - 1);
					}

					return meses.reverse();
				}

				function obterCorAleatoria() {
					const letras = '0123456789ABCDEF';
					let cor = '#'; // Inicia com o símbolo #

					for (let i = 0; i < 6; i++) {
						// Adiciona um caractere aleatório da string de letras
						cor += letras[Math.floor(Math.random() * 16)];
					}

					return cor;
				}

				
				function chartInsetGoogle(){
					
					const ctx = document.getElementById('myLineChart').getContext('2d');
					
					function transformarEmArray(obj) {
						const resultado = [];
						obj.forEach((ele) => {
							let meses = mesesAnteriores(vendas_totais[ele.ID]);
							let valor1 = meses.map(valor => valor.valor);
							 resultado.push({
								label: ele['title'],
								backgroundColor: 'transparent',
								borderColor: obterCorAleatoria(),
								data: valor1, 
								fill: true,
								tension: 0.4,
							});
						})
						console.log(resultado)
						return resultado;
					}

					let dadosData = transformarEmArray(productsArray)
					console.log(dadosData)
					const myLineChart = new Chart(ctx, {
						type: 'line',
						data: {
							labels: obterUltimosSeisMeses(), 
							datasets: dadosData
						},
						options: {
							responsive: true, // Torna o gráfico responsivo
							scales: {
								y: {
									beginAtZero: true, // Começar o eixo Y do zero
									max: total_vendas + 5 // Define o valor máximo (adicionando um buffer de 5)
								}
							}
						}
					});

					
				}
				
                function insertContentPage(){
                    divContent.innerHTML = '';
					let divChartTag = document.createElement('canvas')
					divChartTag.id = 'myLineChart';
					divContent.appendChild(divChartTag)

					chartInsetGoogle()
                    // Cria a tabela e o cabeçalho
                    let table = document.createElement('table');
                    table.className = 'woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table';

                    let thead = document.createElement('thead');
                    let trHead = document.createElement('tr');
                    
                    // Colunas do cabeçalho
                    let headers = ['Produto', 'Preço', 'Vendas /30 Dias', 'Vendas Totais', 'Ações'];
                    headers.forEach((header, i) => {
                        let th = document.createElement('th');
                        th.scope = 'col';
                        th.className = i == 2 || i == 3 ? 'woocommerce-orders-table__header table_text' : 'woocommerce-orders-table__header';
                        th.innerHTML = `<span class="nobr">${header}</span>`;
                        trHead.appendChild(th);
                    });
                    thead.appendChild(trHead);
                    table.appendChild(thead);

                    // Corpo da tabela
                    let tbody = document.createElement('tbody');
					console.log(productsArray)
                    // Exibe os produtos e suas vendas
                    if (productsArray.length > 0) {
                        productsArray.forEach(product => {
                            let tr = document.createElement('tr');
                            tr.className = 'woocommerce-orders-table__row';

                            // Coluna Produto
                            let tdProduct = document.createElement('td');
                            tdProduct.className = 'woocommerce-orders-table__cell woocommerce-orders-table__cell-product';
                            tdProduct.innerHTML = `<h2 class="h2Tag">${product.title}</h2>`;
                            tr.appendChild(tdProduct);
							
							// Coluna Preços
                            let tdPrice = document.createElement('td');
                            tdPrice.className = 'woocommerce-orders-table__cell';
                            tdPrice.innerText = product.price;
                            tr.appendChild(tdPrice);


                            // Coluna Vendas Últimos 30 Dias
                            let tdSales30Days = document.createElement('td');
                            tdSales30Days.className = 'woocommerce-orders-table__cell table_text';
                            tdSales30Days.innerText = product.sales_last_30_days + ' / R$' + product.sales_last_30_days_revenue.toFixed(2).replace('.', ',');
                            tr.appendChild(tdSales30Days);
							
							// Coluna Vendas Totais
                            let tdTotalSales = document.createElement('td');
                            tdTotalSales.className = 'woocommerce-orders-table__cell table_text';
                            tdTotalSales.innerText = product.total_sales + ' / R$' +product.total_sales_revenue.toFixed(2).replace('.', ',');
                            tr.appendChild(tdTotalSales);

                            // Coluna Ações
                            let tdActions = document.createElement('td');
                            tdActions.className = 'woocommerce-orders-table__cell woocommerce-orders-table__cell-order-actions';
                            tdActions.innerHTML = `<a href="${product.link_product}" class="woocommerce-button button view" target="_blank">Visualizar </a>`;
                            tr.appendChild(tdActions);

                            tbody.appendChild(tr);
                        });
                    } else {
                        let tr = document.createElement('tr');
                        let tdEmpty = document.createElement('td');
                        tdEmpty.colSpan = 4;
                        tdEmpty.innerText = 'Nenhum produto encontrado';
                        tr.appendChild(tdEmpty);
                        tbody.appendChild(tr);
                    }

                    table.appendChild(tbody);
                    divContent.appendChild(table);
                }
                
                if(menuLateral){
                    let ulPai = menuLateral.querySelector('ul');
					
                    
                    let liTag = document.createElement('li');
                    liTag.className = 'woocommerce-MyAccount-navigation-link woocommerce-MyAccount-navigation-link--dashboard';
                    
                    let aTag = document.createElement('a');
                    aTag.href = 'minha-conta/#minhas-vendas';
                    aTag.innerText = 'Minhas vendas';
                    
                    liTag.appendChild(aTag);
                    ulPai.insertBefore(liTag, ulPai.children[2]);
					
					if(window.location.href.includes('/#minhas-vendas')){
						document.querySelector('.is-active').classList.remove('is-active')
						liTag.className = 'is-active'
						insertContentPage()
					}
                }
            });
        </script>
		<style>
			#myLineChart{
				margin-bottom: 50px;
			}
			.h2Tag{
				margin: 0 !important;
				font-weight: 600 !important;
				font-size: 14px !important;
				color: #4D4D4D !important;
			}
			.table_text{
				text-align: center !important;
			}
		</style>
        <?php
        }
    }
}
add_action('wp_footer', 'adicionar_script_my_aacount');
