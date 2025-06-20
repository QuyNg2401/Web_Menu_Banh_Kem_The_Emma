<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Kiểm tra đăng nhập
checkAuth();

// Lấy thông tin người dùng
$user = getCurrentUser();

// Lấy thống kê
$stats = [
    'total_orders' => $db->selectOne("SELECT COUNT(*) as total FROM orders")['total'],
    'total_products' => $db->selectOne("SELECT COUNT(*) as total FROM products")['total'],
    'total_users' => $db->selectOne("SELECT COUNT(*) as total FROM users WHERE role = 'customer'")['total'],
    'total_revenue' => $db->selectOne("SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed'")['total'] ?? 0
];

// Lấy đơn hàng mới nhất
$latestOrders = $db->select(
    "SELECT o.*, c.name as customer_name 
    FROM orders o 
    LEFT JOIN customers c ON o.customer_id = c.id 
    ORDER BY o.created_at DESC 
    LIMIT 5"
);

// Lấy sản phẩm bán chạy
$topProducts = $db->select(
    "SELECT p.*, COUNT(oi.id) as order_count 
    FROM products p 
    LEFT JOIN order_items oi ON p.id = oi.product_id 
    GROUP BY p.id 
    ORDER BY order_count DESC 
    LIMIT 5"
);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../Assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    .status-badge {
        font-size: 0.95rem;
        padding: 6px 14px;
        border-radius: 14px;
        font-weight: 500;
        display: inline-block;
        min-width: 70px;
        text-align: center;
        line-height: 1.2;
    }
    @media (max-width: 768px) {
        .status-badge {
            font-size: 0.85rem;
            padding: 5px 10px;
            min-width: 50px;
        }
    }
    @media (max-width: 546px) {
        .status-badge {
            font-size: 0.75rem;
            padding: 3px 7px;
            min-width: 36px;
        }
    }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
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
                    <li class="active">
                        <a href="index.php">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="categories.php">
                            <i class="fas fa-tags"></i>
                            <span>Danh mục</span>
                        </a>
                    </li>
                    <li>
                        <a href="products.php">
                            <i class="fas fa-box"></i>
                            <span>Sản phẩm</span>
                        </a>
                    </li>
                    <li>
                        <a href="orders.php">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Đơn hàng</span>
                        </a>
                    </li>
                    <li>
                        <a href="customers.php">
                            <i class="fas fa-user"></i>
                            <span>Khách hàng</span>
                        </a>
                    </li>
                    <li>
                        <a href="users.php">
                            <i class="fas fa-users"></i>
                            <span>Nhân viên</span>
                        </a>
                    </li>
                    <li>
                        <a href="attendance.php">
                            <i class="fas fa-calendar-check"></i>
                            <span>Chấm công</span>
                        </a>
                    </li>
                    <li>
                        <a href="inventory.php">
                            <i class="fas fa-warehouse"></i>
                            <span>Quản lý kho</span>
                        </a>
                    </li>
                    <li>
                        <a href="bcpt.php">
                            <i class="fas fa-chart-bar"></i>
                            <span>Thống kê & Báo cáo</span>
                        </a>
                    </li>
                    <li>
                        <a href="settings.php">
                            <i class="fas fa-cog"></i>
                            <span>Cài đặt</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <a href="../api/auth/index.php?action=logout" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Đăng xuất</span>
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <div class="header-left">
                    <button class="menu-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h2>Dashboard</h2>
                </div>
                
                <div class="header-right">
                    <div class="user-menu">
                        <span><?php echo $user['name']; ?></span>
                    </div>
                </div>
            </header>
            
            <div class="dashboard">
                <!-- Thống kê -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Tổng đơn hàng</h3>
                            <p><?php echo number_format($stats['total_orders']); ?></p>
                            <div class="stat-trend">Tăng 0%</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Tổng sản phẩm</h3>
                            <p><?php echo number_format($stats['total_products']); ?></p>
                            <div class="stat-trend">Tăng 0%</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Tổng khách hàng</h3>
                            <p><?php echo number_format($stats['total_users']); ?></p>
                            <div class="stat-trend">Giảm 0%</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Doanh thu</h3>
                            <p><?php echo number_format($stats['total_revenue']); ?> VNĐ</p>
                            <div class="stat-trend">Tăng 0%</div>
                        </div>
                    </div>
                </div>
                
                <!-- Đơn hàng mới nhất và Sản phẩm bán chạy -->
                <div class="dashboard-row">
                    <div class="dashboard-section">
                        <div class="section-header">
                            <h3>Đơn hàng mới nhất</h3>
                            <a href="orders.php" class="view-all">Xem tất cả</a>
                        </div>
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Khách hàng</th>
                                        <th>Tổng tiền</th>
                                        <th>Trạng thái</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($latestOrders as $order): ?>
                                    <?php if ($order['status'] === 'completed') continue; ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td><?php echo $order['customer_name']; ?></td>
                                        <td><?php echo number_format($order['total_amount']); ?> VNĐ</td>
                                        <td>
                                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                                <?php echo ORDER_STATUS[$order['status']]; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="btn-view">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="dashboard-section">
                        <div class="section-header">
                            <h3>Sản phẩm bán chạy</h3>
                            <a href="products.php" class="view-all">Xem tất cả</a>
                        </div>
                        <div class="table-responsive">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tên sản phẩm</th>
                                        <th>Giá</th>
                                        <th>Đã bán</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topProducts as $product): ?>
                                    <tr>
                                        <td><?php echo $product['id']; ?></td>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo number_format($product['price']); ?> VNĐ</td>
                                        <td><?php echo $product['order_count']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../Assets/js/admin.js"></script>
    <script>
    // Responsive sidebar cho mobile/tablet
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