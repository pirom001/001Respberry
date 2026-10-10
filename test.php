<?php
// $datelog = date("Y-m-d");
$datelog = '2024-01-24';
$file_path = 'backup_data/GroupSMTFAC1_No01_' . $datelog . '.log';
// $file_path = 'data_chart/GroupSMTFAC1_No01_'.$datelog .'.txt';

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
    // แสดงผลลัพธ์
    print_r($countByColor);
    // ตั้งค่า header เพื่อระบุว่าข้อมูลที่ส่งกลับเป็น JSON
     header('Content-Type: application/json');
    // ส่ง JSON กลับไปที่ JavaScript
   // echo json_encode($resultArray);
} else {
    echo "ไฟล์ไม่มีข้อมูลหรือว่างเปล่า";
}
