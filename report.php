<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://yourdomain.com');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

// Load configuration securely
if (!file_exists(__DIR__ . '/telegram-config.php')) {
    error_log('MISSING: telegram-config.php');
    http_response_code(500);
    die(json_encode(['error' => 'Server misconfiguration']));
}

require_once __DIR__ . '/telegram-config.php';

// Decode request payload
$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data) || ($data['type'] ?? '') !== 'visit') {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid payload']));
}

// PRIORITY: Visit notification only
$message =
    "<b>🚀 New Visit</b>\n\n" .
    "🌐 <b>IP:</b> " . htmlspecialchars((string) ($data['ip'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8') . "\n" .
    "📍 <b>Location:</b> " .
    htmlspecialchars((string) ($data['city'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8') . ", " .
    htmlspecialchars((string) ($data['country'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8') . "\n" .
    "🕒 <b>Time:</b> " .
    htmlspecialchars((string) ($data['time'] ?? date('Y-m-d H:i:s')), ENT_QUOTES, 'UTF-8') . "\n\n" .
    "🖥️ <b>OS:</b> " . htmlspecialchars((string) ($data['os'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8') . "\n" .
    "💻 <b>PC Type:</b> " . htmlspecialchars((string) ($data['pcType'] ?? 'Desktop'), ENT_QUOTES, 'UTF-8') . "\n" .
    "🌍 <b>Browser:</b> " . htmlspecialchars((string) ($data['browser'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8') . "\n\n" .
    "🌐 <b>Website:</b> " . htmlspecialchars((string) ($data['url'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8');

// Send visit notification to Telegram
$ch = curl_init(
    "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage"
);

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'chat_id' => TELEGRAM_CHAT_ID,
        'text' => $message,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($response === false) {
    error_log('Telegram connection error: ' . curl_error($ch));
}

curl_close($ch);

if ($http_code === 200 && $response !== false) {
    echo json_encode(['success' => true]);
} else {
    error_log("Telegram error ($http_code): " . substr((string) $response, 0, 500));
    http_response_code(502);
    echo json_encode(['error' => 'Notification failed']);
}
?>