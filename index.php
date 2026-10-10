<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/monitor.php';

$config = monitor_get_config();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#10151f">
    <title>Raspberry Monitor | Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body data-refresh-seconds="<?= (int) $config['refreshSeconds'] ?>" data-history-days="<?= (int) $config['historyDays'] ?>">
    <div class="app-shell">
        <aside class="sidebar">
            <a class="brand" href="index.php" aria-label="Raspberry Monitor หน้าหลัก">
                <span class="brand-mark">R</span>
                <span><strong>raspberry</strong><small>MONITORING SYSTEM</small></span>
            </a>
            <div class="sidebar-caption">เมนูหลัก</div>
            <nav class="main-nav" aria-label="เมนูหลัก">
                <a class="nav-link active" href="index.php"><span class="nav-icon">◫</span> ภาพรวม</a>
                <a class="nav-link" href="settings.php"><span class="nav-icon">⚙</span> ตั้งค่าระบบ</a>
                <a class="nav-link" href="legacy_index.php"><span class="nav-icon">↗</span> ระบบ SMT เดิม</a>
            </nav>
            <div class="sidebar-bottom">
                <span class="live-dot"></span>
                <span>ระบบมอนิเตอร์พร้อมทำงาน</span>
                <small>CSV DATA MONITOR</small>
            </div>
        </aside>

        <main class="main-content">
            <header class="topbar">
                <div>
                    <div class="eyebrow">FACTORY DATA / OVERVIEW</div>
                    <h1>ภาพรวมข้อมูล</h1>
                    <p class="page-subtitle">ติดตามข้อมูลเรียลไทม์ พร้อมดูประวัติย้อนหลัง <?= (int) $config['historyDays'] ?> วัน</p>
                </div>
                <div class="topbar-actions">
                    <div class="connection-pill" id="connection-status" data-state="loading">
                        <span class="status-dot"></span><span id="connection-label">กำลังเชื่อมต่อ</span>
                    </div>
                    <button class="button button-secondary" id="refresh-button" type="button">
                        <span aria-hidden="true">↻</span> รีเฟรช
                    </button>
                    <button class="button button-primary" id="export-button" type="button" title="ส่งออกข้อมูล 30 วันของกราฟที่เลือก">
                        <span aria-hidden="true">↓</span> ส่งออกกราฟที่เลือก
                    </button>
                </div>
            </header>

            <section class="source-strip" aria-label="สถานะแหล่งข้อมูล">
                <div class="source-icon" aria-hidden="true">▤</div>
                <div class="source-copy">
                    <span class="section-label">สถานะแหล่งข้อมูล</span>
                    <strong id="source-file">กำลังเชื่อมต่อแหล่งข้อมูล...</strong>
                    <small id="source-updated">กำลังโหลดไฟล์ย้อนหลัง</small>
                </div>
                <div class="source-refresh">
                    <span class="live-dot"></span>
                    <span>ย้อนหลัง <strong><?= (int) $config['historyDays'] ?> วัน</strong> · อัปเดตทุก <strong id="refresh-interval"><?= (int) $config['refreshSeconds'] ?></strong> วินาที</span>
                </div>
            </section>

            <section class="stats-grid" aria-label="สถิติข้อมูล">
                <article class="stat-card">
                    <div class="stat-heading"><span>ค่าสูงสุด</span><span class="stat-icon icon-violet">↗</span></div>
                    <strong class="stat-value" id="stat-max">—</strong>
                    <small id="stat-max-label">ค่าของคอลัมน์ที่เลือก</small>
                </article>
                <article class="stat-card">
                    <div class="stat-heading"><span>ค่าต่ำสุด</span><span class="stat-icon icon-blue">↘</span></div>
                    <strong class="stat-value" id="stat-min">—</strong>
                    <small id="stat-min-label">ค่าของคอลัมน์ที่เลือก</small>
                </article>
                <article class="stat-card">
                    <div class="stat-heading"><span>ค่าเฉลี่ย</span><span class="stat-icon icon-green">∿</span></div>
                    <strong class="stat-value" id="stat-average">—</strong>
                    <small id="stat-average-label">ค่าของคอลัมน์ที่เลือก</small>
                </article>
                <article class="stat-card">
                    <div class="stat-heading"><span>จำนวนข้อมูล</span><span class="stat-icon icon-orange">#</span></div>
                    <strong class="stat-value" id="stat-count">—</strong>
                    <small id="stat-category-summary">แถวข้อมูลทั้งหมด</small>
                </article>
            </section>

            <section class="chart-manager panel">
                <div>
                    <div class="section-label">CHART MANAGEMENT</div>
                    <h2>กราฟที่กำลังแสดง</h2>
                    <p>เพิ่มหรือลบกราฟได้ คลิกกราฟเพื่อเลือกข้อมูลสถิติ ตาราง และการส่งออก</p>
                </div>
                <div class="chart-manager-actions">
                    <label class="visually-hidden" for="add-chart-source">เลือกแหล่งข้อมูลเพื่อเพิ่มกราฟ</label>
                    <select id="add-chart-source" aria-label="เลือกแหล่งข้อมูลเพื่อเพิ่มกราฟ"></select>
                    <button class="button button-primary" id="add-chart-button" type="button">＋ เพิ่มกราฟ</button>
                </div>
            </section>
            <section class="charts-grid" id="charts-grid" aria-label="กราฟข้อมูลหลายแหล่ง">
                <div class="panel chart-panel chart-placeholder">กำลังโหลดแหล่งข้อมูล...</div>
            </section>

            <section class="panel table-panel">
                <div class="panel-heading">
                    <div>
                        <div class="section-label">LATEST RECORDS</div>
                        <h2>รายการข้อมูลล่าสุด</h2>
                    </div>
                    <span class="table-note" id="table-summary">แสดงรายการล่าสุด</span>
                </div>
                <div class="table-scroll">
                    <table>
                        <thead id="data-table-head"></thead>
                        <tbody id="data-table-body">
                            <tr><td class="empty-state" colspan="4">กำลังโหลดข้อมูล...</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
            <footer class="page-footer">Raspberry Monitor <span>•</span> แสดงข้อมูลจากไฟล์ CSV ที่เซิร์ฟเวอร์กำหนด</footer>
        </main>
    </div>
    <div class="toast" id="toast" role="status" aria-live="polite"></div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/hammerjs@2.0.8/hammer.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.2.0/dist/chartjs-plugin-zoom.min.js"></script>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>
