elemento.addEventListener("input", (event) => {
    var value = event.target.value.replace(/\D/g, '');

    // Captura o código da tecla pressionada
    var keyCode = event.keyCode || event.which;

    // Se o código da tecla for "delete" ou "backspace", não formate o valor
    if (keyCode === 8 || keyCode === 46) {
        return;
    }

    // Formatação do valor
    if (value.length > 2) {
        value = `(${value.slice(0, 2)}) ${value.slice(2, 7)}` + (value.length > 7 ? `-${value.slice(7, 11)}` : '');
    }

    // Atualiza o valor formatado no campo de entrada
    event.target.value = value;
});