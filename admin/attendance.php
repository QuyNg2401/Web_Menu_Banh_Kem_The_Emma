<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

checkAuth();
$user = getCurrentUser();

// Xác định tháng/năm hiện tại hoặc lấy từ GET
$month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$firstDayOfMonth = date('Y-m-01', strtotime("$year-$month-01"));
$lastDayOfMonth = date('Y-m-t', strtotime($firstDayOfMonth));
$startWeekDay = date('N', strtotime($firstDayOfMonth)); // 1=Thứ 2, 7=Chủ nhật
$daysInMonth = date('t', strtotime($firstDayOfMonth));

// Tạo mảng các ngày trong tháng dạng lịch
$calendar = [];
$week = [];
$dayCounter = 1;
for ($i = 1; $i < $startWeekDay; $i++) {
    $week[] = null;
}
while ($dayCounter <= $daysInMonth) {
    $week[] = $dayCounter;
    if (count($week) == 7) {
        $calendar[] = $week;
        $week = [];
    }
    $dayCounter++;
}
if (count($week) > 0) {
    while (count($week) < 7) $week[] = null;
    $calendar[] = $week;
}

// Lấy danh sách nhân viên
$users = $db->select("SELECT id, name FROM users WHERE role = 'user'");

// Lấy dữ liệu chấm công tháng này
$attendance = $db->select(
    "SELECT date FROM attendance WHERE user_id = ? AND date BETWEEN ? AND ?",
    [$user['id'], $firstDayOfMonth, $lastDayOfMonth]
);
$attendanceDays = array_column($attendance, 'date');

// Sử dụng user_id từ GET nếu có, ngược lại lấy user hiện tại
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : $user['id'];

// Lấy dữ liệu ca làm chi tiết tháng này
$details = $db->select(
    "SELECT date, time_from, time_to FROM attendance_details WHERE user_id = ? AND date BETWEEN ? AND ?",
    [$user_id, $firstDayOfMonth, $lastDayOfMonth]
);
$detailsMap = [];
foreach ($details as $row) {
    $detailsMap[$row['date']] = [$row['time_from'], $row['time_to']];
}

$weekdays = ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ nhật'];

// Xử lý chấm công
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['date'])) {
    $date = $_POST['date'];
    // Kiểm tra đã chấm công chưa
    $exists = $db->selectOne("SELECT id FROM attendance WHERE user_id = ? AND date = ?", [$user['id'], $date]);
    if (!$exists) {
        $db->insert('attendance', [
            'user_id' => $user['id'],
            'date' => $date,
            'checked_at' => date('Y-m-d H:i:s')
        ]);
        $message = 'Chấm công thành công cho ngày ' . date('d/m/Y', strtotime($date));
    } else {
        $message = 'Bạn đã chấm công ngày này rồi!';
    }
}

// Tính tổng giờ đã làm trong tháng
$totalHours = 0;
foreach ($details as $row) {
    $from = strtotime($row['time_from']);
    $to = strtotime($row['time_to']);
    if ($to > $from) {
        $totalHours += ($to - $from) / 3600;
    }
}
// Lấy lương/giờ của nhân viên
$userInfo = $db->selectOne("SELECT hourly_rate FROM users WHERE id = ?", [$user_id]);
$hourlyRate = $userInfo ? floatval($userInfo['hourly_rate']) : 0;
$totalSalary = $totalHours * $hourlyRate;

// Xác định tuần hiện tại (tính từ Thứ 2)
$today = date('Y-m-d');
$startOfWeek = date('Y-m-d', strtotime('monday this week', strtotime($today)));
$weekDates = [];
for ($i = 0; $i < 7; $i++) {
    $weekDates[] = date('Y-m-d', strtotime("$startOfWeek +$i day"));
}
// Lấy lịch làm việc tuần này
$weekDetails = $db->select(
    "SELECT date, time_from, time_to FROM attendance_details WHERE user_id = ? AND date BETWEEN ? AND ?",
    [$user_id, $weekDates[0], $weekDates[6]]
);
$weekDetailsMap = [];
foreach ($weekDetails as $row) {
    $weekDetailsMap[$row['date']] = [$row['time_from'], $row['time_to']];
}
// Lấy lịch làm việc tuần này của tất cả nhân viên
$allWeekDetails = $db->select(
    "SELECT user_id, date, time_from, time_to FROM attendance_details WHERE date BETWEEN ? AND ?",
    [$weekDates[0], $weekDates[6]]
);
$allWeekDetailsMap = [];
foreach ($allWeekDetails as $row) {
    $allWeekDetailsMap[$row['date']][$row['user_id']] = [$row['time_from'], $row['time_to']];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chấm công - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../Assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" integrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <style>
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 auto;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        }
        .attendance-table th, .attendance-table td {
            padding: 16px 10px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }
        .attendance-table th {
            background: #f7f7f7;
            font-weight: 600;
            color: #333;
        }
        .attendance-table tr:last-child td {
            border-bottom: none;
        }
        .btn-checkin {
            background: #2ecc71;
            color: #fff;
            border: none;
            padding: 7px 18px;
            border-radius: 6px;
            font-size: 15px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-checkin:disabled {
            background: #aaa;
            cursor: not-allowed;
        }
        .month-schedule-table td span.checked, .week-schedule-table td span.checked {
            color: #2ecc71 !important;
            font-weight: 600 !important;
            font-style: normal !important;
        }
        @media (max-width: 768px) {
            .attendance-table th, .attendance-table td {
                padding: 10px 4px;
                font-size: 14px;
            }
        }
        @media (max-width: 500px) {
            .attendance-table th, .attendance-table td {
                padding: 7px 2px;
                font-size: 12px;
            }
        }
        /* Bảng lịch làm việc tuần này */
        .week-schedule-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 16px rgba(229,188,111,0.10);
            margin: 0 auto 24px auto;
        }
        .week-schedule-table th, .week-schedule-table td {
            padding: 18px 14px;
            text-align: center;
            border-bottom: 1px solid #f3e3c2;
            font-size: 1.08rem;
        }
        .week-schedule-table th {
            background: #f8f3e7;
            color: #b88a2b;
            font-weight: bold;
            font-size: 1.1rem;
            border-bottom: 2px solid #e5bc6f;
        }
        .week-schedule-table tr:last-child td {
            border-bottom: none;
        }
        .week-schedule-table td {
            color: #222;
            font-weight: 500;
        }
        .week-schedule-table td span {
            color: #bbb !important;
            font-weight: 400;
            font-style: italic;
        }
        @media (max-width: 900px) {
            .week-schedule-table th, .week-schedule-table td {
                padding: 10px 4px;
                font-size: 0.98rem;
            }
            .attendance-summary {
                min-width: 160px;
                font-size: 1rem;
                padding: 12px 10px;
            }
        }
        @media (max-width: 700px) {
            .attendance-filter-form {
                flex-direction: row !important;
                align-items: center !important;
                gap: 6px;
                padding: 8px 2px;
                margin-left: 0;
                text-align: left !important;
                flex-wrap: nowrap;
                width: 100%;
            }
            .attendance-filter-form label,
            .attendance-filter-form input,
            .attendance-filter-form select,
            .attendance-filter-form button {
                text-align: left !important;
                margin-left: 0 !important;
                margin-right: 0 !important;
                width: auto !important;
                display: inline-block;
            }
            .attendance-filter-summary-row {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 10px;
            }
            .attendance-summary {
                margin-left: 0 !important;
                margin-top: 10px !important;
                width: 100%;
                min-width: 0;
                box-sizing: border-box;
                text-align: left;
            }
            .attendance-summary div { font-size: 0.98rem; }
            .month-schedule-table, .week-schedule-table {
                min-width: 500px;
            }
            .month-schedule-table-wrapper, .week-schedule-table-wrapper {
                overflow-x: auto;
                width: 100%;
            }
            .attendance-summary {
                font-size: 0.98rem;
            }
        }
        @media (max-width: 600px) {
            .month-schedule-table th, .month-schedule-table td,
            .week-schedule-table th, .week-schedule-table td {
                padding: 7px 2px;
                font-size: 0.92rem;
            }
            .attendance-summary {
                padding: 8px 4px;
                font-size: 0.95rem;
            }
        }
        @media (max-width: 700px) {
            /* Filter + summary xếp dọc, căn giữa */
            .attendance-filter-form, .attendance-summary {
                width: 100%;
                min-width: 0;
                box-sizing: border-box;
            }
            .attendance-summary {
                margin-top: 10px !important;
                margin-bottom: 0;
                text-align: left;
            }
            .attendance-summary div { font-size: 0.98rem; }
        }
        .week-schedule-title {
            font-size: 1.25rem;
            font-weight: bold;
            color: #b88a2b;
            margin-bottom: 18px;
            letter-spacing: 0.01em;
            text-shadow: 0 1px 0 #f8f3e7, 0 2px 8px rgba(229,188,111,0.08);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        /* Bảng lịch tháng */
        .month-schedule-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 16px rgba(229,188,111,0.10);
            margin: 0 auto 24px auto;
        }
        .month-schedule-table th, .month-schedule-table td {
            padding: 16px 10px;
            text-align: center;
            border-bottom: 1px solid #f3e3c2;
            font-size: 1.05rem;
        }
        .month-schedule-table th {
            background: #f8f3e7;
            color: #b88a2b;
            font-weight: bold;
            font-size: 1.08rem;
            border-bottom: 2px solid #e5bc6f;
        }
        .month-schedule-table tr:last-child td {
            border-bottom: none;
        }
        .month-schedule-table td {
            color: #222;
            font-weight: 500;
        }
        .month-schedule-table td span {
            color: #bbb !important;
            font-weight: 400;
            font-style: italic;
        }
        @media (max-width: 900px) {
            .month-schedule-table th, .month-schedule-table td {
                padding: 10px 4px;
                font-size: 0.98rem;
            }
        }
        @media (max-width: 600px) {
            .month-schedule-table th, .month-schedule-table td {
                padding: 7px 2px;
                font-size: 0.92rem;
            }
            .month-schedule-table {
                min-width: 600px;
            }
            .month-schedule-table-wrapper {
                overflow-x: auto;
                width: 100%;
            }
        }
        .attendance-filter-form {
            margin-bottom: 16px;
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
            background: #fff;
            border-radius: 10px;
            padding: 14px 18px;
            box-shadow: 0 2px 8px rgba(229,188,111,0.07);
        }
        .attendance-filter-form label {
            font-weight: 500;
            color: #b88a2b;
            margin-right: 2px;
        }
        .attendance-filter-form select,
        .attendance-filter-form input[type="number"] {
            font-size: 1rem;
            padding: 6px 10px;
            border-radius: 5px;
            border: 1px solid #f3e3c2;
            background: #fdf8ef;
            margin-right: 6px;
            transition: border 0.2s;
        }
        .attendance-filter-form select:focus,
        .attendance-filter-form input[type="number"]:focus {
            border: 1.5px solid #e5bc6f;
            outline: none;
        }
        .attendance-filter-form button[type="submit"] {
            background: #d49a3a;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 6px 18px;
            font-size: 1rem;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s, color 0.2s;
        }
        .attendance-filter-form button[type="submit"]:hover {
            background: #b88a2b;
            color: #fff;
        }
        .attendance-summary {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(229,188,111,0.07);
            padding: 18px 24px;
            margin-top: 18px;
            font-size: 1.13rem;
            color: #b88a2b;
            font-weight: 600;
            display: inline-block;
            min-width: 220px;
        }
        .attendance-summary b {
            color: #222;
            font-weight: bold;
        }
        .attendance-summary-row {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 8px;
            margin-bottom: 2px;
        }
        .attendance-summary-label {
            min-width: 140px;
            font-weight: bold;
            color: #222;
            text-align: left;
            display: inline-block;
        }
        .attendance-summary-value {
            color: #b88a2b;
            font-weight: bold;
            text-align: right;
            min-width: 70px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../Assets/images/logo.png" alt="Logo" class="sidebar-logo" style="height:48px;width:auto;display:inline-block;vertical-align:middle;margin-right:12px;">
                <div style="display:inline-block;vertical-align:middle;">
                    <h1 style="margin:0;"><?php echo SITE_NAME; ?></h1>
                    <p style="margin:0;">Admin Panel</p>
                </div>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="index.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
                    <li><a href="categories.php"><i class="fas fa-tags"></i><span>Danh mục</span></a></li>
                    <li><a href="products.php"><i class="fas fa-box"></i><span>Sản phẩm</span></a></li>
                    <li><a href="orders.php"><i class="fas fa-shopping-cart"></i><span>Đơn hàng</span></a></li>
                    <li><a href="customers.php"><i class="fas fa-user"></i><span>Khách hàng</span></a></li>
                    <li><a href="users.php"><i class="fas fa-users"></i><span>Nhân viên</span></a></li>
                    <li class="active"><a href="attendance.php" ><i class="fas fa-calendar-check"></i><span>Chấm công</span></a></li>
                    <li><a href="inventory.php"><i class="fas fa-warehouse"></i><span>Quản lý kho</span></a></li>
                    <li><a href="bcpt.php"><i class="fas fa-chart-bar"></i><span>Thống kê & Báo cáo</span></a></li>
                    <li><a href="settings.php"><i class="fas fa-cog"></i><span>Cài đặt</span></a></li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <a href="../api/auth/index.php?action=logout" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Đăng xuất</span>
                </a>
            </div>
        </aside>
        <main class="main-content">
            <header class="main-header">
                <div class="header-left">
                    <button class="menu-toggle"><i class="fas fa-bars"></i></button>
                    <h2>Chấm công tháng <?php echo $month . '/' . $year; ?></h2>
                </div>
                <div class="header-right">
                    <div class="user-menu">
                        <span><?php echo $user['name']; ?></span>
                    </div>
                </div>
            </header>
            <div class="content-inner">
                <div class="content-wrapper">
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-success" style="margin-bottom:18px;">
                            <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    <div style="margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                        <h3 class="week-schedule-title" style="margin-bottom:0;">Lịch làm việc tuần này (tất cả nhân viên)</h3>
                        <div class="actions" style="display:flex; gap: 10px; align-items: center;">
                             <button id="exportPdfBtn" style="background:#dc3545;color:#fff;padding:8px 18px;border-radius:6px;text-decoration:none;font-weight:bold;display:inline-flex;align-items:center;gap:6px; border: none; cursor: pointer; font-family: inherit; font-size: 1rem;">
                                <i class="fas fa-file-pdf"></i> Xuất PDF
                            </button>
                            <a href="create-week-schedule.php?start=<?php echo $weekDates[0]; ?>" style="background:#d49a3a;color:#fff;padding:8px 18px;border-radius:6px;text-decoration:none;font-weight:bold;display:inline-flex;align-items:center;gap:6px;">
                                <i class="fas fa-plus"></i> Tạo lịch làm tuần
                            </a>
                        </div>
                    </div>
                    <div class="week-schedule-table-wrapper">
                        <table class="week-schedule-table">
                            <thead>
                                <tr>
                                    <th>Nhân viên</th>
                                    <?php foreach ($weekdays as $i => $wd): ?>
                                        <th><?php echo $wd . '<br>' . date('d/m', strtotime($weekDates[$i])); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($u['name']); ?></td>
                                        <?php foreach ($weekDates as $date): ?>
                                            <td>
                                                <?php if (isset($allWeekDetailsMap[$date][$u['id']])): ?>
                                                    <?php echo substr($allWeekDetailsMap[$date][$u['id']][0],0,5) . ' - ' . substr($allWeekDetailsMap[$date][$u['id']][1],0,5); ?>
                                                <?php else: ?>
                                                    <span><i class="fas fa-times" style="color: #dc3545;"></i></span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:18px;flex-wrap:wrap;margin-bottom:18px;" class="attendance-filter-summary-row">
                        <form method="GET" class="attendance-filter-form" style="margin-bottom:0;box-shadow:none;padding:0;background:transparent;">
                            <label>Nhân viên
                                <select name="user_id" style="min-width:120px;">
                                    <?php foreach (
                                        $users as $u): ?>
                                        <option value="<?php echo $u['id']; ?>" <?php echo (isset($_GET['user_id']) && $_GET['user_id'] == $u['id']) || (!isset($_GET['user_id']) && $user['id'] == $u['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($u['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>Tháng <input type="number" name="month" min="1" max="12" value="<?php echo $month; ?>" style="width:60px;"></label>
                            <label>Năm <input type="number" name="year" min="2020" max="2100" value="<?php echo $year; ?>" style="width:80px;"></label>
                            <button type="submit">Xem</button>
                        </form>
                        <div class="attendance-summary" style="margin-top:0;">
                            <div class="attendance-summary-row">
                                <span class="attendance-summary-label">Tổng giờ đã làm:</span>
                                <span class="attendance-summary-value"><?php echo round($totalHours, 2); ?> giờ</span>
                            </div>
                            <div class="attendance-summary-row">
                                <span class="attendance-summary-label">Tổng lương:</span>
                                <span class="attendance-summary-value"><?php echo number_format($totalSalary, 0, ',', '.'); ?> VNĐ</span>
                            </div>
                        </div>
                    </div>
                    <div class="month-schedule-table-wrapper">
                        <table class="month-schedule-table">
                            <thead>
                                <tr>
                                    <?php foreach ($weekdays as $wd): ?>
                                        <th><?php echo $wd; ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($calendar as $week): ?>
                                    <tr>
                                        <?php foreach ($week as $day): ?>
                                            <td>
                                            <?php if ($day): 
                                                $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                                                if (isset($detailsMap[$date])) {
                                                    $from = substr($detailsMap[$date][0], 0, 5);
                                                    $to = substr($detailsMap[$date][1], 0, 5);
                                                    echo "<span class='checked'><i class='fas fa-check-circle'></i> $from - $to</span>";
                                                } elseif ($date > date('Y-m-d')) {
                                                    echo '<span>Chưa tới</span>';
                                                } else {
                                                    echo '<span><i class="fas fa-times" style="color: #dc3545;"></i></span>';
                                                }
                                            else:
                                                echo '';
                                            endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="../Assets/js/admin.js"></script>
    <script>
    document.getElementById('exportPdfBtn').addEventListener('click', function () {
        const tableWrapper = document.querySelector('.week-schedule-table-wrapper');
        const titleText = "Lịch làm việc tuần (<?php echo date('d/m/Y', strtotime($weekDates[0])) . ' - ' . date('d/m/Y', strtotime($weekDates[6])); ?>)";

        // Tạo một container để chứa nội dung cần in
        const printContainer = document.createElement('div');
        
        // Tạo và định dạng tiêu đề
        const titleElement = document.createElement('h2');
        titleElement.innerText = titleText;
        titleElement.style.textAlign = 'center';
        titleElement.style.marginBottom = '20px';
        titleElement.style.color = '#b88a2b';
        titleElement.style.fontFamily = 'Arial, sans-serif';
        titleElement.style.fontSize = '18px';

        // Sao chép bảng để không ảnh hưởng đến trang web gốc
        const tableClone = tableWrapper.cloneNode(true);
        
        // Thêm tiêu đề và bảng đã sao chép vào container
        printContainer.appendChild(titleElement);
        printContainer.appendChild(tableClone);
        
        const opt = {
            margin:       10, // tính bằng mm
            filename:     'lich-lam-viec-tuan.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true, logging: false },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'landscape' }
        };

        // Sử dụng html2pdf để tạo PDF từ container tạm thời
        html2pdf().from(printContainer).set(opt).save();
    });

    const menuToggle = document.querySelector('.menu-toggle');
    menuToggle && menuToggle.addEventListener('click', function(e) {
        e.stopPropagation();
        sidebar.classList.toggle('active');
    });
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 1080 && sidebar.classList.contains('active')) {
            if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        }
    });
    </script>
    <?php if (!empty($_SESSION['success'])): ?>
    <script>
        showNotification("<?php echo addslashes($_SESSION['success']); ?>", "success");
    </script>
    <?php unset($_SESSION['success']); endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
    <script>
        showNotification("<?php echo addslashes($_SESSION['error']); ?>", "error");
    </script>
    <?php unset($_SESSION['error']); endif; ?>
</body>
</html> 