$(document).ready(function () {
    function formatarPreco(valor) {
        return parseFloat(valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    function atualizarPrecoExibido() {
        var nome = $('#detalhesProduto .info1 h3').text().trim();
        var precoUnitario = obterPrecoProduto(nome, lerPrecoDaPagina());
        var qtde = parseInt($('#detalhesProduto .info3 .qtdeProduto .qtde').val()) || 1;
        var total = precoUnitario * qtde;

        if (!precoUnitario || isNaN(precoUnitario)) return;

        $('#precoProduto').text(formatarPreco(total));
        $('#detalhesProduto .info3 .precoProduto').text(precoUnitario);
    }

    atualizarPrecoExibido();

    if (typeof totalProduto === 'function') {
        totalProduto();
    }

    $(document).on('click', '.adicionarQtdeOpcao, .removerQtdeOpcao, .adicionarQtde, .removerQtde', function () {
        setTimeout(atualizarPrecoExibido, 50);
    });
});
