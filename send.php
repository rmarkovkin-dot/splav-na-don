<?php
// Rate-limiting: 5 заявок с IP за 60 секунд
function rate_limit_check($ip) {
  $file = sys_get_temp_dir() . "/rate_limit_" . md5($ip) . ".json";
  $now = time();
  $window = 60;
  $max = 5;
  if (!file_exists($file)) {
    file_put_contents($file, json_encode([$now]));
    return true;
  }
  $requests = json_decode(file_get_contents($file), true);
  $requests = array_filter($requests, function($t) use ($now, $window) {
    return ($now - $t) < $window;
  });
  if (count($requests) >= $max) return false;
  $requests[] = $now;
  file_put_contents($file, json_encode($requests));
  return true;
}
$client_ip = $_SERVER["HTTP_X_FORWARDED_FOR"] ?? $_SERVER["REMOTE_ADDR"];
$client_ip = explode(",", $client_ip)[0];
if (!rate_limit_check($client_ip)) {
  http_response_code(429);
  echo json_encode(["error" => "Too many requests. Try again in 1 minute."]);
  exit;
}
// Honeypot check
if (!empty($_POST["_gotcha"])) {
  http_response_code(400);
  echo json_encode(["error" => "Bot detected"]);
  exit;
}
/**
* Приёмник заявок rostur.expert → Telegram + Email
* Версия: v7.1 (JSON + Form support) · 2026-10-12
*
* Принимает:
* - application/json (Dify AI агент)
* - x-www-form-urlencoded (обычные формы сайта)
*/

/* ═══════════════ ЗАГРУЗКА СЕКРЕТОВ ═══════════════ */
$secrets_file = '/home/users/j/j24080170/secrets/rostur-secrets.php';
if (!file_exists($secrets_file)) {
    http_response_code(500);
    echo json_encode(['error' => 'Secrets file missing']);
    exit;
}
require_once $secrets_file;

$BOT_TOKEN = getenv('TG_BOT_TOKEN');
$CHAT_ID   = getenv('TG_CHAT_ID');
$RELAY_URL = getenv('RELAY_URL');
$RELAY_KEY = getenv('RELAY_KEY');
$EMAIL_TO  = getenv('EMAIL_TO');

if (!$BOT_TOKEN || !$CHAT_ID) {
    http_response_code(500);
    echo json_encode(['error' => 'Missing secrets in env']);
    exit;
}
/* ═══════════════════════════════════════════════════ */

header('Content-Type: application/json; charset=utf-8');

/* ── cURL POST-клиент ── */
function http_post($url, $body, $is_json = false) {
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        if ($is_json) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
            ]);
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded',
            ]);
        }
        
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
    
    // Fallback
    $headers = $is_json 
        ? "Content-Type: application/json\r\nAccept: application/json\r\n" 
        : "Content-Type: application/x-www-form-urlencoded\r\n";
    
    $ctx = stream_context_create([
        'http' => [
            'method'        => 'POST',
            'header'        => $headers,
            'content'       => $body,
            'timeout'       => 12,
            'ignore_errors' => true,
        ]
    ]);
    
    return @file_get_contents($url, false, $ctx);
}

/* ── Отправка в Telegram (relay → direct) ── */
function tg_send($text) {
    global $BOT_TOKEN, $CHAT_ID, $RELAY_URL, $RELAY_KEY;
    
    $body = http_build_query([
        'chat_id'                  => $CHAT_ID,
        'text'                     => $text,
        'parse_mode'               => 'HTML',
        'disable_web_page_preview' => 'true',
    ]);
    
    // 1) Relay
    if ($RELAY_URL !== '' && $RELAY_KEY !== '') {
        $rb = http_build_query([
            'key'     => $RELAY_KEY,
            'chat_id' => $CHAT_ID,
            'text'    => $text
        ]);
        $rr = http_post($RELAY_URL, $rb);
        if ($rr !== false && strpos($rr, '"ok":true') !== false) {
            return ['ok' => true, 'via' => 'relay', 'raw' => $rr];
        }
    }
    
    // 2) Direct API
    $direct_url = 'https://api.telegram.org/bot' . $BOT_TOKEN . '/sendMessage';
    $r = http_post($direct_url, $body);
    if ($r !== false && strpos($r, '"ok":true') !== false) {
        return ['ok' => true, 'via' => 'direct', 'raw' => $r];
    }
    
    return ['ok' => false, 'via' => 'none', 'raw' => $r];
}

/* ── Почтовый резерв ─ */
function mail_fallback($subject, $plain) {
    global $EMAIL_TO;
    if ($EMAIL_TO === '') return false;
    
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
    $headers .= 'From: =?utf-8?B?' . base64_encode('РосТур Эксперт') . "?= <no-reply@rostur.expert>\r\n";
    
    return @mail($EMAIL_TO, '=?utf-8?B?' . base64_encode($subject) . '?=', $plain, $headers);
}

/* ══════════════ ДИАГНОСТИКА ═══════════════ */
if (isset($_GET['test'])) {
    $test_msg = "🛠 Тест send.php v7.1 · " . date('d.m.Y H:i:s');
    $tg = tg_send($test_msg);
    $mail = !$tg['ok'] ? mail_fallback('Тест send.php', $test_msg) : false;
    
    header('Content-Type: text/plain; charset=utf-8');
    echo "TG:   " . json_encode($tg, JSON_UNESCAPED_UNICODE) . "\n";
    echo "MAIL: " . ($mail ? 'ОТПРАВЛЕНО на ' . $EMAIL_TO : 'не отправлено') . "\n";
    exit;
}

/* ═══════════════ БОЕВОЙ РЕЖИМ ═══════════════ */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

// Referer-проверка
$ref = $_SERVER['HTTP_REFERER'] ?? '';
if ($ref !== '' && strpos($ref, 'https://rostur.expert') !== 0 && strpos($ref, 'https://difyretail.ru') !== 0) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'bad_referer']);
    exit;
}

// ═══════════════ КЛЮЧЕВОЕ: принимаем И JSON, И form-urlencoded ══════════════

$content_type = $_SERVER['CONTENT_TYPE'] ?? '';
$post_data = [];

if (stripos($content_type, 'application/json') !== false) {
    // Dify шлёт JSON
    $raw_input = file_get_contents('php://input');
    $post_data = json_decode($raw_input, true) ?: [];
} else {
    // Обычные формы
    $post_data = $_POST;
}

// ═══════════════ Сбор заявки ══════════════

$labels = [
    'name'    => '👤 Имя',
    'phone'   => ' Телефон',
    'comment' => '💬 Комментарий',
    'dates'   => '📅 Даты',
    'rafts'   => '🚢 Плотов',
    'people'  => ' Человек',
    'days'    => ' Суток',
    'zone'    => ' Зона',
    'line'    => '🌊 Линия',
    'format'  => '🎯 Формат',
    'route'   => ' Маршрут',
    'source'  => ' Страница',
    'pole1'   => '👤 Имя',
    'pole2'   => '📞 Телефон',
    'pole3'   => '💬 Комментарий',
    'pole4'   => ' Даты',
];

$lines = [' <b>Новая заявка с сайта!</b>'];
$plain = ['НОВАЯ ЗАЯВКА С САЙТА rostur.expert', ''];

foreach ($post_data as $k => $v) {
    $v = trim(strip_tags((string) $v));
    if ($v === '') continue;
    $v = mb_substr($v, 0, 300);
    $label = $labels[$k] ?? ('• ' . htmlspecialchars($k));
    $lines[] = $label . ': ' . htmlspecialchars($v);
    $plain[] = ($labels[$k] ?? $k) . ': ' . $v;
}

if (count($lines) === 1) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'empty_form']);
    exit;
}

$lines[] = '⏰ ' . date('d.m.Y H:i');
$plain[] = date('d.m.Y H:i');

$tg = tg_send(implode("\n", $lines));
$mail = !$tg['ok'] ? mail_fallback('Заявка с rostur.expert', implode("\n", $plain)) : false;

echo json_encode([
    'ok'  => ($tg['ok'] || (bool) $mail),
    'via' => $tg['ok'] ? $tg['via'] : ($mail ? 'email' : 'none'),
], JSON_UNESCAPED_UNICODE);