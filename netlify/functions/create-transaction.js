const MISTICPAY_URL = 'https://api.misticpay.com/api/transactions/create';

const headers = {
    'Content-Type': 'application/json; charset=utf-8',
    'Access-Control-Allow-Origin': '*',
    'Access-Control-Allow-Methods': 'POST, OPTIONS',
    'Access-Control-Allow-Headers': 'Content-Type',
};

function jsonResponse(statusCode, body) {
    return {
        statusCode,
        headers,
        body: JSON.stringify(body),
    };
}

exports.handler = async function (event) {
    if (event.httpMethod === 'OPTIONS') {
        return { statusCode: 204, headers, body: '' };
    }

    if (event.httpMethod !== 'POST') {
        return jsonResponse(405, { success: false, message: 'Método não permitido' });
    }

    const clientId = process.env.MISTICPAY_CLIENT_ID || 'ci_2k6zxx6h33hio1m';
    const clientSecret = process.env.MISTICPAY_CLIENT_SECRET || 'cs_5fqt6z7ot8uhzx8vrckddxc4j';

    let input;
    try {
        input = JSON.parse(event.body || '{}');
    } catch (e) {
        return jsonResponse(400, { success: false, message: 'JSON inválido' });
    }

    const amount = parseFloat(input.amount) || 0;
    const payerName = String(input.payerName || '').trim();
    const payerDocument = String(input.payerDocument || '').replace(/\D/g, '');
    const transactionId = String(input.transactionId || '').trim();
    const description = String(input.description || '').trim();

    if (
        amount <= 0 ||
        !payerName ||
        payerDocument.length !== 11 ||
        !transactionId ||
        !description
    ) {
        return jsonResponse(400, {
            success: false,
            message: 'Dados inválidos para gerar o PIX',
        });
    }

    try {
        const response = await fetch(MISTICPAY_URL, {
            method: 'POST',
            headers: {
                ci: clientId,
                cs: clientSecret,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                amount,
                payerName,
                payerDocument,
                transactionId,
                description,
            }),
        });

        const data = await response.json().catch(function () {
            return {};
        });

        if (!response.ok || !data.data) {
            return jsonResponse(response.ok ? 502 : response.status, {
                success: false,
                message: data.message || 'Erro ao criar transação PIX',
            });
        }

        return jsonResponse(200, {
            success: true,
            message: data.message || 'Transação criada com sucesso',
            data: data.data,
        });
    } catch (e) {
        return jsonResponse(502, {
            success: false,
            message: 'Erro ao conectar com o gateway de pagamento',
        });
    }
};
