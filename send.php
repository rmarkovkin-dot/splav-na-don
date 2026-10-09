<?php
/**
 * Приёмник заявок rostur.expert → Telegram
 * Версия: v4 FINAL · 2026-10-09
 *
 * Каналы отправки (по приоритету):
 *   1. Telegram напрямую (Джино → api.telegram.org) — часто не работает из РФ
 *   2. Telegram через реле (Джино → зарубежный сервер → Telegram)
 *   3. E-mail резерв (если оба Telegram-канала недоступны)
 *
 * НЕ КОММИТИТЬ В GITHUB! В репозитории держим только send.example.php
 * с заглушками вместо токена. Сам send.php — добавить в .gitignore.
 */

/* ═══════════════ НАСТРОЙКИ ═══════════════ */
$BOT_TOKEN = '8897925330:AAFbfm3He_rsdU-l2Q3yLFDXZnuC_8YTaNg';
$CHAT_ID   = '464451191';                      // реальный chat_id (проверен)
$RELAY_URL = 'https://lessons.razgovor-s-psihologom.ru/tg-relay'; // реле на зарубежном сервере
$RELAY_KEY = 'Rt2026TgRelay_X9fK2LmQ7ZwR4t';  // секрет, совпадает с relay.py
$EMAIL_TO  = 'mr.plesovskikh@ya.ru';                              // ← сюда впиши свой e-mail для резерва
/* ═════════════════════════════════════════ */

header('Content-Type: application/json; charset=utf-8');

/* ── Универсальный POST-клиент ── */
function http_post($url, $body) {
    $ctx = stream_context_create(['http' => [
        'method'        => 'POST',
        'header'        => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content'       => $body,
        'timeout'       => 12,
        'ignore_errors' => true,
    ]]);
    return @file_get_contents($url, false, $ctx);
}

/* ── Отправка в Telegram с каскадом: direct → relay ── */
function tg_send($text) {
    global $BOT_TOKEN, $CHAT_ID, $RELAY_URL, $RELAY_KEY;
    $body = http_build_query([
        'chat_id'                    => $CHAT_ID,
        'text'                       => $text,
        'parse_mode'                 => 'HTML',
        'disable_web_page_preview'   => 'true',
    ]);
    $direct_url = 'https://api.telegram.org/bot' . $BOT_TOKEN . '/sendMessage';

    // 1) Прямой путь
    $r = http_post($direct_url, $body);
    if ($r !== false && strpos($r, '"ok":true') !== false) {
        return ['ok' => true, 'via' => 'direct', 'raw' => $r];
    }

    // 2) Реле через зарубежный сервер
    if ($RELAY_URL !== '' && $RELAY_KEY !== '') {
        $rb = http_build_query(['key' => $RELAY_KEY, 'chat_id' => $CHAT_ID, 'text' => $text]);
        $rr = http_post($RELAY_URL, $rb);
        if ($rr !== false && strpos($rr, '"ok":true') !== false) {
            return ['ok' => true, 'via' => 'relay', 'raw' => $rr];
        }
    }

    return ['ok' => false, 'via' => 'none', 'raw' => (string) $r];
}

/* ── Почтовый резерв ── */
function mail_fallback($subject, $plain) {
    global $EMAIL_TO;
    if ($EMAIL_TO === '') return false;
    $h  = "MIME-Version: 1.0\r\n";
    $h .= "Content-Type: text/plain; charset=utf-8\r\n";
    $h .= 'From: =?utf-8?B?' . base64_encode('РосТур Эксперт') . "?= <no-reply@rostur.expert>\r\n";
    return @mail($EMAIL_TO, '=?utf-8?B?' . base64_encode($subject) . '?=', $plain, $h);
}

/* ═══════════════ ДИАГНОСТИКА: /send.php?test=1 ═══════════════ */
if (isset($_GET['test'])) {
    $test_msg = "🛠 Тест send.php · " . date('d.m.Y H:i:s') . " · IP: " .
                ($_SERVER['REMOTE_ADDR'] ?? '?');
    $tg   = tg_send($test_msg);
    $mail = false;
    if (!$tg['ok']) {
        $mail = mail_fallback('Тест send.php', $test_msg);
    }
    header('Content-Type: text/plain; charset=utf-8');
    echo "TG:   " . json_encode($tg, JSON_UNESCAPED_UNICODE) . "\n";
    echo "MAIL: " . ($mail ? 'отправлено на ' . $EMAIL_TO : ($EMAIL_TO === '' ? 'не настроен' : 'ошибка')) . "\n";
    exit;
}

/* ═══════════════ БОЕВОЙ ПРИЁМ ЗАЯВОК ═══════════════ */

// Только POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

// Referer-защита: принимаем заявки только с нашего домена
$ref = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
if ($ref !== '' && strpos($ref, 'https://rostur.expert') !== 0) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'bad_referer']);
    exit;
}

// Собираем поля в красивую карточку для Telegram + plain-версию для почты
$labels = [
    'name'    => '👤 Имя',
    'phone'   => '📞 Телефон',
    'comment' => '💬 Комментарий',
    'dates'   => '📅 Даты',
    'rafts'   => '🚢 Плотов',
    'people'  => '👥 Человек',
    'days'    => '📆 Суток',
    'zone'    => '🏕 Зона',
    'line'    => '🌊 Линия',
    'format'  => '🎯 Формат',
    'route'   => '🧭 Маршрут',
    'source'  => '🔗 Страница',
    'pole1'   => '👤 Имя',
    'pole2'   => '📞 Телефон',
    'pole3'   => '💬 Комментарий',
    'pole4'   => '📅 Даты',
];
$lines = ['🚢 <b>Новая заявка с сайта!</b>'];
$plain = ['НОВАЯ ЗАЯВКА С САЙТА rostur.expert', ''];

foreach ($_POST as $k => $v) {
    $v = trim(strip_tags((string) $v));
    if ($v === '') continue;
    $v = mb_substr($v, 0, 300);
    $label = isset($labels[$k]) ? $labels[$k] : ('• ' . htmlspecialchars($k));
    $lines[] = $label . ': ' . htmlspecialchars($v);
    $plain[] = (isset($labels[$k]) ? $labels[$k] : $k) . ': ' . $v;
}

// Пустая заявка — отбиваем
if (count($lines) === 1) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'empty_form']);
    exit;
}

$ts = '⏰ ' . date('d.m.Y H:i');
$lines[] = $ts;
$plain[] = $ts;

$tg   = tg_send(implode("\n", $lines));
$mail = false;
if (!$tg['ok']) {
    $mail = mail_fallback('Заявка с rostur.expert', implode("\n", $plain));
}

echo json_encode([
    'ok'   => ($tg['ok'] || (bool) $mail),
    'via'  => $tg['ok'] ? $tg['via'] : ($mail ? 'email' : 'none'),
], JSON_UNESCAPED_UNICODE);