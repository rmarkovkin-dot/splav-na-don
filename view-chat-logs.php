<?php
/**
 * Просмотр логов диалогов Ани · v2
 * Все даты одним списком, включая архив отправленных на почту дней
 */
$ACCESS_KEY = 'rostur2026';
$LOGS = __DIR__ . '/chat-logs';
$ARCH = $LOGS . '/archive';
header('Content-Type: text/html; charset=utf-8');
if (!isset($_GET['key']) || $_GET['key'] !== $ACCESS_KEY) { http_response_code(403); exit('Доступ запрещён'); }

function files_for_date($dir, $date) {
    $out = [];
    if (!is_dir($dir)) return $out;
    foreach (scandir($dir) as $f) {
        if (preg_match('/^dialog_' . $date . '(_[\d-]+)?\.jsonl$/', $f)) $out[] = $dir . '/' . $f;
    }
    return $out;
}
function all_dates($dirs) {
    $dates = [];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) continue;
        foreach (scandir($dir) as $f) {
            if (preg_match('/^dialog_(\d{4}-\d{2}-\d{2})/', $f, $m)) $dates[$m[1]] = 1;
        }
    }
    $dates = array_keys($dates); rsort($dates); return $dates;
}

$dates = all_dates([$LOGS, $ARCH]);
$date = isset($_GET['date']) ? $_GET['date'] : (count($dates) ? $dates[0] : date('Y-m-d'));

echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Логи Ани</title><style>body{font-family:Arial,sans-serif;margin:2rem;background:#f8fafc}.dialog{border:1px solid #ddd;border-radius:8px;padding:15px;margin-bottom:20px;background:#fff}.msg{margin:8px 0;padding:10px;border-radius:6px}.user{background:#dbeafe;border-left:4px solid #2563eb}.bot{background:#d1fae5;border-left:4px solid #16a34a}.meta{color:#666;font-size:12px}h1{font-size:1.3rem}.dates a{display:inline-block;margin:0 6px 8px 0;padding:6px 12px;background:#1e40af;color:#fff;border-radius:6px;text-decoration:none}.dates a.cur{background:#f59e0b}</style></head><body>';
echo '<h1>📊 Логи диалогов Ани</h1><p class="dates">';
foreach ($dates as $d) {
    $cur = ($d === $date) ? ' class="cur"' : '';
    echo '<a' . $cur . ' href="?key=' . htmlspecialchars($ACCESS_KEY) . '&date=' . $d . '">' . $d . '</a>';
}
echo '</p>';

$files = array_merge(files_for_date($LOGS, $date), files_for_date($ARCH, $date));
if (!$files) { echo '<p>Логов за эту дату нет.</p></body></html>'; exit; }

$entries = [];
foreach ($files as $f) {
    foreach (file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $e = json_decode($line, true);
        if ($e) $entries[] = $e;
    }
}
usort($entries, function ($a, $b) { return strcmp($a['timestamp'] ?? '', $b['timestamp'] ?? ''); });

$dialogs = [];
foreach ($entries as $e) { $dialogs[$e['conversation_id'] ?? 'unknown'][] = $e; }
echo '<p><b>Дата: ' . htmlspecialchars($date) . '</b> · диалогов: ' . count($dialogs) . ' · сообщений: ' . count($entries) . '</p>';

foreach ($dialogs as $cid => $msgs) {
    echo '<div class="dialog"><h3>🗨️ ' . htmlspecialchars($cid) . '</h3>';
    echo '<p class="meta">IP: ' . htmlspecialchars($msgs[0]['ip'] ?? '—') . ' · источник: ' . htmlspecialchars($msgs[0]['referer'] ?? '—') . '</p>';
    foreach ($msgs as $m) {
        if (!empty($m['user_message'])) echo '<div class="msg user"><div class="meta">' . htmlspecialchars($m['timestamp'] ?? '') . '</div><b>👤 Гость:</b> ' . nl2br(htmlspecialchars($m['user_message'])) . '</div>';
        if (!empty($m['bot_answer']))   echo '<div class="msg bot"><div class="meta">' . htmlspecialchars($m['timestamp'] ?? '') . '</div><b>🤖 Аня:</b> ' . nl2br(htmlspecialchars($m['bot_answer'])) . '</div>';
    }
    echo '</div>';
}
echo '</body></html>';