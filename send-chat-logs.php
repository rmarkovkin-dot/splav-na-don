<?php
/**
 * Отправка логов диалогов на почту
 * v1.0 · 2026
 * 
 * ЗАПУСК:
 *   Вручную:   https://rostur.expert/send-chat-logs.php?key=rostur2026
 *   По cron:   php /path/to/send-chat-logs.php --key=rostur2026
 */

$ACCESS_KEY = 'rostur2026';
$EMAIL_TO   = 'mr.plesovskikh@ya.ru';
$LOGS_DIR   = __DIR__ . '/chat-logs';
$ARCHIVE_DIR = __DIR__ . '/chat-logs/archive';

// Проверка ключа (для веб-запуска)
if (php_sapi_name() !== 'cli') {
    if (!isset($_GET['key']) || $_GET['key'] !== $ACCESS_KEY) {
        http_response_code(403);
        exit('Доступ запрещён');
    }
    header('Content-Type: text/html; charset=utf-8');
} else {
    // CLI: проверяем аргумент --key
    $args = getopt('', ['key:']);
    if (!isset($args['key']) || $args['key'] !== $ACCESS_KEY) {
        exit("Ошибка: нужен параметр --key=rostur2026\n");
    }
}

// Создаём архивную папку
if (!is_dir($ARCHIVE_DIR)) {
    @mkdir($ARCHIVE_DIR, 0755, true);
}

// Собираем все логи за сегодня
$today = date('Y-m-d');
$logFile = $LOGS_DIR . '/dialog_' . $today . '.jsonl';

if (!file_exists($logFile)) {
    $msg = "Логов за сегодня ($today) нет.";
    if (php_sapi_name() === 'cli') {
        echo $msg . "\n";
    } else {
        echo "<p>$msg</p>";
    }
    exit;
}

// Читаем логи
$lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if (empty($lines)) {
    $msg = "Файл логов пуст.";
    if (php_sapi_name() === 'cli') {
        echo $msg . "\n";
    } else {
        echo "<p>$msg</p>";
    }
    exit;
}

// Парсим и группируем по conversation_id
$dialogs = [];
foreach ($lines as $line) {
    $entry = json_decode($line, true);
    if (!$entry) continue;
    
    $convId = $entry['conversation_id'] ?? 'unknown';
    if (!isset($dialogs[$convId])) {
        $dialogs[$convId] = [];
    }
    $dialogs[$convId][] = $entry;
}

// Формируем HTML-письмо
$html = "<h2>📊 Логи диалогов за $today</h2>";
$html .= "<p><strong>Всего диалогов:</strong> " . count($dialogs) . "</p>";
$html .= "<p><strong>Всего сообщений:</strong> " . count($lines) . "</p>";
$html .= "<hr>";

foreach ($dialogs as $convId => $messages) {
    $html .= "<div style='margin-bottom: 30px; padding: 15px; border: 1px solid #ddd; border-radius: 8px;'>";
    $html .= "<h3 style='margin-top: 0;'>🗨️ Диалог: <code>" . htmlspecialchars($convId) . "</code></h3>";
    $html .= "<p><strong>IP:</strong> " . htmlspecialchars($messages[0]['ip'] ?? 'N/A') . "</p>";
    $html .= "<p><strong>Источник:</strong> " . htmlspecialchars($messages[0]['referer'] ?? 'N/A') . "</p>";
    $html .= "<hr style='margin: 10px 0;'>";
    
    foreach ($messages as $msg) {
        $time = $msg['timestamp'] ?? '';
        $userMsg = $msg['user_message'] ?? '';
        $botAnswer = $msg['bot_answer'] ?? '';
        
        $html .= "<div style='margin-bottom: 15px;'>";
        $html .= "<p style='color: #666; font-size: 12px;'>$time</p>";
        
        if ($userMsg) {
            $html .= "<p><strong style='color: #2563eb;'>👤 Гость:</strong> " . nl2br(htmlspecialchars($userMsg)) . "</p>";
        }
        
        if ($botAnswer) {
            $html .= "<p><strong style='color: #16a34a;'>🤖 Аня:</strong> " . nl2br(htmlspecialchars($botAnswer)) . "</p>";
        }
        
        $html .= "</div>";
    }
    
    $html .= "</div>";
}

// Отправка письма
$subject = "📊 Логи диалогов Ани · $today · " . count($dialogs) . " диалогов";
$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=utf-8\r\n";
$headers .= "From: =?utf-8?B?" . base64_encode('РосТур Эксперт') . "?= <no-reply@rostur.expert>\r\n";

$mailSent = @mail($EMAIL_TO, '=?utf-8?B?' . base64_encode($subject) . '?=', $html, $headers);

// Архивируем отправленный файл
$archiveFile = $ARCHIVE_DIR . '/dialog_' . $today . '_' . date('H-i-s') . '.jsonl';
if (@rename($logFile, $archiveFile)) {
    $status = "Логи отправлены и заархивированы.";
} else {
    $status = "Логи отправлены, но архивация не удалась.";
}

if (php_sapi_name() === 'cli') {
    echo $status . "\n";
    echo "Отправлено: " . count($lines) . " сообщений в " . count($dialogs) . " диалогах\n";
} else {
    echo "<h1>$status</h1>";
    echo "<p>Отправлено на <strong>$EMAIL_TO</strong></p>";
    echo "<p>Всего сообщений: " . count($lines) . "</p>";
    echo "<p>Всего диалогов: " . count($dialogs) . "</p>";
}