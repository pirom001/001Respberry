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
    let seconds = 120;
    function countdown() {
      // console.log(seconds + " วินาที");
      document.getElementById("con1").innerHTML = "นับถอยหลังการ Get data Chart.:"+seconds + " วินาที";
      seconds--;
      if (seconds < 0) {
        clearInterval(intervalId); // หยุดการทำงานของ setInterval เมื่อนับถอยหลังเสร็จสิ้น
        document.getElementById("con1").innerHTML = "เริ่มทำการ Update Query data!";

      }
    }
    // เรียกใช้ฟังก์ชัน countdown ทุกวินาที
    let intervalId = setInterval(countdown, 1000);
  </script>
      <script>
        // สร้าง Object ของเวลาปัจจุบัน
        function updateTime() {
            const now = new Date();

            // ดึงข้อมูลเวลา
            const hours = now.getHours();
            const minutes = now.getMinutes();
            const seconds = now.getSeconds();

            // กำหนดรูปแบบเวลา (ในที่นี้ใช้รูปแบบ HH:MM:SS)
            const formattedTime = `${formatDigit(hours)}:${formatDigit(minutes)}:${formatDigit(seconds)}`;

            // นำเวลาที่ได้มาแสดงใน textarea
            document.getElementById('story').value = formattedTime;
            // console.log(formattedTime);
            if (formattedTime === '23:59:59') {
              location.reload(true);
            }
        }

        function formatDigit(digit) {
            // ฟังก์ชันเพิ่ม 0 ถ้าเลขน้อยกว่า 10
            return digit < 10 ? `0${digit}` : digit;
        }

        // เรียกใช้ฟังก์ชันทุกๆ 1 วินาที
        setInterval(updateTime, 1000);

        // ตั้งค่าเวลาเริ่มต้น
        updateTime();
    </script>
</body>

</html>
<?php
//  header('Content-Type: application/json');
$page = $_SERVER['PHP_SELF'];
$sec = "120";
header("Refresh: $sec; url=$page");
include("connected.php");
include_once("Arraydata2.php");
$dateString = date("Y-m-d");
$newFormat = date("Ymd", strtotime($dateString));
// echo"test";

function Funcolor($color)
{
  $getdata = '';
  if ($color == ' run' || $color == ' start') {
    $getdata = 'green';
  } elseif ($color == ' end' || $color == ' stop') {
    $getdata = 'red';
  } elseif ($color == ' alarm') {
    $getdata = 'yellow';
  }
  return $getdata;
}

function FunlastnumArray($arr)
{
  $resultArray = array();
  // วนลูปเพื่อกรองข้อมูล
  foreach ($arr as $data) {
    $data_minute = $data['data_minute'];
    // echo''. $data_minute .'<br>';
    // เก็บ Array ล่าสุดเมื่อ 'data_minute' ซ้ำกัน
    $resultArray[$data_minute] = $data;
  }
  // $resultArray = array_unique($data['data_minute']);

  // แปลง $resultArray เป็น Indexed Array
  $resultArray = array_values($resultArray);

  //   print_r($resultArray); 
  // echo "<br>"; 

  $maxDataMinute = max(array_column($resultArray, 'data_minute'));
  secondnumArray($arr, $maxDataMinute);
}

function secondnumArray($resultArray, $maxDataMinute)
{
  echo "ค่ามากที่สุด {$maxDataMinute}<br>";
  // $data_groub = '';
  // $data_number = '';
  $latestIdByDataGroup = array();
  foreach ($resultArray as $data) {
    $id = $data['id'];
    $date_event = $data['date_event'];
    $status = $data['status'];
    $status_before = $data['status_before'];
    $data_minute = $data['data_minute'];

    $data_group = $data['data_group'];
    $data_number = $data['data_number'];

    // echo "id:{$id} : {$status}  : {$date_event} : {$status_before} : {$data_minute}<br>";

    if (!isset($latestIdByDataGroup[$data_minute]) || $id > $latestIdByDataGroup[$data_minute]['id']) {
      $latestIdByDataGroup[$data_minute] = array(
        'id' => $id,
        'status' => $status,
        'date_event' => $date_event,
        'status_before' => $status_before,
        'data_minute' => $data_minute,
        'data_group' => $data_group,
        'data_number' => $data_number
      );
    }
  }
  foreach ($latestIdByDataGroup as $data) {
    $id = $data['id'];
    $status = $data['status'];
    $date_event = $data['date_event'];
    $status_before = $data['status_before'];
    $data_minute = $data['data_minute'];
    $data_group = $data['data_group'];
    $data_number = $data['data_number'];

    echo "id:{$id} {$date_event} : {$status} : {$status_before} : {$data_minute} : {$data_group} : {$data_number}<br>";
  }

  $status_num = "";
  $datelog = date("Y-m-d");
  $file = 'data_chart/' . $data_group . '_' . $data_number . '_' . $datelog . '.txt';
  $logFile = 'backup_data/' . $data_group . '_' . $data_number . '_' . $datelog . '.log';
  // echo''. $file .''. $status_num .''.
  $status_num = '';
  $statusOf2 = '';
  $statusOf3 = '';
  $statusOf4 = '';


  if (file_exists($file)) {
    // ถ้ามี, ให้ลบไฟล์เดิม
    unlink($file);
  }


  for ($j = 0; $j < $maxDataMinute + 1; $j++) { //สร้างกราฟ
    //echo "{$j}:{$maxDataMinute} <br>";
    if (in_array($j, array_column($latestIdByDataGroup, 'data_minute'))) {

      //  $key = array_search($j, array_column($latestIdByDataGroup, 'data_minute'));
      $statusOf4 = $latestIdByDataGroup[$j]['status'];
      $statusOf3 = $latestIdByDataGroup[$j]['status_before'];
      $statusOf2 = $latestIdByDataGroup[$j]['date_event'];
      if ($statusOf4 == " other") {
        $statusOf3 = Funcolor(" stop"); //เครื่อง SMT  "other คือ Stop"
        $status_num = $statusOf3;
        echo "{$statusOf4} other ค่า {$j} คือ:" . $status_num . "<br>";
      } else {
        $statusOf4 = Funcolor($statusOf4);
        $status_num = $statusOf4;
        echo " ค่า {$j} คือ:1" . $status_num . "<br>";
      }
      // echo "มี, ค่า status: {$statusOf4} <br>";

    } else {

      echo " ค่า {$j} ไม่พบใน \$resultArray: {$status_num}<br>";
    }

    // file_put_contents($logFile, "[{$j}]" . $status_num . "\n", FILE_APPEND | LOCK_EX);
    $status_num2 = "{$j},$status_num";
    file_put_contents($file, $status_num . "\n", FILE_APPEND);


    if (strpos(file_get_contents($logFile), $status_num2) === false) {
      // ไม่พบค่านี้ในไฟล์ ดังนั้นให้เพิ่มลงในไฟล์
      file_put_contents($logFile, $status_num2 . "\n", FILE_APPEND | LOCK_EX);
    } else {
      // พบค่านี้ในไฟล์แล้ว
      echo "Value already exists.";
    }
  }
  # code...

}

$numberOfElements = count($result); //หาจำนวนสมาชิกอาเรย์
// echo "จำนวน {$numberOfElements}";
$dataArray = array();
for ($i = 0; $i < $numberOfElements; $i++) {
  $result[$i][3] = preg_replace('/[^\w]/', '', str_replace("\n", "", $result[$i][3]));
  $num1 = $result[$i][2];
  $num2 = $result[$i][3];
  $sql = "SELECT id,date_event,data_date,data_time, status,status_before,data_minute,data_group,data_number from rasberry_data 
  where (data_date='{$dateString}')AND(data_group='$num1')AND(data_number='$num2') ORDER BY id ASC";
  echo $sql . "<br>";
  // // ทำการ query และเก็บผลลัพธ์ในตัวแปร result
  $result2 = $conn->query($sql);
  // ตรวจสอบว่ามีข้อมูลใน result หรือไม่
  if ($result2->num_rows > 0) {
    // สร้าง Array เพื่อเก็บค่า data_minute
    $dataMinuteArray = array();
    // วนลูปเก็บค่า data_minute ลงใน Array
    while ($row = $result2->fetch_assoc()) {
      $dataArray[] = array(
        'status' => $row['status'],
        'data_minute' => $row['data_minute'],
        'status_before' => $row['status_before'],
        'date_event' => $row['date_event'],
        'id' => $row['id'],
        'data_group' => $row['data_group'],
        'data_number' => $row['data_number']
      );
    }
    FunlastnumArray($dataArray);
  }
  unset($dataArray); //ล้างค่า Array
  echo '<br>';
}
