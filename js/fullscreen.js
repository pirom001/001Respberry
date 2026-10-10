        //********************************************************************************************************* */
        // ฟังก์ชันที่ใช้ในการรีโหลดหน้าทุก 1 ชม
        function reloadPage() {
            location.reload(true); // ใช้พารามิเตอร์ true เพื่อทำการโหลดหน้าใหม่จากเซิร์ฟเวอร์ (ไม่ใช้จากแคช)
        }
        // เรียกใช้ฟังก์ชันที่ทำการรีโหลดทุก 1 ชั่วโมง
        setInterval(reloadPage, 3600000); // 1 ชั่วโมง = 3600000 มิลลิวินาที
        //*********************************************************************************************************** */
        function toggleFullScreen() {
            var doc = window.document;
            var docEl = doc.documentElement;

            var requestFullScreen = docEl.requestFullscreen || docEl.mozRequestFullScreen || docEl.webkitRequestFullScreen || docEl.msRequestFullscreen;
            var exitFullScreen = doc.exitFullscreen || doc.mozCancelFullScreen || doc.webkitExitFullscreen || doc.msExitFullscreen;

            if (!doc.fullscreenElement && !doc.mozFullScreenElement && !doc.webkitFullscreenElement && !doc.msFullscreenElement) {
                requestFullScreen.call(docEl);
            } else {
                exitFullScreen.call(doc);
            }
        }
        //*********************************************************************************************** */
        function reloadPage() {
            location.reload(true); // ใช้พารามิเตอร์ true เพื่อทำการโหลดหน้าใหม่จากเซิร์ฟเวอร์ (ไม่ใช้จากแคช)
        }
        //*********************************************************************************************** */
        function toggleSetting() {
            var settingDiv = document.getElementById('sh_setting');
            settingDiv.style.display = (settingDiv.style.display === 'none') ? 'block' : 'none';
        }
        //*********************************************************************************************88 */