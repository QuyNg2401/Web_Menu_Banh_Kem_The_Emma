<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

checkAuth();
$user = getCurrentUser();

// Xác định tuần hiện tại
$today = date('Y-m-d');
$startOfWeek = date('Y-m-d', strtotime('monday this week', strtotime($today)));
$days = [];
for ($i = 0; $i < 7; $i++) {
    $days[] = date('Y-m-d', strtotime("$startOfWeek +$i day"));
}

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

// Lấy dữ liệu chấm công tuần này
$attendance = $db->select(
    "SELECT date FROM attendance WHERE user_id = ? AND date BETWEEN ? AND ?",
    [$user['id'], $days[0], $days[6]]
);
$attendanceDays = array_column($attendance, 'date');

$weekdays = ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ nhật'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chấm công - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../Assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        .checked {
            color: #2ecc71;
            font-weight: 600;
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
                    <li><a href="products.php"><i class="fas fa-box"></i><span>Sản phẩm</span></a></li>
                    <li><a href="orders.php"><i class="fas fa-shopping-cart"></i><span>Đơn hàng</span></a></li>
                    <li><a href="users.php"><i class="fas fa-users"></i><span>Nhân viên</span></a></li>
                    <li><a href="customers.php"><i class="fas fa-user"></i><span>Khách hàng</span></a></li>
                    <li><a href="categories.php"><i class="fas fa-tags"></i><span>Danh mục</span></a></li>
                    <li><a href="inventory.php"><i class="fas fa-warehouse"></i><span>Quản lý kho</span></a></li>
                    <li><a href="bcpt.php"><i class="fas fa-chart-bar"></i><span>Thống kê & Báo cáo</span></a></li>
                    <li><a href="attendance.php" class="active"><i class="fas fa-calendar-check"></i><span>Chấm công</span></a></li>
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
                    <h2>Chấm công tuần này</h2>
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
                    <div class="table-responsive">
                        <table class="attendance-table">
                            <thead>
                                <tr>
                                    <?php foreach ($weekdays as $i => $wd): ?>
                                        <th><?php echo $wd; ?><br><span style="font-size:12px;color:#888;"><?php echo date('d/m', strtotime($days[$i])); ?></span></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <?php foreach ($days as $i => $date): ?>
                                        <td>
                                            <?php if (in_array($date, $attendanceDays)): ?>
                                                <span class="checked"><i class="fas fa-check-circle"></i> Đã chấm</span>
                                            <?php elseif ($date > date('Y-m-d')): ?>
                                                <span style="color:#aaa;">Chưa tới</span>
                                            <?php else: ?>
                                                <form method="POST" style="margin:0;">
                                                    <input type="hidden" name="date" value="<?php echo $date; ?>">
                                                    <button type="submit" class="btn-checkin">Chấm công</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="../Assets/js/admin.js"></script>
    <script>
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
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
</body>
</html> 