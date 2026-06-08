var PRECOS_PRODUTOS = {
    "2 Marmitex Pequena Com Churrasco": 23.90,
    "2 Marmitex Grande Com Churrasco": 28.90,
    "3 Kit Familia Grande Com Churrasco": 39.90,
    "2 Marmitex Grande Com Bife Milanesa Frango": 23.90,
    "2 Marmitex Grande Com Strogonof": 25.90,
    "2 Marmitex Grande de Costela Assada": 29.90,
    "2 Marmitex Grande Parmegiana": 25.90,
    "2 Marmitex Grande de File de Peixe": 26.90,
    "2 Marmitex Grande de Feijoada": 29.90
};

function obterPrecoProduto(nome, precoFallback) {
    if (nome && PRECOS_PRODUTOS[nome]) {
        return PRECOS_PRODUTOS[nome];
    }
    var preco = parseFloat(precoFallback);
    return isNaN(preco) ? 0 : preco;
}
