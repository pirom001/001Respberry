<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/monitor.php';

try {
    $config = monitor_get_config();
    $sourceId = isset($_GET['sourceId']) && is_string($_GET['sourceId'])
        ? $_GET['sourceId']
        : '';
    if ($sourceId === '') {
        monitor_json_response([
            'ok' => true,
            'sources' => array_map(static function (array $source): array {
                return ['id' => $source['id'], 'name' => $source['name']];
            }, $config['sources']),
            'historyDays' => $config['historyDays'],
            'refreshSeconds' => $config['refreshSeconds'],
        ]);
    }

    $source = null;
    foreach ($config['sources'] as $configuredSource) {
        if ($configuredSource['id'] === $sourceId) {
            $source = $configuredSource;
            break;
        }
    }
    if ($source === null) {
        throw new InvalidArgumentException('ไม่พบแหล่งข้อมูลที่เลือก');
    }

    $csv = monitor_read_history($source, $config['historyDays']);
    $columnNames = array_column($csv['columns'], 'name');
    $selectedColumn = isset($_GET['column']) && is_string($_GET['column'])
        ? $_GET['column']
        : (string) $csv['columns'][0]['name'];

    if (!in_array($selectedColumn, $columnNames, true)) {
        throw new InvalidArgumentException('คอลัมน์ที่เลือกไม่ถูกต้อง');
    }

    $stats = monitor_column_stats($csv, $selectedColumn);
    $chartRows = array_slice($csv['rows'], -2000);
    $tableRows = array_slice($csv['rows'], -300);
    monitor_json_response([
        'ok' => true,
        'source' => ['id' => $source['id'], 'name' => $source['name']],
        'files' => $csv['files'],
        'fileCount' => count($csv['files']),
        'modifiedAt' => date(DATE_ATOM, $csv['modifiedAt']),
        'lastTimestamp' => $csv['rows'][count($csv['rows']) - 1]['timestamp'],
        'totalRows' => $csv['rowCount'],
        'historyDays' => $config['historyDays'],
        'columns' => $csv['columns'],
        'selectedColumn' => $selectedColumn,
        'stats' => $stats,
        'chartRows' => $chartRows,
        'tableRows' => $tableRows,
        'refreshSeconds' => $config['refreshSeconds'],
        'limits' => ['chartRows' => 2000, 'tableRows' => 300],
    ]);
} catch (InvalidArgumentException $exception) {
    monitor_json_response(['ok' => false, 'error' => $exception->getMessage()], 400);
} catch (RuntimeException $exception) {
    if (in_array($exception->getMessage(), [
        'ไม่พบโฟลเดอร์ข้อมูล CSV หรือไม่มีสิทธิ์อ่าน',
        'ไม่พบไฟล์ CSV ของแหล่งนี้ภายใน 30 วันที่ผ่านมา',
    ], true)) {
        monitor_json_response(['ok' => false, 'error' => $exception->getMessage()]);
    }

    monitor_log('get_data.php: ' . $exception->getMessage());
    monitor_json_response(['ok' => false, 'error' => $exception->getMessage()], 500);
} catch (Throwable $exception) {
    monitor_log('get_data.php: ' . $exception->getMessage());
    monitor_json_response(['ok' => false, 'error' => $exception->getMessage()], 500);
}
