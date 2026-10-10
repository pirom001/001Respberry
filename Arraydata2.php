<?php
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
// echo"dsfdg".$result[0][3]."cljxcljxl";
