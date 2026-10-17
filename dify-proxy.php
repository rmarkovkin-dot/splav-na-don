<?php
/**
 * Proxy для Dify API + логирование диалогов
 * v3.1 · 2026
 * Логирует все диалоги в /chat-logs/ и отправляет на почту
 */

header('Content-Type: application/json; charset=utf-8');
// CORS whitelist: только разрешённые домены
$allowed_origins = ['https://rostur.expert', 'https://www.rostur.expert'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}
// ═══════════════ RATE-LIMIT (60 req/min per IP) ═══════════════
$RATE_LIMIT_DIR = sys_get_temp_dir() . '/dify-proxy-rl';
if (!is_dir($RATE_LIMIT_DIR)) {
    @mkdir($RATE_LIMIT_DIR, 0755, true);
}
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rate_file = $RATE_LIMIT_DIR . '/' . md5($client_ip) . '.json';
$now = time();
$window = 60; // 1 минута
$max_requests = 60;

$rate_data = ['requests' => [], 'blocked_until' => 0];
if (file_exists($rate_file)) {
    $rate_data = json_decode(file_get_contents($rate_file), true) ?: $rate_data;
}

// Проверка блокировки
if ($rate_data['blocked_until'] > $now) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded', 'retry_after' => $rate_data['blocked_until'] - $now]);
    exit;
}

// Очистка старых запросов (старше окна)
$rate_data['requests'] = array_filter($rate_data['requests'], function($ts) use ($now, $window) {
    return $ts > ($now - $window);
});

// Добавляем текущий запрос
$rate_data['requests'][] = $now;

// Проверка лимита
if (count($rate_data['requests']) > $max_requests) {
    $rate_data['blocked_until'] = $now + $window;
    file_put_contents($rate_file, json_encode($rate_data));
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded', 'retry_after' => $window]);
    exit;
}

// Сохраняем данные
file_put_contents($rate_file, json_encode($rate_data));

// ═══════════════ ЗАГРУЗКА СЕКРЕТОВ ═══════════════
$secrets_file = '/home/users/j/j24080170/secrets/rostur-secrets.php';
if (!file_exists($secrets_file)) {
    http_response_code(500);
    echo json_encode(['error' => 'Secrets file missing']);
    exit;
}
require_once $secrets_file;

$DIFY_BASE_URL = getenv('DIFY_BASE_URL');
$DIFY_API_KEY  = getenv('DIFY_API_KEY');
$EMAIL_TO      = getenv('EMAIL_TO');
$LOGS_DIR      = __DIR__ . '/chat-logs';

if (!$DIFY_API_KEY) {
    http_response_code(500);
    echo json_encode(['error' => 'Missing DIFY_API_KEY in env']);
    exit;
} // ← Твоя почта для логов

// Создаём папку для логов
if (!is_dir($LOGS_DIR)) {
    @mkdir($LOGS_DIR, 0755, true);
}

// ═══════════════ ВХОДНЫЕ ДАННЫЕ ═══════════════
$input = file_get_contents('php://input');
$data  = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON', 'details' => json_last_error_msg()]);
    exit;
}

// Логируем запрос
$conversationId = isset($data['conversation_id']) ? $data['conversation_id'] : 'new_' . uniqid();
$userMessage    = isset($data['query']) ? $data['query'] : '';
$userId         = isset($data['user']) ? $data['user'] : 'unknown';
$timestamp      = date('Y-m-d H:i:s');
$ip             = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$referer        = $_SERVER['HTTP_REFERER'] ?? '';

// Endpoint
$endpoint = isset($data['endpoint']) ? $data['endpoint'] : 'chat-messages';
unset($data['endpoint']);

// Нормализация inputs
if (!isset($data['inputs']) || !is_array($data['inputs']) || empty($data['inputs'])) {
    $data['inputs'] = new stdClass();
}

// Пустой conversation_id удаляем
if (isset($data['conversation_id']) && empty($data['conversation_id'])) {
    unset($data['conversation_id']);
}

// ═══════════════ ЗАПРОС К DIFY ═══════════════
$url = $DIFY_BASE_URL . '/' . $endpoint;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $DIFY_API_KEY,
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 120);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error    = curl_error($ch);
curl_close($ch);

if ($error) {
    http_response_code(502);
    echo json_encode(['error' => 'Dify upstream error', 'message' => $error]);
    exit;
}

// ═══════════════ ЛОГИРОВАНИЕ ОТВЕТА ═══════════════
$botAnswer = '';
$parsedResponse = null;

if ($httpCode === 200) {
    // Парсим SSE-поток, чтобы извлечь ответ бота
    $lines = explode("\n", $response);
    foreach ($lines as $line) {
        if (strpos($line, 'data: ') === 0) {
            $jsonStr = trim(substr($line, 6));
            if ($jsonStr === '[DONE]' || empty($jsonStr)) continue;
            $event = json_decode($jsonStr, true);
            if ($event && isset($event['event']) && $event['event'] === 'message') {
                if (isset($event['answer'])) {
                    $botAnswer .= $event['answer'];
                }
                if (isset($event['conversation_id'])) {
                    $conversationId = $event['conversation_id'];
                }
            }
        }
    }
}

// Записываем лог
$logEntry = [
    'timestamp'       => $timestamp,
    'conversation_id' => $conversationId,
    'user_id'         => $userId,
    'ip'              => $ip,
    'referer'         => $referer,
    'user_message'    => $userMessage,
    'bot_answer'      => $botAnswer,
    'http_code'       => $httpCode,
];

$logFile = $LOGS_DIR . '/dialog_' . date('Y-m-d') . '.jsonl';
@file_put_contents($logFile, json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);

// Возвращаем ответ Dify
http_response_code($httpCode);
echo $response;