<?php
if (isset($_GET["date_h"])) {
    $group = $_GET["group"];
    $date_h = $_GET['date_h'];
    $number_h = $_GET['number'];
    $get_data = "{$group}_{$number_h}_{$date_h}.log";
    $get_data_show = "{$group}_{$number_h}_{$date_h}";
    // echo $get_data;

?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>001 Project Rasberry Pi</title>
        <link rel="stylesheet" href="css/stylesheet.css">
        <link rel="stylesheet" href="css/tooltip.css">
        <script src="js/html2canvas.min.js"> </script>
        <script src="js/moment.min.js"> </script>
        <style>
            #scrollContainer1 {
                width: 100%;
                height: 80px;
                overflow: auto;
                white-space: nowrap;
                border: 1px solid #ccc;
                position: relative;
            }
        </style>
    </head>

    <body>
        <div id="photo">
            <div style="display: flex; flex-direction: row; width: 100%;">
                <div style="width: 50%;">
                    <div style="display: flex; flex-direction: row; width: 100%;">
                        <div style="width: 20%;">
                            <a href="index.php"> <img src="img/TTEC (1).png" alt="Girl in a jacket" width="150" height="100"></a>

                        </div>
                        <div style="width: 40%;">
                            <label style="font-size: -webkit-xxx-large;"><b>SMT機械稼働率 </b></label>
                            <p>อัตราการทำงานเครื่อง SMT. <mark>version 0.1</mark> date: 24-01-2024</p>
                        </div>
                        <div style="width: 40%;text-align:right">
                            <!-- <label for="">TEST</label> -->
                            <fieldset>
                                <legend>[ 状態 ] Status:</legend>
                                <table border="1" style="width: 100%;">
                                    <tr>
                                        <td style="background:red ;text-align:center;">[赤] RED</td>
                                        <td>停止、終了 他の</td>
                                        <td>Stop, End ,Other</td>
                                    </tr>
                                    <tr>
                                        <td style="background:yellow ;text-align:center;">[黄色] YELLOW</td>
                                        <td>警報</td>
                                        <td>Alarm</td>
                                    </tr>
                                    <tr>
                                        <td style="background:green ;text-align:center; color:aliceblue">[緑] GREEN</td>
                                        <td>スタート、ラン</td>
                                        <td>Start, Run</td>
                                    </tr>
                                </table>
                            </fieldset>
                        </div>
                    </div>
                </div>
                <div style="width: 38%; text-align:right">
                    <form action="history.php">
                        <fieldset>
                            <legend>[ 検索履歴 ] <img src="img/Search.png" alt="Girl in a jacket" style="margin-bottom: -3px;"> Search History:</legend>
                            <label for="date">[日付] date:</label>
                            <input type="date" id="date_h" name="date_h" required="required">
                            <label for="birthday">Group:</label>
                            <select name="group" id="group" class="form-control" required="required">
                                <?php
                                include('connected.php');
                                $sql = "SELECT data_group FROM rasberry_data GROUP BY data_group ORDER BY data_group";
                                $result2 = $conn->query($sql);
                                // ตรวจสอบว่ามีข้อมูลใน result หรือไม่
                                if ($result2->num_rows > 0) {
                                    while ($row = $result2->fetch_assoc()) {
                                        echo "<option value=\"{$row['data_group']}\">{$row['data_group']}</option>";
                                    }
                                }
                                ?>
                            </select>
                            <label for="number"> No:</label>
                            <select name="number" id="number" class="form-control" required="required" style="width: 150px">
                                <?php
                                include('connected.php');
                                $sql = "SELECT data_number FROM rasberry_data GROUP BY data_number ORDER BY data_number";
                                $result2 = $conn->query($sql);
                                // ตรวจสอบว่ามีข้อมูลใน result หรือไม่
                                if ($result2->num_rows > 0) {
                                    while ($row = $result2->fetch_assoc()) {
                                        echo "<option value=\"{$row['data_number']}\">{$row['data_number']}</option>";
                                    }
                                }
                                ?>

                            </select>
                            <br><br>
                            <input type="submit" value="Submit">
                        </fieldset>
                    </form>
                </div>
                <div style="width: 12%; text-align:center">
                    <textarea id="story" name="story" rows="1" cols="10">TIME</textarea>
                    <table border="1" style="width: 100%;">
                        <tr>
                            <td><button id="fullScreenButton" onclick="toggleFullScreen()">
                                    <img src="img/fullsreen.ico" alt="Girl in a jacket" width="" height="">
                                </button>
                            </td>
                            <td><button id="reloadButton" onclick="reloadPage()"><img src="img/Reload2.png" alt="Girl in a jacket" width="" height=""></button></td>
                            <td>
                                <button><img src="img/Setting.ico" alt="Girl in a jacket" width="" height=""></button>
                            </td>
                        </tr>
                        <tr>
                            <th>Full screen</th>
                            <td>Reload</td>
                            <td>Setting</td>
                        </tr>
                        <tr>
                            <th>Data File[ <label id="lastLineContainer">test</label> ]</th>
                            <td></td>
                            <td></td>
                        </tr>
                    </table>
                </div>

            </div>
            <div>
                <hr>

                <label style="font-size: x-large;color: #08519e;">
                    <img src="img/history.png" alt="Girl in a jacket" style="width: 2%; margin-bottom: -10px;">
                    <b>[ ページ履歴データ <?php echo $_GET['date_h'] ?> ]</b></label>
                Page History data of <?php echo $_GET['date_h'] ?>
                <!-- <input value='<?= $get_data ?>' width ='50px' />  -->
                <button style="width: 100%; font-size:larger"><?= $group; ?></button>
            </div>

            <div id="scrollContainer1">
                <div id="scrollContent" class="scroll-container">
                    <table border="1" style="width: 100%;">
                        <!-- <tr>
                        <th colspan="146" style="font-size: x-large;background: azure;">GroupSMTFAC1</th>
                    </tr> -->
                        <tr style="text-align: left;">
                            <th colspan="2">ชั่วโมง</th>
                            <?php
                            for ($k = 0; $k < 24; $k++) {
                                echo "<th colspan=\"6\">" . $k . ":00</th>";
                            }
                            ?>
                        </tr>
                        <tr>
                            <th>นาที</th>
                            <th>-</th>
                            <?php
                            for ($k = 0; $k < 24; $k++) {
                                for ($m = 0; $m < 6; $m++) {
                                    echo "<td>" . $m . "0</td>";
                                }
                            }
                            ?>
                        </tr>
                        <tbody id="getshow"></tbody>

                        <script>
                            function getCurrentTimeInMinutes() {
                                const now = new Date();
                                const hours = now.getHours();
                                const minutes = now.getMinutes();
                                const totalMinutes = hours * 60 + minutes;
                                return totalMinutes;
                            }

                            let lastDataValue = null;

                            function fetchAndDisplayData() {
                                var getdata = 'backup_data/<?= $get_data ?>';
                                fetch(getdata)
                                    .then(response => response.text())
                                    .then(data => {
                                        // ตัดข้อมูลเป็นแถว
                                        const rows = data.split('\n').map(item => {
                                            // Replace digits and comma with an empty string
                                            return item.replace(/\d+,/, '').trim();
                                        });
                                        // console.log(rows);
                                        const currentTimeInMinutes = getCurrentTimeInMinutes();

                                        // สร้างตารางใหม่
                                        const table = document.getElementById('getshow');
                                        table.innerHTML = ''; // เคลียร์ตารางทั้งหมด

                                        // เพิ่มแถวในตาราง
                                        for (let row = 1; row <= 1; row++) {
                                            let num = 0;
                                            const newRow = document.createElement('tr');

                                            // เพิ่มเซลล์ในแถว
                                            const cell1 = document.createElement('td');
                                            cell1.textContent = 'No01';
                                            cell1.style.backgroundColor = 'white';
                                            cell1.style.transform = 'rotate(0deg)';
                                            cell1.style.whiteSpace = 'nowrap';
                                            newRow.appendChild(cell1);

                                            const cell2 = document.createElement('td');
                                            cell2.innerHTML = '00';
                                            newRow.appendChild(cell2);

                                            // เพิ่มเซลล์ที่เป็น th ในแถว
                                            for (let col = 0; col < 144; col++) {
                                                const th = document.createElement('th');

                                                // แยกข้อมูลที่ได้รับมาและนำมาแสดงในแต่ละเซลล์
                                                for (let i = 0; i < 10; i++) {
                                                    const label = document.createElement('label');
                                                    const dataIndex = num + i;

                                                    if (typeof rows[dataIndex] !== 'undefined') {
                                                        label.style.background = rows[dataIndex].trim();
                                                        label.style.color = rows[dataIndex].trim();

                                                        if (i < 10 - 1) {
                                                            label.textContent = `.`;
                                                        } else {
                                                            label.textContent = `.`;
                                                        }

                                                        th.appendChild(label);
                                                        lastDataValue = rows[dataIndex].trim();
                                                    } else {
                                                        if (currentTimeInMinutes > num) {
                                                            const label = document.createElement('label');
                                                            label.style.background = lastDataValue;
                                                            label.style.color = lastDataValue;
                                                            label.textContent = `.`;

                                                            th.appendChild(label);
                                                        } else {
                                                            const label = document.createElement('label');

                                                            label.style.color = 'white';
                                                            label.textContent = `.`;

                                                            th.appendChild(label);
                                                        }
                                                    }
                                                }

                                                newRow.appendChild(th);
                                                num += 10;
                                            }

                                            table.appendChild(newRow);
                                        }
                                    })
                                    .catch(error => console.error('Error fetching data:', error));
                            }

                            fetchAndDisplayData();

                            // Fetch data every 1 second
                            setInterval(fetchAndDisplayData, 1000);
                        </script>

                    </table>
                </div>
            </div>

            <div id="sh_setting" style="display: none;">
                <iframe src="http://localhost/001rasberry/action.php" title="aaction.php" width="49%" height="100px"></iframe>
                <iframe src="http://localhost/001rasberry/getdata.php" title="aaction.php" width="49%" height="100px"></iframe>
            </div>
            <div>
                <script type="text/javascript" src="js/loader.js"></script>
                <?php include_once("chart_data_history.php"); ?>
                <div id="piechart"></div>
                <script type="text/javascript">
                    // Load google charts
                    google.charts.load('current', {
                        'packages': ['corechart']
                    });
                    google.charts.setOnLoadCallback(drawChart);
                    // ใส่ข้อมูลลงไปในแผนภูมิวงกลม
                    function drawChart() {
                        var data = google.visualization.arrayToDataTable([
                            ['Task', 'Hours per Day'],
                            ['<?= $firstKey ?> <?= $firstValue ?> minutes or <?= $minutes1 ?> min', <?= $firstValue ?>],
                            ['<?= $firstKey2 ?> <?= $firstValue2 ?> minutes or <?= $minutes2 ?> min', <?= $firstValue2 ?>],
                            ['<?= $firstKey3 ?> <?= $firstValue3 ?> minutes or <?= $minutes3 ?> min', <?= $firstValue3 ?>],

                        ]);

                        // ชื่อหัข้อวงกลม ขนาด และความสูงของแผนภูมิวงกลม
                        var options = {
                            'title': '<?= $get_data_show; ?>',
                            'width': 550,
                            'height': 300,
                            'colors': ['<?= $firstKey ?>', '<?= $firstKey2 ?>', '<?= $firstKey3 ?>'], // Specify custom colors for each section
                            'pieSliceTextStyle': {
                                color: 'black', // Set the text color of the labels
                                fontName: 'Arial', // Set the font family for the labels
                                fontSize: 12 // Set the font size for the labels
                            }
                        };

                        // Display the chart inside the <div> element with id="piechart"
                        var chart = new google.visualization.PieChart(document.getElementById('piechart'));
                        chart.draw(data, options);
                    }
                </script>
            </div>
        </div>

        <button onclick="takeshot()">Take Screenshot </button>
        <button onclick="toggleSetting()">Check settings</button>
        <div>
            <!-- <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script> -->
            <div id="output"></div>
        </div>

        <script>
            // คำนวน scroll bar ตาม time 
            var scrollContainer = document.getElementById('scrollContainer1');
            var scrollContent = document.getElementById('scrollContent');
            var scrollSpeed = 60000; // ความเร็วของ scroll (ปรับตามต้องการ)// 60000 เท่ากับ 1 นาที
            var initialScrollLeft = calculateInitialScrollLeft(); // คำนวณตำแหน่งเริ่มต้น

            // กำหนดให้ scrollContainer.scrollLeft เท่ากับ initialScrollLeft
            scrollContainer.scrollLeft = Math.max(initialScrollLeft, 0);
            console.log('Scroll :', scrollContainer.scrollLeft);

            function slowScroll() {
                scrollContainer.scrollLeft += 2.09;

                // ล็อค cell1 ทำให้ไม่เลื่อนไปด้วย scroll
                var cell1 = document.querySelector('#scrollContainer1 #scrollContent td:first-child');
                //cell1.style.left = `${scrollContainer.scrollLeft}px`;

                // แสดงค่าความยาวของ Scroll
                console.log('Scroll Container Scroll Left:', scrollContainer.scrollLeft);

                if (scrollContainer.scrollLeft >= scrollContent.scrollWidth - scrollContainer.clientWidth) {
                    clearInterval(scrollInterval);
                }
            }

            var scrollInterval = setInterval(slowScroll, scrollSpeed);

            // ฟังก์ชันคำนวณตำแหน่งเริ่มต้น
            function calculateInitialScrollLeft() {
                var currentDate = new Date();
                var hours = currentDate.getHours();
                var minutes = currentDate.getMinutes();
                var seconds = currentDate.getSeconds();
                console.log(' HH ' + hours + ' tt ' + minutes + ' ss ' + seconds);

                // แปลงเวลาให้เป็นตำแหน่ง scroll
                var totalSeconds = hours * 3600 + minutes * 60 + seconds;
                console.log(' total ' + totalSeconds);
                var totalScrollWidth = scrollContent.scrollWidth - scrollContainer.clientWidth;
                var initialScrollLeft = (totalSeconds % 86400) / 86400 * totalScrollWidth; // 86400 คือจำนวนวินาทีใน 1 วัน

                return initialScrollLeft;
            }
        </script>

        </script>
        <script type="text/javascript">
            //แค๊ปถ่ายรูป
            // Define the function
            // to screenshot the div
            function takeshot() {
                let div =
                    document.getElementById('photo');

                // Use the html2canvas
                // function to take a screenshot
                // and append it
                // to the output div
                html2canvas(div).then(
                    function(canvas) {
                        document
                            .getElementById('output')
                            .appendChild(canvas);
                    })
            }
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
                const formattedTime = `T:${formatDigit(hours)}:${formatDigit(minutes)}:${formatDigit(seconds)}`;

                // นำเวลาที่ได้มาแสดงใน textarea
                document.getElementById('story').value = formattedTime;
                console.log(formattedTime);
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
        <script src="js/fullscreen.js" type="text/javascript"> </script>
        <script src="js/funtionscript_index.js" type="text/javascript"></script>

    </body>

    </html>

<?php } else {
    echo "<h1>ไม่พบข้อมูลที่คุณตามหา  กรูณตรวจสอบใหม่อีกครั้งค่ะ</h1>";
} ?>