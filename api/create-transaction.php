<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

require_once __DIR__ . '/misticpay-config.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'JSON inválido']);
    exit;
}

$amount = isset($input['amount']) ? (float) $input['amount'] : 0;
$payerName = trim($input['payerName'] ?? '');
$payerDocument = preg_replace('/\D/', '', $input['payerDocument'] ?? '');
$transactionId = trim($input['transactionId'] ?? '');
$description = trim($input['description'] ?? '');

if ($amount <= 0 || $payerName === '' || strlen($payerDocument) !== 11 || $transactionId === '' || $description === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos para gerar o PIX']);
    exit;
}

$payload = json_encode([
    'amount' => $amount,
    'payerName' => $payerName,
    'payerDocument' => $payerDocument,
    'transactionId' => $transactionId,
    'description' => $description,
]);

$ch = curl_init('https://api.misticpay.com/api/transactions/create');

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'ci: ' . MISTICPAY_CLIENT_ID,
        'cs: ' . MISTICPAY_CLIENT_SECRET,
        'Content-Type: application/json',
    ],
    CURLOPT_TIMEOUT => 30,
]);

$response = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(502);
    echo json_encode(['success' => false, 'message' => 'Erro ao conectar com o gateway de pagamento']);
    exit;
}

$data = json_decode($response, true);

if ($httpCode < 200 || $httpCode >= 300 || !isset($data['data'])) {
    $status = $httpCode >= 400 ? $httpCode : 502;
    http_response_code($status);
    echo json_encode([
        'success' => false,
        'message' => $data['message'] ?? 'Erro ao criar transação PIX',
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => $data['message'] ?? 'Transação criada com sucesso',
    'data' => $data['data'],
]);
