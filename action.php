<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body style="background: black; color: greenyellow;">
    <H3 id="con1"></H3>
    <script>
        let seconds = 140;

        function countdown() {
            // console.log(seconds + " วินาที");
            document.getElementById("con1").innerHTML = "นับถอยหลังการ Update:"+seconds + " วินาที";
            seconds--;
            if (seconds < 0) {
                clearInterval(intervalId); // หยุดการทำงานของ setInterval เมื่อนับถอยหลังเสร็จสิ้น
                document.getElementById("con1").innerHTML = "เริ่มทำการ Update Query data!";

            }
        }
        // เรียกใช้ฟังก์ชัน countdown ทุกวินาที
        let intervalId = setInterval(countdown, 1000);
    </script>
</body>

</html>


<?php
$page = $_SERVER['PHP_SELF'];
$sec = "120"; //ทุก 2 นาที 
header("Refresh: $sec; url=$page");
$file_path = 'remark.txt';
$result = array();

// ตรวจสอบว่าไฟล์มีข้อมูลหรือไม่
if (file_exists($file_path) && filesize($file_path) > 0) {
    // อ่านข้อมูลจากไฟล์
    $file_content = file_get_contents($file_path);

    // แบ่งข้อมูลเป็นอาเรย์ โดยใช้ \n เป็นตัวแยก
    $rows = explode("\n", $file_content);

    // สร้าง Array 3 มิติ


    foreach ($rows as $row) {
        // แยกข้อมูลในแต่ละบรรทัดด้วยเครื่องหมาย ","
        $columns = explode(",", $row);

        // เก็บข้อมูลใน Array 3 มิติ
        $result[] = $columns;
    }

    // แสดงผลลัพธ์
    //  print_r($result);
} else {
    echo "ไฟล์ไม่มีข้อมูลหรือว่างเปล่า";
}
// header('Content-Type: application/json');
date_default_timezone_set("Asia/Bangkok");
$dateString = date("Y/m/d");
$newFormat = date("Ymd", strtotime($dateString));

// echo $newFormat . "<br>";

function convertToMinutes($timeString)
{
    // แปลงรูปแบบ ชม:นาที เป็น นาที
    $seconds = strtotime('1970-01-01 ' . $timeString . ' GMT') - strtotime('1970-01-01 00:00:00 GMT');
    $minutes = $seconds / 60;
    return $minutes;
}
function FunSQLdata($data, $num1, $num2)
{
    include('connected.php');
    $myArray = explode(",", $data);
    $myArray[0] = date("y-m-d", strtotime($myArray[0]));
    $time_m = date("H:i:00", strtotime($myArray[1]));

    if ($myArray[1] == " 00:00:00") {
        $myArray[1] = "00:00:01";
    }

    $minutes = convertToMinutes($time_m);
    //    echo $minutes."<br>";
    // แสดงค่าทั้งหมดใน array
    //  print_r($myArray);

    $sql = "INSERT INTO rasberry_data (data_date, data_time, status, status_before, data_minute, data_group, data_number, date_event)
        SELECT '{$myArray[0]}', '{$time_m}', '{$myArray[2]}', '{$myArray[3]}',{$minutes},'{$num1}','{$num2}','{$myArray[1]}'
                FROM dual
        WHERE NOT EXISTS (
        SELECT 1 FROM rasberry_data 
                WHERE data_date = '{$myArray[0]}' 
                AND data_time = '{$time_m}' 
                AND status = '{$myArray[2]}' 
                AND status_before='{$myArray[3]}'
                AND data_minute='{$minutes}'
                AND data_group='{$num1}'
                AND data_number='{$num2}'
                AND date_event='{$myArray[1]}')LIMIT 1;";
    //echo $sql."<br>";
    if ($conn->query($sql) === TRUE) {
        error_log("$sql\n", 3, "error.log");
        echo "New record created successfully<br>";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

    $conn->close();
}

// กำหนดชื่อไฟล์ CSV ที่ต้องการดึงข้อมูล
for ($i = 0; $i < 3; $i++) {

    $result[$i][3] = preg_replace('/[^\w]/', '', str_replace("\n", "", $result[$i][3]));

    $num1 = $result[$i][2];
    $num2 = $result[$i][3];

    $csvFile = '\\\\' . $result[$i][0] . '\\' . $result[$i][1] . '\\' . $result[$i][2] . '\\' . $result[$i][3] . '\\GSMTFAC1_' . $result[$i][3] . '_' . $newFormat . '.csv';
    // เปิดไฟล์ CSV
    echo "<br>";
    echo $csvFile . "<br>";

    $fileHandle = fopen($csvFile, 'r');
    $dateString = date("Y-m-d");
    $logFile = 'setting/File_log_status/Status_file_' . $dateString . '.log';
    $time_connect = '';
    $currentTimestamp = time();

    $currentDateTime = date("Y-m-d H:i:s", $currentTimestamp);
    // ตรวจสอบว่าไฟล์ถูกเปิดสำเร็จหรือไม่
    if ($fileHandle !== false) {
        $time_connect = $currentDateTime . ':เชื่อมต่อ File data สมบูรณ์:';
        // วนลูปอ่านข้อมูลจากไฟล์ CSV
        while (($data = fgetcsv($fileHandle)) !== false) {
            // $data เป็น array ที่มีข้อมูลของแถวปัจจุบัน
            // เตรียมข้อมูลสำหรับบันทึกลงฐานข้อมูล
            $columns = implode(', ', $data);
            $values = implode(', ', array_fill(0, count($data), '?'));
            if ($columns == 'date, time, now, before') {
                # code...
            } else {
                FunSQLdata($columns, $num1, $num2);
                // echo $columns.'<br>'; # code...
            }
            // สร้างบรรทัดใหม่หลังจากแสดงข้อมูลในแถวปัจจุบัน
            echo "\n";
        }

        // ปิดไฟล์ CSV
        fclose($fileHandle);
    } else {
        // ไม่สามารถเปิดไฟล์ CSV ได้
        echo "ไม่สามารถเปิดไฟล์ CSV ได้<br>";
        $time_connect = $currentDateTime . '!!!เชื่อม File data ล้มเหลว!!!:';
    }

    if (strpos(file_get_contents($logFile), $time_connect) === false) {
        // ไม่พบค่านี้ในไฟล์ ดังนั้นให้เพิ่มลงในไฟล์
        file_put_contents($logFile, $time_connect . "\n", FILE_APPEND | LOCK_EX);
    } else {
        // พบค่านี้ในไฟล์แล้ว
        echo "Value already exists.";
    }
}
?>