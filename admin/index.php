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
    "SELECT o.*, u.name as customer_name 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
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
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1><?php echo SITE_NAME; ?></h1>
                <p>Admin Panel</p>
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
                        <a href="users.php">
                            <i class="fas fa-users"></i>
                            <span>Người dùng</span>
                        </a>
                    </li>
                    <li>
                        <a href="categories.php">
                            <i class="fas fa-tags"></i>
                            <span>Danh mục</span>
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
                        <img src="<?php echo $user['avatar'] ?? '../Assets/images/default-avatar.png'; ?>" alt="Avatar">
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
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Tổng sản phẩm</h3>
                            <p><?php echo number_format($stats['total_products']); ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Tổng khách hàng</h3>
                            <p><?php echo number_format($stats['total_users']); ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-info">
                            <h3>Doanh thu</h3>
                            <p><?php echo number_format($stats['total_revenue']); ?> VNĐ</p>
                        </div>
                    </div>
                </div>
                
                <!-- Đơn hàng mới nhất -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h3>Đơn hàng mới nhất</h3>
                        <a href="orders.php" class="view-all">Xem tất cả</a>
                    </div>
                    
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Khách hàng</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày đặt</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($latestOrders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo $order['customer_name']; ?></td>
                                    <td><?php echo number_format($order['total_amount']); ?> VNĐ</td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php echo ORDER_STATUS[$order['status']]; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
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
                
                <!-- Sản phẩm bán chạy -->
                <div class="dashboard-section">
                    <div class="section-header">
                        <h3>Sản phẩm bán chạy</h3>
                        <a href="products.php" class="view-all">Xem tất cả</a>
                    </div>
                    
                    <div class="products-grid">
                        <?php foreach ($topProducts as $product): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="<?php echo $product['image'] ? '../uploads/' . $product['image'] : '../Assets/images/no-image.png'; ?>" alt="<?php echo $product['name']; ?>">
                            </div>
                            <div class="product-info">
                                <h4><?php echo $product['name']; ?></h4>
                                <p class="price"><?php echo number_format($product['price']); ?> VNĐ</p>
                                <p class="sales">Đã bán: <?php echo $product['order_count']; ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../Assets/js/admin.js"></script>
</body>
</html> 