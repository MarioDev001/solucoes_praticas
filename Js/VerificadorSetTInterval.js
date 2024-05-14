// Verifica um objeto usando o setInterval até encontralo e limpar a execução
var cont = 0;
var verific = setInterval(cardAtiva, 2000);

function cardAtiva() {
    var typeServicoElements = document.querySelectorAll("");

    if (typeServicoElements.length > 0) {
		clearInterval(verific);
    } else if (cont >= 4) {
        clearInterval(verific);
    }
    cont += 1;
}
	
