<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/monitor.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    monitor_json_response(['ok' => false, 'error' => 'รองรับเฉพาะคำขอ POST'], 405);
}

$contentType = isset($_SERVER['CONTENT_TYPE']) ? strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'])[0])) : '';
if ($contentType !== 'application/json') {
    monitor_json_response(['ok' => false, 'error' => 'รูปแบบคำขอต้องเป็น JSON'], 415);
}

try {
    $body = file_get_contents('php://input');
    $settings = $body === false ? null : json_decode($body, true);
    if (!is_array($settings)) {
        throw new InvalidArgumentException('ข้อมูลการตั้งค่าไม่ถูกต้อง');
    }

    $config = monitor_save_config($settings);
    monitor_json_response(['ok' => true, 'config' => $config, 'message' => 'บันทึกการตั้งค่าเรียบร้อยแล้ว']);
} catch (InvalidArgumentException $exception) {
    monitor_json_response(['ok' => false, 'error' => $exception->getMessage()], 400);
} catch (Throwable $exception) {
    monitor_log('save_config.php: ' . $exception->getMessage());
    monitor_json_response(['ok' => false, 'error' => 'ไม่สามารถบันทึกการตั้งค่าได้: ' . $exception->getMessage()], 500);
}
