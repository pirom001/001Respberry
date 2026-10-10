<?php
declare(strict_types=1);

const MONITOR_MAX_CSV_BYTES = 52428800;
const MONITOR_MAX_CSV_ROWS = 250000;

function monitor_default_config(): array
{
    return [
        'sources' => [
            [
                'id' => 'smt-fac3-no04',
                'name' => 'SMT FAC3 No04',
                'dataDirectory' => 'E:\\Rasberrypi\\GroupSMTFAC3\\No04',
                'filePattern' => 'GSMTFAC3_No04_{date}.csv',
            ],
            [
                'id' => 'smt-fac3-no05',
                'name' => 'SMT FAC3 No05',
                'dataDirectory' => 'E:\\Rasberrypi\\GroupSMTFAC3\\No05',
                'filePattern' => 'GSMTFAC3_No05_{date}.csv',
            ],
            [
                'id' => 'coating-fac3-no01',
                'name' => 'COATING FAC3 No01',
                'dataDirectory' => 'E:\\Rasberrypi\\GroupCOATINGFAC3\\No01',
                'filePattern' => 'GCOATINGFAC3_No01_{date}.csv',
            ],
        ],
        'historyDays' => 30,
        'refreshSeconds' => 8,
        'exportDirectory' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'exports',
    ];
}

function monitor_config_file(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.json';
}

function monitor_get_config(): array
{
    $path = monitor_config_file();
    if (!is_file($path) || !is_readable($path)) {
        throw new RuntimeException('ไม่สามารถอ่านไฟล์ตั้งค่าระบบได้');
    }

    $contents = file_get_contents($path);
    if ($contents === false) {
        throw new RuntimeException('ไม่สามารถอ่านไฟล์ตั้งค่าระบบได้');
    }

    $config = json_decode($contents, true);
    if (!is_array($config)) {
        throw new RuntimeException('รูปแบบไฟล์ตั้งค่าระบบไม่ถูกต้อง');
    }

    return monitor_validate_config($config);
}

function monitor_validate_config(array $config): array
{
    $sources = $config['sources'] ?? null;
    if (!is_array($sources) && isset($config['dataDirectory'], $config['filePattern'])) {
        $sources = [[
            'id' => 'legacy-source',
            'name' => 'แหล่งข้อมูลเดิม',
            'dataDirectory' => $config['dataDirectory'],
            'filePattern' => $config['filePattern'],
        ]];
    }
    $refreshSeconds = filter_var($config['refreshSeconds'] ?? null, FILTER_VALIDATE_INT);
    $exportDirectory = isset($config['exportDirectory']) && is_string($config['exportDirectory'])
        ? trim($config['exportDirectory'])
        : '';

    if (!is_array($sources) || count($sources) < 1 || count($sources) > 20) {
        throw new InvalidArgumentException('ต้องกำหนดแหล่งข้อมูลอย่างน้อย 1 และไม่เกิน 20 แหล่ง');
    }

    $validatedSources = [];
    $seenIds = [];
    foreach ($sources as $source) {
        if (!is_array($source)) {
            throw new InvalidArgumentException('รูปแบบแหล่งข้อมูลไม่ถูกต้อง');
        }

        $id = isset($source['id']) && is_string($source['id']) ? trim($source['id']) : '';
        $name = isset($source['name']) && is_string($source['name']) ? trim($source['name']) : '';
        $directory = isset($source['dataDirectory']) && is_string($source['dataDirectory'])
            ? trim($source['dataDirectory'])
            : '';
        $pattern = isset($source['filePattern']) && is_string($source['filePattern'])
            ? trim($source['filePattern'])
            : '';

        if (preg_match('/^[a-z0-9_-]{1,64}$/D', $id) !== 1 || isset($seenIds[$id])) {
            throw new InvalidArgumentException('รหัสแหล่งข้อมูลไม่ถูกต้องหรือซ้ำกัน');
        }
        if ($name === '' || strlen($name) > 100 || preg_match('/[\x00-\x1F]/', $name) === 1) {
            throw new InvalidArgumentException('ชื่อแหล่งข้อมูลไม่ถูกต้อง');
        }

        monitor_validate_absolute_directory($directory, 'โฟลเดอร์ข้อมูลของ ' . $name);
        monitor_validate_pattern($pattern);
        $seenIds[$id] = true;
        $validatedSources[] = [
            'id' => $id,
            'name' => $name,
            'dataDirectory' => $directory,
            'filePattern' => $pattern,
        ];
    }

    monitor_validate_absolute_directory($exportDirectory, 'โฟลเดอร์บันทึกไฟล์');

    if ($refreshSeconds === false || $refreshSeconds < 5 || $refreshSeconds > 300) {
        throw new InvalidArgumentException('ช่วงเวลารีเฟรชต้องอยู่ระหว่าง 5 ถึง 300 วินาที');
    }

    return [
        'sources' => $validatedSources,
        'historyDays' => 30,
        'refreshSeconds' => $refreshSeconds,
        'exportDirectory' => $exportDirectory,
    ];
}

function monitor_validate_absolute_directory(string $path, string $label): void
{
    if ($path === '' || preg_match('/[\x00-\x1F]/', $path) === 1) {
        throw new InvalidArgumentException($label . ' ไม่ถูกต้อง');
    }

    $isDriveAbsolute = strlen($path) >= 3
        && ctype_alpha($path[0])
        && $path[1] === ':'
        && ($path[2] === '\\' || $path[2] === '/');
    $isUncAbsolute = substr($path, 0, 2) === '\\\\' || substr($path, 0, 2) === '//';
    if (!$isDriveAbsolute && !$isUncAbsolute && substr($path, 0, 1) !== DIRECTORY_SEPARATOR) {
        throw new InvalidArgumentException($label . ' ต้องเป็น absolute path');
    }

    $segments = preg_split('~[\\\\/]~', $path);
    if ($segments === false || in_array('..', $segments, true)) {
        throw new InvalidArgumentException($label . ' ห้ามมี path traversal');
    }
}

function monitor_validate_pattern(string $pattern): void
{
    if (substr_count($pattern, '{date}') !== 1
        || preg_match('/^[A-Za-z0-9_.-]*\{date\}[A-Za-z0-9_.-]*$/D', $pattern) !== 1
    ) {
        throw new InvalidArgumentException('รูปแบบชื่อไฟล์ไม่ถูกต้อง ต้องมี {date} หนึ่งตำแหน่งและไม่มี path');
    }
}

function monitor_save_config(array $config): array
{
    $validated = monitor_validate_config($config);
    $path = monitor_config_file();
    $directory = dirname($path);

    if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
        throw new RuntimeException('ไม่สามารถสร้างโฟลเดอร์ config ได้');
    }

    $json = json_encode($validated, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new RuntimeException('ไม่สามารถแปลงการตั้งค่าเป็น JSON ได้');
    }

    $temporaryPath = $path . '.' . bin2hex(random_bytes(8)) . '.tmp';
    if (file_put_contents($temporaryPath, $json . PHP_EOL, LOCK_EX) === false) {
        throw new RuntimeException('ไม่สามารถเขียนไฟล์ตั้งค่าชั่วคราวได้');
    }

    if (!rename($temporaryPath, $path)) {
        unlink($temporaryPath);
        throw new RuntimeException('ไม่สามารถแทนที่ไฟล์ตั้งค่าได้');
    }

    return $validated;
}

function monitor_log(string $message): void
{
    $directory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs';
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;

    if (is_dir($directory) && is_writable($directory)) {
        if (file_put_contents($directory . DIRECTORY_SEPARATOR . 'monitor.log', $line, FILE_APPEND | LOCK_EX) !== false) {
            return;
        }
    }

    error_log(trim($line));
}

function monitor_csv_delimiter(string $headerLine): string
{
    $bestDelimiter = ',';
    $bestCount = 1;
    foreach ([',', ';', "\t"] as $delimiter) {
        $fields = str_getcsv($headerLine, $delimiter, '"', '');
        if (count($fields) > $bestCount) {
            $bestCount = count($fields);
            $bestDelimiter = $delimiter;
        }
    }

    if ($bestCount < 2) {
        throw new RuntimeException('ไฟล์ CSV ต้องมีหัวตารางอย่างน้อย 2 คอลัมน์');
    }

    return $bestDelimiter;
}

function monitor_find_csv_files(array $source, int $historyDays = 30): array
{
    $directory = realpath($source['dataDirectory']);
    if ($directory === false || !is_dir($directory) || !is_readable($directory)) {
        throw new RuntimeException('ไม่พบโฟลเดอร์ข้อมูล CSV หรือไม่มีสิทธิ์อ่าน');
    }

    $pattern = $source['filePattern'];
    $globPattern = str_replace('{date}', '*', $pattern);
    $matches = glob($directory . DIRECTORY_SEPARATOR . $globPattern, GLOB_NOSORT);
    $candidates = is_array($matches) ? $matches : [];
    $namePattern = '/^' . str_replace('\{date\}', '([0-9]{8})', preg_quote($pattern, '/')) . '$/D';
    $lastDate = new DateTimeImmutable('today');
    $firstDate = $lastDate->sub(new DateInterval('P' . ($historyDays - 1) . 'D'));
    $firstDateKey = $firstDate->format('Ymd');
    $lastDateKey = $lastDate->format('Ymd');
    $files = [];
    foreach ($candidates as $candidate) {
        $name = basename($candidate);
        if (preg_match($namePattern, $name, $match) !== 1
            || $match[1] < $firstDateKey
            || $match[1] > $lastDateKey
            || !is_file($candidate)
            || !is_readable($candidate)
        ) {
            continue;
        }
        $year = (int) substr($match[1], 0, 4);
        $month = (int) substr($match[1], 4, 2);
        $day = (int) substr($match[1], 6, 2);
        if (!checkdate($month, $day, $year)) {
            continue;
        }

        $realFile = realpath($candidate);
        if ($realFile === false || strcasecmp(dirname($realFile), $directory) !== 0) {
            continue;
        }

        $files[] = [
            'path' => $realFile,
            'name' => $name,
            'date' => $match[1],
            'modifiedAt' => filemtime($realFile) ?: 0,
        ];
    }

    usort($files, static function (array $left, array $right): int {
        $dateOrder = strcmp($left['date'], $right['date']);
        return $dateOrder !== 0 ? $dateOrder : ($left['modifiedAt'] <=> $right['modifiedAt']);
    });

    return $files;
}

function monitor_find_csv(array $config): array
{
    $files = monitor_find_csv_files($config);
    if ($files === []) {
        throw new RuntimeException('ไม่พบไฟล์ CSV ภายใน 30 วันที่ผ่านมา');
    }

    return $files[count($files) - 1];
}

function monitor_header_key(string $header): string
{
    return strtolower((string) preg_replace('/[^a-z0-9]/i', '', $header));
}

function monitor_read_csv(string $path): array
{
    $size = filesize($path);
    if ($size === false || $size > MONITOR_MAX_CSV_BYTES) {
        throw new RuntimeException('ขนาดไฟล์ CSV เกินขีดจำกัด 50 MB');
    }

    $handle = fopen($path, 'rb');
    if ($handle === false) {
        throw new RuntimeException('ไม่สามารถเปิดอ่านไฟล์ CSV ได้');
    }

    try {
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            throw new RuntimeException('ไฟล์ CSV ว่างเปล่า');
        }

        $firstLine = preg_replace('/^\xEF\xBB\xBF/', '', $firstLine) ?? $firstLine;
        $delimiter = monitor_csv_delimiter($firstLine);
        rewind($handle);

        $headers = fgetcsv($handle, 0, $delimiter, '"', '');
        if (!is_array($headers) || count($headers) < 2) {
            throw new RuntimeException('ไม่สามารถอ่านหัวตาราง CSV ได้');
        }

        foreach ($headers as $index => $header) {
            $header = trim((string) $header);
            if ($index === 0) {
                $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;
            }
            if ($header === '') {
                throw new RuntimeException('หัวตาราง CSV มีคอลัมน์ที่ไม่มีชื่อ');
            }

            $baseName = $header;
            $suffix = 2;
            while (in_array($header, $headers, true) && array_search($header, $headers, true) !== $index) {
                $header = $baseName . '_' . $suffix;
                $suffix++;
            }
            $headers[$index] = $header;
        }

        $headerSignatures = array_map(
            static function (string $header): string {
                return strtolower(trim($header));
            },
            $headers
        );
        $normalizedHeaders = array_map('monitor_header_key', $headers);
        $dateIndex = null;
        $timeIndex = null;
        $timestampIndex = null;

        foreach ($normalizedHeaders as $index => $key) {
            if (in_array($key, ['timestamp', 'datetime', 'recordedat', 'createdat'], true)) {
                $timestampIndex = $index;
            } elseif (in_array($key, ['date', 'day', 'datadate'], true)) {
                $dateIndex = $index;
            } elseif (in_array($key, ['time', 'clock', 'datatime'], true)) {
                $timeIndex = $index;
            }
        }

        $timestampIndexes = $timestampIndex !== null
            ? [$timestampIndex]
            : array_values(array_filter([$dateIndex, $timeIndex], static function ($index): bool {
                return $index !== null;
            }));
        if ($timestampIndexes === []) {
            $timestampIndex = 0;
            $timestampIndexes = [0];
        }

        $rows = [];
        while (($fields = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
            if ($fields === [null] || $fields === []) {
                continue;
            }

            $fields = array_map(static function ($value): string {
                return trim((string) $value);
            }, $fields);
            if (count($fields) === count($headers)
                && array_map('strtolower', $fields) === $headerSignatures
            ) {
                continue;
            }
            if (count($fields) !== count($headers)) {
                throw new RuntimeException('จำนวนคอลัมน์ในข้อมูล CSV ไม่ตรงกับหัวตาราง');
            }

            $values = array_combine($headers, $fields);
            if (!is_array($values)) {
                throw new RuntimeException('ไม่สามารถจับคู่ข้อมูลกับหัวตาราง CSV ได้');
            }

            if ($timestampIndex !== null) {
                $timestamp = $fields[$timestampIndex];
            } elseif ($dateIndex !== null && $timeIndex !== null) {
                $timestamp = trim($fields[$dateIndex] . ' ' . $fields[$timeIndex]);
            } else {
                $timestamp = $fields[$timestampIndexes[0]];
            }

            $rows[] = ['timestamp' => $timestamp, 'values' => $values];
            if (count($rows) > MONITOR_MAX_CSV_ROWS) {
                throw new RuntimeException('จำนวนแถวในไฟล์ CSV เกินขีดจำกัด 250,000 แถว');
            }
        }
    } finally {
        fclose($handle);
    }

    return monitor_summarize_csv($headers, $rows, $timestampIndexes);
}

function monitor_summarize_csv(array $headers, array $rows, array $timestampIndexes): array
{
    if ($rows === []) {
        throw new RuntimeException('ไฟล์ CSV ไม่มีแถวข้อมูล');
    }

    $dataColumns = array_values(array_filter($headers, static function (string $header) use ($timestampIndexes, $headers): bool {
        $index = array_search($header, $headers, true);
        return $index !== false && !in_array($index, $timestampIndexes, true);
    }));
    if ($dataColumns === []) {
        $dataColumns = [$headers[0]];
    }

    $columns = [];
    foreach ($dataColumns as $column) {
        $numericValues = [];
        $categories = [];
        $isNumeric = true;
        foreach ($rows as $row) {
            $value = $row['values'][$column];
            if ($value === '') {
                continue;
            }
            if (!is_numeric($value) || !is_finite((float) $value)) {
                $isNumeric = false;
            } else {
                $numericValues[] = (float) $value;
            }
            if (!array_key_exists($value, $categories)) {
                $categories[$value] = 0;
            }
            $categories[$value]++;
        }

        $type = $isNumeric && $numericValues !== [] ? 'numeric' : 'categorical';
        $columns[] = [
            'name' => $column,
            'type' => $type,
            'categories' => $type === 'categorical' ? array_slice(array_keys($categories), 0, 50) : [],
            'numericValues' => $type === 'numeric' ? $numericValues : [],
            'categoryCounts' => $type === 'categorical' ? $categories : [],
        ];
    }

    return [
        'headers' => $headers,
        'rows' => $rows,
        'columns' => $columns,
        'rowCount' => count($rows),
    ];
}

function monitor_read_history(array $source, int $historyDays = 30): array
{
    $files = monitor_find_csv_files($source, $historyDays);
    if ($files === []) {
        throw new RuntimeException('ไม่พบไฟล์ CSV ของแหล่งนี้ภายใน 30 วันที่ผ่านมา');
    }

    $totalBytes = 0;
    $allRows = [];
    $headers = null;
    $timestampIndexes = null;
    $fileNames = [];
    $modifiedAt = 0;

    foreach ($files as $file) {
        $totalBytes += (int) filesize($file['path']);
        if ($totalBytes > MONITOR_MAX_CSV_BYTES) {
            throw new RuntimeException('ข้อมูลย้อนหลังของแหล่งนี้มีขนาดเกิน 50 MB');
        }

        $csv = monitor_read_csv($file['path']);
        if ($headers !== null && $headers !== $csv['headers']) {
            throw new RuntimeException('หัวตารางของไฟล์ CSV ในช่วง 30 วันไม่ตรงกัน');
        }

        $headers = $csv['headers'];
        if ($timestampIndexes === null) {
            $headerKeys = array_map('monitor_header_key', $headers);
            $dateIndex = null;
            $timeIndex = null;
            $timestampIndex = null;
            foreach ($headerKeys as $index => $key) {
                if (in_array($key, ['timestamp', 'datetime', 'recordedat', 'createdat'], true)) {
                    $timestampIndex = $index;
                } elseif (in_array($key, ['date', 'day', 'datadate'], true)) {
                    $dateIndex = $index;
                } elseif (in_array($key, ['time', 'clock', 'datatime'], true)) {
                    $timeIndex = $index;
                }
            }
            $timestampIndexes = $timestampIndex !== null
                ? [$timestampIndex]
                : array_values(array_filter([$dateIndex, $timeIndex], static function ($index): bool {
                    return $index !== null;
                }));
            if ($timestampIndexes === []) {
                $timestampIndexes = [0];
            }
        }

        foreach ($csv['rows'] as $row) {
            $row['sourceFile'] = $file['name'];
            $allRows[] = $row;
        }
        if (count($allRows) > MONITOR_MAX_CSV_ROWS) {
            throw new RuntimeException('ข้อมูลย้อนหลังเกินขีดจำกัด 250,000 แถวต่อแหล่ง');
        }
        $fileNames[] = $file['name'];
        $modifiedAt = max($modifiedAt, $file['modifiedAt']);
    }

    if ($headers === null || $timestampIndexes === null) {
        throw new RuntimeException('ไม่พบข้อมูล CSV ที่อ่านได้');
    }

    $summary = monitor_summarize_csv($headers, $allRows, $timestampIndexes);
    $summary['files'] = $fileNames;
    $summary['modifiedAt'] = $modifiedAt;
    return $summary;
}

function monitor_column_stats(array $csv, string $column): array
{
    foreach ($csv['columns'] as $metadata) {
        if ($metadata['name'] !== $column) {
            continue;
        }

        if ($metadata['type'] === 'numeric') {
            $values = $metadata['numericValues'];
            return [
                'type' => 'numeric',
                'count' => count($values),
                'min' => min($values),
                'max' => max($values),
                'average' => array_sum($values) / count($values),
                'categorySummary' => '',
            ];
        }

        $counts = $metadata['categoryCounts'];
        arsort($counts);
        $summary = $counts === []
            ? 'ไม่มีค่าหมวดหมู่'
            : 'ค่าที่พบบ่อย: ' . (string) array_key_first($counts) . ' (' . reset($counts) . ' ครั้ง)';

        return [
            'type' => 'categorical',
            'count' => array_sum($counts),
            'min' => null,
            'max' => null,
            'average' => null,
            'categorySummary' => $summary,
        ];
    }

    throw new InvalidArgumentException('ไม่พบคอลัมน์ที่เลือกในไฟล์ CSV');
}

function monitor_json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, max-age=0');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
