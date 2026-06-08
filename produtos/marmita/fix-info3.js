const fs = require('fs');
const path = require('path');
const dir = __dirname;

const precos = {
    'churrascopequena.html': ['R$ 23,90', '23.90'],
    'churrascogrande.html': ['R$ 28,90', '28.90'],
    'kit3.html': ['R$ 39,90', '39.90'],
    'milanesa.html': ['R$ 23,90', '23.90'],
    'strogonof.html': ['R$ 25,90', '25.90'],
    'costela.html': ['R$ 29,90', '29.90'],
    'parmegiana.html': ['R$ 25,90', '25.90'],
    'peixe.html': ['R$ 26,90', '26.90'],
    'feijoada.html': ['R$ 29,90', '29.90']
};

for (const [file, [fmt, val]] of Object.entries(precos)) {
    const fp = path.join(dir, file);
    let c = fs.readFileSync(fp, 'utf8');
    const info3 = `                    <div class="info3">
                        <input id="idProduto" type="hidden" name="idProduto" value="12111">
                        <input type="hidden" id="precoBase" value="${val}">
                        <div class="qtdeProduto" style="display: none;">
                            <button class="removerQtde" aria-label="Remover"><i class="fa-solid fa-minus"></i></button>
                            <input class="qtde" type="text" value="1" min="1" max="86" size="3" disabled="">
                            <button class="adicionarQtde" aria-label="Adicionar"><i class="fa-solid fa-plus"></i></button>
                        </div>
                        <span id="precoProduto">${fmt}</span>
                        <span class="precoProduto" style="display:none">${val}</span>
                        <button class="btn" onclick="finalizar()">FINALIZAR PEDIDO</button>
                    </div>`;
    c = c.replace(/<div class="info3">[\s\S]*?<button class="btn" onclick="finalizar\(\)">FINALIZAR PEDIDO<\/button>\s*<\/div>/, info3);
    c = c.replace(/R\$ 0,00/g, '');
    fs.writeFileSync(fp, c, 'utf8');
    console.log('OK:', file);
}
