<?php
// Тест send.php с полной формой
$url = 'https://rostur.expert/send.php';
$data = [
    'name'    => 'Тестовый Иван',
    'phone'   => '+79991234567',
    'comment' => 'Тестовая заявка от Ани (Dify AI)',
    'source'  => 'ai-consultant',
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded',
    'Referer: https://rostur.expert/',
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";