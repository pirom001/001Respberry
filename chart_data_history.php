<?php
function minutesToHours($minutes)
{
    // หาจำนวนชั่วโมง
    $hours = floor($minutes / 60);
    // หาจำนวนนาทีที่เหลือ
    $remainingMinutes = $minutes % 60;
    // ส่งค่าออกในรูปแบบ "ชั่วโมง:นาที"
    return sprintf("%02d hrs %02d", $hours, $remainingMinutes);
}

$file_path = 'backup_data/' . $get_data;
$firstKey = ''; //สี 1
$firstKey2 = ''; //สี 2
$firstKey3 = ''; //สี 3
$firstValue = 0; //จำนวน 1
$firstValue2 = 0; //จำนวน 2
$firstValue3 = 0; //จำนวน 3
$minutes1 = '';
$minutes2 = '';
$minutes3 = '';
// minutesToHours($minutes);

// ตรวจสอบว่าไฟล์มีข้อมูลหรือไม่
if (file_exists($file_path) && filesize($file_path) > 0) {
    // อ่านข้อมูลจากไฟล์
    $file_content = file_get_contents($file_path);

    // แบ่งข้อมูลเป็นอาเรย์ โดยใช้ \n เป็นตัวแยก
    $dataArray = explode("\n", $file_content);
    array_pop($dataArray);

    // สร้างอาร์เรย์ใหม่
    $resultArray = [];

    // ใช้ลูปเพื่อดึงข้อมูลหลัง `,` เท่านั้น
    foreach ($dataArray as $item) {
        $parts = explode(',', $item);
        if (isset($parts[1])) {
            $resultArray[] = trim($parts[1]);
        }
    }

    // แปลงข้อมูลทั้งหมดใน $resultArray เป็นตัวพิมพ์เล็ก
    $resultArrayLowerCase = array_map('strtolower', $resultArray);

    // นับจำนวนข้อมูลของแต่ละสี
    $countByColor = array_count_values($resultArrayLowerCase);
    ksort($countByColor);

    //    foreach ($countByColor as $key => $value) {
    //   echo''.$key.'  '.$value.'<br>';

    //    }
    if (isset(array_keys($countByColor)[0])) {
        $firstKey = array_keys($countByColor)[0]; //คือ สี
        $firstValue = $countByColor[$firstKey]; //คือ จำนวน  # code...
        $minutes1 = minutesToHours($firstValue);
    }
    if (isset(array_keys($countByColor)[1])) {
        $firstKey2 = array_keys($countByColor)[1]; //คือ สี
        $firstValue2 = $countByColor[$firstKey2]; //คือ จำนวน  
        $minutes2 = minutesToHours($firstValue2);
    }
    if (isset(array_keys($countByColor)[2])) {
        $firstKey3 = array_keys($countByColor)[2]; //คือ สี
        $firstValue3 = $countByColor[$firstKey3]; //คือ จำนวน
        $minutes3 = minutesToHours($firstValue3);
    }
} else {
    echo "ไฟล์ไม่มีข้อมูลหรือว่างเปล่า";
}
