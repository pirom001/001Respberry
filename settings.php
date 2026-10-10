<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/monitor.php';

$config = monitor_get_config();
function monitor_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#10151f">
    <title>Raspberry Monitor | ตั้งค่า</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <a class="brand" href="index.php" aria-label="Raspberry Monitor หน้าหลัก">
                <span class="brand-mark">R</span>
                <span><strong>raspberry</strong><small>MONITORING SYSTEM</small></span>
            </a>
            <div class="sidebar-caption">เมนูหลัก</div>
            <nav class="main-nav" aria-label="เมนูหลัก">
                <a class="nav-link" href="index.php"><span class="nav-icon">◫</span> ภาพรวม</a>
                <a class="nav-link active" href="settings.php"><span class="nav-icon">⚙</span> ตั้งค่าระบบ</a>
                <a class="nav-link" href="legacy_index.php"><span class="nav-icon">↗</span> ระบบ SMT เดิม</a>
            </nav>
            <div class="sidebar-bottom">
                <span class="live-dot"></span>
                <span>ระบบมอนิเตอร์พร้อมทำงาน</span>
                <small>CSV DATA MONITOR</small>
            </div>
        </aside>
        <main class="main-content">
            <header class="topbar settings-topbar">
                <div>
                    <div class="eyebrow">SYSTEM / CONFIGURATION</div>
                    <h1>ตั้งค่าระบบ</h1>
                    <p class="page-subtitle">กำหนดแหล่งข้อมูลและรูปแบบการทำงานของ Dashboard</p>
                </div>
                <a class="button button-secondary" href="index.php">← กลับหน้าภาพรวม</a>
            </header>
            <section class="panel settings-panel">
                <form id="settings-form">
                    <div class="form-section">
                        <div class="section-label">CSV DATA SOURCES</div>
                        <h2>แหล่งข้อมูล CSV <span class="retention-badge">ย้อนหลัง 30 วัน</span></h2>
                        <p class="form-intro">เพิ่มแหล่งข้อมูลแต่ละเครื่องได้สูงสุด 20 แหล่ง แล้วเลือกเพิ่ม/ลบกราฟที่หน้า Dashboard ได้ตามต้องการ ระบบอ่านไฟล์ต้นทางย้อนหลัง 30 วันและไม่ลบไฟล์ CSV</p>
                        <div id="source-list" class="source-list">
                            <?php foreach ($config['sources'] as $source): ?>
                                <article class="source-config-card" data-source-row>
                                    <input type="hidden" data-field="id" value="<?= monitor_escape($source['id']) ?>">
                                    <div class="source-config-heading">
                                        <strong data-source-title><?= monitor_escape($source['name']) ?></strong>
                                        <button class="button button-ghost remove-source-button" type="button" aria-label="ลบแหล่งข้อมูล">ลบแหล่งนี้</button>
                                    </div>
                                    <div class="source-config-fields">
                                        <label class="form-field">
                                            <span>ชื่อแหล่งข้อมูล</span>
                                            <input data-field="name" type="text" value="<?= monitor_escape($source['name']) ?>" required maxlength="100" autocomplete="off">
                                        </label>
                                        <label class="form-field">
                                            <span>โฟลเดอร์ CSV</span>
                                            <input data-field="dataDirectory" type="text" value="<?= monitor_escape($source['dataDirectory']) ?>" required autocomplete="off">
                                        </label>
                                        <label class="form-field">
                                            <span>รูปแบบชื่อไฟล์</span>
                                            <input data-field="filePattern" type="text" value="<?= monitor_escape($source['filePattern']) ?>" required autocomplete="off">
                                        </label>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                        <button class="button button-secondary add-source-button" id="add-source-button" type="button">＋ เพิ่มแหล่งข้อมูล</button>
                        <p class="form-help">ใช้ <code>{date}</code> แทนวันที่รูปแบบ YYYYMMDD เช่น <code>GCOATINGFAC3_No01_{date}.csv</code> ต้องมีไฟล์รายวันอยู่ในโฟลเดอร์ และสิทธิ์ PHP อ่านโฟลเดอร์ได้</p>
                    </div>
                    <div class="form-section">
                        <div class="section-label">DASHBOARD</div>
                        <h2>การรีเฟรชข้อมูล</h2>
                        <label class="form-field compact-field" for="refresh-seconds">
                            <span>ช่วงเวลารีเฟรช (วินาที)</span>
                            <input id="refresh-seconds" name="refreshSeconds" type="number" min="5" max="300" step="1" value="<?= (int) $config['refreshSeconds'] ?>" required>
                            <small>กำหนดได้ตั้งแต่ 5 ถึง 300 วินาที</small>
                        </label>
                    </div>
                    <div class="form-section">
                        <div class="section-label">DATA EXPORT</div>
                        <h2>บันทึกข้อมูลเป็นไฟล์ TXT</h2>
                        <label class="form-field" for="export-directory">
                            <span>โฟลเดอร์บันทึกไฟล์</span>
                            <input id="export-directory" name="exportDirectory" type="text" value="<?= monitor_escape($config['exportDirectory']) ?>" required autocomplete="off">
                            <small>โฟลเดอร์นี้ต้องเขียนได้โดยบัญชีผู้ใช้ของ IIS Application Pool ไฟล์ที่ส่งออกใช้รูปแบบ tab-separated</small>
                        </label>
                    </div>
                    <div class="settings-actions">
                        <span id="settings-message" role="status" aria-live="polite"></span>
                        <button class="button button-primary" id="save-settings-button" type="submit">บันทึกการตั้งค่า</button>
                    </div>
                </form>
            </section>
            <section class="notice-card">
                <strong>หมายเหตุด้านสิทธิ์การใช้งาน</strong>
                <p>บัญชีที่รัน PHP ต้องมีสิทธิ์อ่านโฟลเดอร์ CSV และสิทธิ์เขียนโฟลเดอร์ config และ exports ควรจำกัดเว็บไซต์ด้วย IIS Authentication หรือให้เข้าถึงได้เฉพาะเครือข่ายที่เชื่อถือได้</p>
            </section>
            <footer class="page-footer">การตั้งค่าจะถูกบันทึกใน config/config.json</footer>
        </main>
    </div>
    <div class="toast" id="toast" role="status" aria-live="polite"></div>
    <script src="assets/js/settings.js"></script>
</body>
</html>
