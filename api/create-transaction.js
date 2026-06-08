const MISTICPAY_URL = 'https://api.misticpay.com/api/transactions/create';

const corsHeaders = {
    'Access-Control-Allow-Origin': '*',
    'Access-Control-Allow-Methods': 'POST, OPTIONS',
    'Access-Control-Allow-Headers': 'Content-Type',
};

module.exports = async function handler(req, res) {
    Object.entries(corsHeaders).forEach(function ([key, value]) {
        res.setHeader(key, value);
    });

    if (req.method === 'OPTIONS') {
        return res.status(204).end();
    }

    if (req.method !== 'POST') {
        return res.status(405).json({ success: false, message: 'Método não permitido' });
    }

    const clientId = process.env.MISTICPAY_CLIENT_ID || 'ci_2k6zxx6h33hio1m';
    const clientSecret = process.env.MISTICPAY_CLIENT_SECRET || 'cs_5fqt6z7ot8uhzx8vrckddxc4j';

    const input = req.body;

    if (!input || typeof input !== 'object') {
        return res.status(400).json({ success: false, message: 'JSON inválido' });
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
        return res.status(400).json({
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
            return res.status(response.ok ? 502 : response.status).json({
                success: false,
                message: data.message || 'Erro ao criar transação PIX',
            });
        }

        return res.status(200).json({
            success: true,
            message: data.message || 'Transação criada com sucesso',
            data: data.data,
        });
    } catch (e) {
        return res.status(502).json({
            success: false,
            message: 'Erro ao conectar com o gateway de pagamento',
        });
    }
};
