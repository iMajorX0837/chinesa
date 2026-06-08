function criarModalCarregando() {
    if (document.getElementById('modalCarregando')) return;

    var modal = document.createElement('div');
    modal.id = 'modalCarregando';
    modal.innerHTML =
        '<div class="bulls-spinner"></div>' +
        '<p class="bulls-loading-text">Preparando seu pedido...</p>';
    document.body.appendChild(modal);
}

function mostrarCarregamento() {
    criarModalCarregando();
    var modal = document.getElementById('modalCarregando');
    modal.style.display = 'flex';
    requestAnimationFrame(function () {
        modal.classList.add('ativo');
    });
}

function finalizar() {
    if (typeof totalProduto === 'function') {
        totalProduto();
    }

    var tipos = $("#detalhesProduto .info2 .tipo");
    var erros = [];

    tipos.each(function () {
        var atual = $(this);
        var maximo = parseInt(atual.data('maximo')) || 0;
        var titulo = atual.find('h3').text().replace(':', '').trim();
        var contador = 0;

        atual.find('.opcoes').each(function () {
            var qty = parseInt($(this).find('.qtdeOpcao').val()) || 0;
            if (qty > 0) contador += qty;
        });

        if (maximo > 0 && contador > maximo) {
            erros.push('Selecione no máximo ' + maximo + ' opções em "' + titulo + '".');
        }
    });

    if (erros.length > 0) {
        Swal.fire({ icon: 'warning', title: 'Atenção', text: erros[0] });
        return;
    }

    var nome = $("#detalhesProduto .info1 h3").text().trim();
    var precoPagina = lerPrecoDaPagina();
    var precoCalculado = parseFloat($("#detalhesProduto .info3 .precoProduto").text()) || 0;
    var preco = obterPrecoProduto(nome, precoPagina || precoCalculado);
    var qtde = parseInt($("#detalhesProduto .info3 .qtdeProduto .qtde").val()) || 1;
    var observacao = $("#detalhesProduto .info2 .observacao").val().trim();
    var imagem = $("#detalhesProduto .info1 .fotoProduto img").attr('src') || '';
    var precoPromocao = $("#detalhesProduto .info1 .precoPromocao").text().trim();

    var complementos = [];
    tipos.each(function () {
        var tipo = $(this).find('h3').text().replace(':', '').trim();
        var selecionados = [];

        $(this).find('.opcoes').each(function () {
            var qty = parseInt($(this).find('.qtdeOpcao').val()) || 0;
            if (qty > 0) {
                var nomeOpcao = $(this).find('.nome b').text().trim();
                var detalhe = $(this).find('.detalheOpcao').text().trim();
                var texto = nomeOpcao;
                if (detalhe) texto += ' (' + detalhe + ')';
                if (qty > 1) texto = qty + 'x ' + texto;
                selecionados.push(texto);
            }
        });

        if (selecionados.length > 0) {
            complementos.push(tipo + ': ' + selecionados.join(', '));
        }
    });

    var item = {
        nome: nome,
        preco: preco,
        precoPromocao: precoPromocao,
        qtde: qtde,
        imagem: imagem,
        complementos: complementos,
        observacao: observacao
    };

    var cart = [];
    try {
        cart = JSON.parse(localStorage.getItem('bulls_cart') || '[]');
    } catch (e) {
        cart = [];
    }

    cart.push(item);
    localStorage.setItem('bulls_cart', JSON.stringify(cart));

    var btn = document.querySelector('.info3 .btn');
    if (btn) {
        btn.disabled = true;
        btn.style.opacity = '0.7';
    }

    mostrarCarregamento();

    setTimeout(function () {
        window.location.href = '/checkout.html';
    }, 900);
}

$(document).ready(function () {
    criarModalCarregando();
});
