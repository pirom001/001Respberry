// เรียกใช้ฟังก์ชันเมื่อหน้าเว็บโหลดเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    // เรียกใช้ฟังก์ชัน fetchAndDisplayLastLine() เมื่อหน้าเว็บโหลด
    fetchAndDisplayLastLine();

    // ตั้งเวลาในการอัปเดตข้อมูลทุก 5 วินาที
    setInterval(fetchAndDisplayLastLine, 2000); // 5000 มีหน่วยเป็นมิลลิวินาที (5 วินาที)
});

function fetchAndDisplayLastLine() {
    const currentMoment = moment();
    // ดึงข้อมูลวันที่ในรูปแบบที่ต้องการ
    const formattedDate = currentMoment.format('YYYY-MM-DD');
    // แสดงค่าวันที่ใน Console
    console.log(formattedDate);
    // ใช้ fetch() เพื่อดึงข้อมูลจากไฟล์
    fetch('setting/file_log_status/Status_file_' + formattedDate + '.log')
        .then(response => response.text()) // แปลง response เป็นข้อความ
        .then(data => {
            // แยกบรรทัดเป็นอาเรย์
            const lines = data.split('\n');

            // ดึงค่าบรรทัดสุดท้าย
            let lastLine = lines[lines.length - 2]; // ลบออกเนื่องจากบรรทัดสุดท้ายมักจะว่างเปล่า
            if (lastLine.includes('ล้มเหลว')) {
                // ถ้ามีให้แสดง 'Red' ใน Console
                // console.log('False');
                lastLine = 'False';

                // ตั้งค่าพื้นหลังของ lastLineContainer เป็นสีแดง
                document.getElementById('lastLineContainer').style.background = 'red';
            } else {
                // ถ้าไม่มีให้แสดง 'Green' ใน Console (หรือสามารถไม่แสดงอะไรก็ได้)
                // console.log('True');
                lastLine = 'True';

                // ตั้งค่าพื้นหลังของ lastLineContainer เป็นสีเขียว
                document.getElementById('lastLineContainer').style.background = 'green';
            }
            // แสดงข้อมูลใน HTML
            document.getElementById('lastLineContainer').innerText = lastLine;
        })
        .catch(error => {
            console.error('Error fetching file.log:', error);
        });
}
//*************************** END ************************************** */