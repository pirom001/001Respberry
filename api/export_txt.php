<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/monitor.php';

try {
    $config = monitor_get_config();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $filename = isset($_GET['download']) && is_string($_GET['download']) ? $_GET['download'] : '';
        if ($filename === '' || basename($filename) !== $filename
            || preg_match('/^monitor_[0-9]{8}_[0-9]{6}\.txt$/D', $filename) !== 1
        ) {
            throw new InvalidArgumentException('ชื่อไฟล์ดาวน์โหลดไม่ถูกต้อง');
        }

        $directory = realpath($config['exportDirectory']);
        $path = $directory === false ? false : realpath($directory . DIRECTORY_SEPARATOR . $filename);
        if ($directory === false || $path === false || strcasecmp(dirname($path), $directory) !== 0 || !is_readable($path)) {
            throw new RuntimeException('ไม่พบไฟล์ส่งออก หรือไม่มีสิทธิ์อ่าน');
        }

        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . (string) filesize($path));
        readfile($path);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: GET, POST');
        monitor_json_response(['ok' => false, 'error' => 'รองรับเฉพาะคำขอ GET หรือ POST'], 405);
    }

    $sourceId = isset($_GET['sourceId']) && is_string($_GET['sourceId'])
        ? $_GET['sourceId']
        : '';
    $source = null;
    foreach ($config['sources'] as $configuredSource) {
        if ($configuredSource['id'] === $sourceId) {
            $source = $configuredSource;
            break;
        }
    }
    if ($source === null) {
        throw new InvalidArgumentException('ไม่พบแหล่งข้อมูลที่เลือกเพื่อส่งออก');
    }

    $csv = monitor_read_history($source, $config['historyDays']);
    $directory = $config['exportDirectory'];
    if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
        throw new RuntimeException('ไม่สามารถสร้างโฟลเดอร์ส่งออกได้');
    }
    if (!is_writable($directory)) {
        throw new RuntimeException('ไม่มีสิทธิ์เขียนโฟลเดอร์ส่งออก');
    }

    $realDirectory = realpath($directory);
    if ($realDirectory === false || !is_dir($realDirectory)) {
        throw new RuntimeException('ไม่สามารถตรวจสอบโฟลเดอร์ส่งออกได้');
    }

    $filename = 'monitor_' . date('Ymd_His') . '.txt';
    $path = $realDirectory . DIRECTORY_SEPARATOR . $filename;
    $output = fopen($path, 'xb');
    if ($output === false) {
        throw new RuntimeException('ไม่สามารถสร้างไฟล์ TXT ได้; ตรวจสอบสิทธิ์หรือรอสักครู่แล้วลองอีกครั้ง');
    }

    $writeSucceeded = fputcsv($output, array_merge(['source_file'], $csv['headers']), "\t", '"', '') !== false;
    foreach ($csv['rows'] as $row) {
        if (!$writeSucceeded
            || fputcsv($output, array_merge([$row['sourceFile']], array_values($row['values'])), "\t", '"', '') === false
        ) {
            $writeSucceeded = false;
            break;
        }
    }
    $closed = fclose($output);
    if (!$writeSucceeded || !$closed) {
        unlink($path);
        throw new RuntimeException('เขียนข้อมูลลงไฟล์ TXT ไม่สำเร็จ');
    }

    monitor_json_response([
        'ok' => true,
        'filename' => $filename,
        'rows' => $csv['rowCount'],
        'files' => count($csv['files']),
        'historyDays' => $config['historyDays'],
        'source' => $source['name'],
        'downloadUrl' => 'api/export_txt.php?download=' . rawurlencode($filename),
    ]);
} catch (InvalidArgumentException $exception) {
    monitor_json_response(['ok' => false, 'error' => $exception->getMessage()], 400);
} catch (Throwable $exception) {
    monitor_log('export_txt.php: ' . $exception->getMessage());
    monitor_json_response(['ok' => false, 'error' => $exception->getMessage()], 500);
}
