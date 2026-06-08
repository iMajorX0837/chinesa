(function () {
    let currentPixCode = '';

    const form = document.getElementById('checkoutForm');
    const panelDados = document.getElementById('panelDados');
    const panelResumo = document.getElementById('panelResumo');
    const btnContinuar = document.getElementById('btnContinuar');
    const btnPagar = document.getElementById('btnPagar');
    const cartItems = document.getElementById('cartItems');
    const cartTotal = document.getElementById('cartTotal');
    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    const pixModal = document.getElementById('pixModal');
    const pixKeyEl = document.getElementById('pixKey');
    const pixQrCodeEl = document.getElementById('pixQrCode');
    const orderNumberEl = document.getElementById('orderNumber');
    const btnCopyPix = document.getElementById('btnCopyPix');

    function getTransactionApiUrl() {
        var host = window.location.hostname;
        if (host === 'localhost' || host === '127.0.0.1') {
            return '/php-api/create-transaction.php';
        }
        return '/api/create-transaction';
    }

    function formatMoney(value) {
        var num = parseFloat(value);
        if (isNaN(num)) return 'R$ 0,00';
        return num.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    function getPrecoItem(item) {
        return obterPrecoProduto(item.nome, item.preco);
    }

    function getCart() {
        try {
            return JSON.parse(localStorage.getItem('bulls_cart') || '[]');
        } catch (e) {
            return [];
        }
    }

    function renderCart() {
        const cart = getCart();

        if (cart.length === 0) {
            cartItems.innerHTML = '<div class="empty-cart"><p>Seu carrinho está vazio.</p><a href="/index.html">Voltar ao cardápio</a></div>';
            cartTotal.textContent = 'R$ 0,00';
            btnPagar.disabled = true;
            return;
        }

        let total = 0;
        const cartAtualizado = cart.map(function (item) {
            const preco = getPrecoItem(item);
            item.preco = preco;
            return item;
        });
        localStorage.setItem('bulls_cart', JSON.stringify(cartAtualizado));

        cartItems.innerHTML = cartAtualizado.map(function (item, index) {
            const preco = getPrecoItem(item);
            const qtde = parseInt(item.qtde) || 1;
            const itemTotal = preco * qtde;
            total += itemTotal;

            let complementosHtml = '';
            if (item.complementos && item.complementos.length > 0) {
                complementosHtml = '<div class="complementos">' + item.complementos.join('<br>') + '</div>';
            }
            if (item.observacao) {
                complementosHtml += '<div class="complementos">Obs: ' + item.observacao + '</div>';
            }

            return '<div class="cart-item" data-index="' + index + '">' +
                '<div class="cart-item-info">' +
                '<h4>' + item.nome + '</h4>' +
                complementosHtml +
                '</div>' +
                '<div class="cart-item-actions">' +
                '<div class="cart-item-price">' +
                '<div class="qty">' + qtde + 'x ' + formatMoney(preco) + '</div>' +
                '<div class="price">' + formatMoney(itemTotal) + '</div>' +
                '</div>' +
                '<button type="button" class="btn-remove-item" data-index="' + index + '" aria-label="Remover produto" title="Remover">' +
                CHECKOUT_SVGS.trash +
                '</button>' +
                '</div>' +
                '</div>';
        }).join('');

        cartTotal.textContent = formatMoney(total);
        btnPagar.disabled = false;
    }

    function showStep(step) {
        if (step === 1) {
            panelDados.classList.add('active');
            panelResumo.classList.remove('active');
            step1.classList.add('active');
            step1.classList.remove('done');
            step2.classList.remove('active');
        } else {
            panelDados.classList.remove('active');
            panelResumo.classList.add('active');
            step1.classList.remove('active');
            step1.classList.add('done');
            step2.classList.add('active');
            renderCart();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }

    function validateField(input) {
        const value = input.value.trim();
        const isRequired = input.hasAttribute('required') || input.dataset.required === 'true';

        if (isRequired && !value) {
            input.classList.add('error');
            return false;
        }

        input.classList.remove('error');
        return true;
    }

    function validateForm() {
        const fields = form.querySelectorAll('input[data-required="true"], input[required], select[data-required="true"]');
        let valid = true;

        fields.forEach(function (field) {
            if (!validateField(field)) {
                valid = false;
            }
        });

        const whatsapp = document.getElementById('whatsapp');
        if (whatsapp.value.replace(/\D/g, '').length < 10) {
            whatsapp.classList.add('error');
            valid = false;
        }

        const cep = document.getElementById('cep');
        if (cep.value.replace(/\D/g, '').length !== 8) {
            cep.classList.add('error');
            valid = false;
        }

        const cpf = document.getElementById('cpf');
        if (cpf.value.replace(/\D/g, '').length !== 11) {
            cpf.classList.add('error');
            valid = false;
        }

        return valid;
    }

    function saveCustomerData() {
        const data = {
            nome: document.getElementById('nome').value.trim(),
            cpf: document.getElementById('cpf').value.trim(),
            whatsapp: document.getElementById('whatsapp').value.trim(),
            cep: document.getElementById('cep').value.trim(),
            endereco: document.getElementById('endereco').value.trim(),
            numero: document.getElementById('numero').value.trim(),
            complemento: document.getElementById('complemento').value.trim(),
            bairro: document.getElementById('bairro').value.trim(),
            cidade: document.getElementById('cidade').value.trim(),
            estado: document.getElementById('estado').value.trim()
        };
        localStorage.setItem('bulls_customer', JSON.stringify(data));
        return data;
    }

    function loadCustomerData() {
        try {
            const data = JSON.parse(localStorage.getItem('bulls_customer') || '{}');
            Object.keys(data).forEach(function (key) {
                const el = document.getElementById(key);
                if (el && data[key]) {
                    el.value = data[key];
                }
            });
        } catch (e) {}
    }

    function buscarCep(cep) {
        const cepLimpo = cep.replace(/\D/g, '');
        if (cepLimpo.length !== 8) return;

        fetch('https://viacep.com.br/ws/' + cepLimpo + '/json/')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.erro) return;
                document.getElementById('endereco').value = data.logradouro || '';
                document.getElementById('bairro').value = data.bairro || '';
                document.getElementById('cidade').value = data.localidade || '';
                document.getElementById('estado').value = data.uf || '';
                document.getElementById('numero').focus();
            })
            .catch(function () {});
    }

    function generateOrderNumber() {
        return 'BL' + Date.now().toString().slice(-8);
    }

    function criarModalCarregando() {
        if (document.getElementById('modalCarregando')) return;
        var modal = document.createElement('div');
        modal.id = 'modalCarregando';
        modal.innerHTML =
            '<div class="bulls-spinner"></div>' +
            '<p class="bulls-loading-text">Carregando resumo...</p>';
        document.body.appendChild(modal);
    }

    function mostrarCarregamento(texto) {
        criarModalCarregando();
        var modal = document.getElementById('modalCarregando');
        var textoEl = modal.querySelector('.bulls-loading-text');
        if (textoEl) {
            textoEl.textContent = texto || 'Carregando...';
        }
        modal.style.display = 'flex';
        requestAnimationFrame(function () {
            modal.classList.add('ativo');
        });
    }

    function esconderCarregamento() {
        var modal = document.getElementById('modalCarregando');
        if (!modal) return;
        modal.classList.remove('ativo');
        setTimeout(function () {
            modal.style.display = 'none';
        }, 350);
    }

    function removeFromCart(index) {
        const cart = getCart();
        if (index < 0 || index >= cart.length) return;
        cart.splice(index, 1);
        localStorage.setItem('bulls_cart', JSON.stringify(cart));
        renderCart();
    }

    cartItems.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-remove-item');
        if (!btn) return;
        const index = parseInt(btn.dataset.index);
        if (!isNaN(index)) {
            removeFromCart(index);
        }
    });

    btnContinuar.addEventListener('click', function () {
        if (!validateForm()) return;

        btnContinuar.disabled = true;
        btnContinuar.style.opacity = '0.7';
        mostrarCarregamento();
        saveCustomerData();

        setTimeout(function () {
            showStep(2);
            esconderCarregamento();
            btnContinuar.disabled = false;
            btnContinuar.style.opacity = '1';
        }, 700);
    });

    function exibirPixModal(orderNumero, tx) {
        currentPixCode = tx.copyPaste || '';

        orderNumberEl.textContent = 'Pedido #' + orderNumero;
        pixKeyEl.textContent = currentPixCode;

        if (tx.qrCodeBase64) {
            pixQrCodeEl.src = tx.qrCodeBase64;
            pixQrCodeEl.hidden = false;
        } else if (tx.qrcodeUrl) {
            pixQrCodeEl.src = tx.qrcodeUrl;
            pixQrCodeEl.hidden = false;
        } else {
            pixQrCodeEl.hidden = true;
        }

        btnCopyPix.innerHTML = '<span id="iconCopy"></span> Copiar código PIX';
        document.getElementById('iconCopy').innerHTML = CHECKOUT_SVGS.copy;
        pixModal.classList.add('active');
    }

    btnPagar.addEventListener('click', function () {
        const cart = getCart();
        if (cart.length === 0) return;

        const customer = JSON.parse(localStorage.getItem('bulls_customer') || '{}');
        const cpf = (customer.cpf || '').replace(/\D/g, '');

        if (!customer.nome || cpf.length !== 11) {
            alert('Preencha nome e CPF válidos na etapa anterior.');
            showStep(1);
            return;
        }

        let total = 0;
        cart.forEach(function (item) {
            total += getPrecoItem(item) * (parseInt(item.qtde) || 1);
        });

        const orderNumero = generateOrderNumber();
        const amount = Math.round(total * 100) / 100;

        btnPagar.disabled = true;
        mostrarCarregamento('Gerando PIX...');

        fetch(getTransactionApiUrl(), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                amount: amount,
                payerName: customer.nome,
                payerDocument: cpf,
                transactionId: orderNumero,
                description: 'Pedido #' + orderNumero + ' - Bulls'
            })
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                });
            })
            .then(function (result) {
                esconderCarregamento();
                btnPagar.disabled = false;

                if (!result.ok || !result.data.success) {
                    alert(result.data.message || 'Erro ao gerar PIX. Tente novamente.');
                    return;
                }

                const tx = result.data.data;
                const order = {
                    numero: orderNumero,
                    data: new Date().toISOString(),
                    cliente: customer,
                    itens: cart,
                    total: total,
                    pagamento: 'PIX',
                    entrega: 'Agora (20 a 30 min)',
                    misticpay: {
                        transactionId: tx.transactionId,
                        transactionState: tx.transactionState,
                        copyPaste: tx.copyPaste
                    }
                };

                localStorage.setItem('bulls_last_order', JSON.stringify(order));
                localStorage.removeItem('bulls_cart');
                renderCart();
                exibirPixModal(orderNumero, tx);
            })
            .catch(function () {
                esconderCarregamento();
                btnPagar.disabled = false;
                alert('Erro de conexão ao gerar PIX. Tente novamente.');
            });
    });

    btnCopyPix.addEventListener('click', function () {
        if (!currentPixCode) return;

        navigator.clipboard.writeText(currentPixCode).then(function () {
            btnCopyPix.textContent = 'Copiado!';
            setTimeout(function () {
                btnCopyPix.innerHTML = '<span id="iconCopy"></span> Copiar código PIX';
                document.getElementById('iconCopy').innerHTML = CHECKOUT_SVGS.copy;
            }, 2000);
        });
    });

    document.getElementById('btnCloseModal').addEventListener('click', function () {
        pixModal.classList.remove('active');
        window.location.href = '/index.html';
    });

    document.getElementById('cep').addEventListener('blur', function () {
        buscarCep(this.value);
    });

    document.getElementById('cep').addEventListener('input', function () {
        if (this.value.replace(/\D/g, '').length === 8) {
            buscarCep(this.value);
        }
    });

    form.querySelectorAll('input').forEach(function (input) {
        input.addEventListener('input', function () {
            input.classList.remove('error');
        });
    });

    if (typeof $ !== 'undefined' && $.fn.mask) {
        $('#whatsapp').mask('(00) 00000-0000');
        $('#cpf').mask('000.000.000-00');
        $('#cep').mask('00000-000');
    }

    function injectCheckoutIcons() {
        document.getElementById('btnVoltar').innerHTML = CHECKOUT_SVGS.back + ' Voltar';
        document.getElementById('iconDados').innerHTML = CHECKOUT_SVGS.user;
        document.getElementById('iconResumo').innerHTML = CHECKOUT_SVGS.receipt;
        document.getElementById('iconCart').innerHTML = CHECKOUT_SVGS.cart;
        document.getElementById('iconPayment').innerHTML = CHECKOUT_SVGS.wallet;
        document.getElementById('iconCopy').innerHTML = CHECKOUT_SVGS.copy;
    }

    criarModalCarregando();
    injectCheckoutIcons();
    loadCustomerData();
    renderCart();
    showStep(1);
})();
