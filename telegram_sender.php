<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Включаем вывод ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Определяем тип контента
$contentType = $_SERVER["CONTENT_TYPE"] ?? '';
$isJson = strpos($contentType, 'application/json') !== false;
$isMultipart = strpos($contentType, 'multipart/form-data') !== false;

// Получаем данные в зависимости от типа
if ($isJson) {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    $type = $data['type'] ?? 'text';
    $message = $data['message'] ?? '';
    $photo = null;
} else {
    $type = $_POST['type'] ?? 'text';
    $message = $_POST['message'] ?? '';
    $photo = $_FILES['photo'] ?? null;
}

// Данные бота и канала
$botToken = '8574660284:AAELLf0OP_KUPJ9qjGJBq0ggFvgo-c4DEec'; // ⚠️ ЗАМЕНИТЕ на реальный токен!
$channel = '@POGklounPOG';

try {
    if ($type === 'photo' && $photo && $photo['error'] === UPLOAD_ERR_OK) {
        // Отправка фото
        sendPhoto($botToken, $channel, $photo, $message);
    } else if ($type === 'text' && !empty(trim($message))) {
        // Отправка текста
        sendText($botToken, $channel, $message);
    } else {
        throw new Exception('Неверные данные: сообщение пустое или фото не загружено');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

function sendText($botToken, $channel, $message) {
    $apiUrl = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $postData = [
        'chat_id' => $channel,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 10
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($httpCode === 200) {
        echo json_encode(['success' => true, 'message' => 'Текст отправлен!']);
    } else {
        $errorInfo = json_decode($response, true);
        $errorMessage = $errorInfo['description'] ?? $curlError ?? 'Unknown error';
        throw new Exception($errorMessage);
    }
}

function sendPhoto($botToken, $channel, $photo, $caption = '') {
    $apiUrl = "https://api.telegram.org/bot{$botToken}/sendPhoto";

    // Подготавливаем файл для отправки
    $photoPath = $photo['tmp_name'];
    $photoName = $photo['name'];

    $postData = [
        'chat_id' => $channel,
        'caption' => $caption,
        'photo' => new CURLFile($photoPath, mime_content_type($photoPath), $photoName)
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30 // Увеличиваем таймаут для загрузки фото
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($httpCode === 200) {
        echo json_encode(['success' => true, 'message' => 'Фото отправлено!']);
    } else {
        $errorInfo = json_decode($response, true);
        $errorMessage = $errorInfo['description'] ?? $curlError ?? 'Unknown error';
        throw new Exception($errorMessage);
    }
}
?>