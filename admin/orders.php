<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Kiểm tra đăng nhập
checkAuth();

// Lấy thông tin người dùng
$user = getCurrentUser();

// Xử lý tìm kiếm và lọc
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$sort = $_GET['sort'] ?? 'created_at_desc';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Xây dựng câu query
$where = "WHERE 1=1";
$params = [];

if ($search) {
    $where .= " AND (o.order_code LIKE ? OR o.customer_name LIKE ? OR o.customer_phone LIKE ? OR o.customer_email LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($status) {
    $where .= " AND o.status = ?";
    $params[] = $status;
}

if ($date_from) {
    $where .= " AND DATE(o.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where .= " AND DATE(o.created_at) <= ?";
    $params[] = $date_to;
}

// Xử lý sắp xếp
$orderBy = match($sort) {
    'total_asc' => 'ORDER BY o.total_amount ASC',
    'total_desc' => 'ORDER BY o.total_amount DESC',
    'created_at_asc' => 'ORDER BY o.created_at ASC',
    default => 'ORDER BY o.created_at DESC'
};

// Lấy danh sách đơn hàng
$orders = $db->select(
    "SELECT o.*, 
            COUNT(oi.id) as total_items,
            GROUP_CONCAT(p.name SEPARATOR ', ') as product_names
     FROM orders o 
     LEFT JOIN order_items oi ON o.id = oi.order_id
     LEFT JOIN products p ON oi.product_id = p.id
     {$where}
     GROUP BY o.id
     {$orderBy}
     LIMIT ? OFFSET ?",
    array_merge($params, [$limit, $offset])
);

// Lấy tổng số đơn hàng
$total = $db->selectOne(
    "SELECT COUNT(DISTINCT o.id) as total 
     FROM orders o 
     LEFT JOIN order_items oi ON o.id = oi.order_id
     {$where}",
    $params
)['total'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn hàng - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../Assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                    <li>
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
                    <li class="active">
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
            <div class="content-inner">
                <header class="main-header">
                    <div class="header-left">
                        <button class="menu-toggle"><i class="fas fa-bars"></i></button>
                        <h2>Quản lý đơn hàng</h2>
                    </div>
                    
                    <div class="header-right">
                        <div class="user-menu">
                            <span><?php echo $user['name']; ?></span>
                        </div>
                    </div>
                </header>
                
                <div class="content-wrapper">
                    <!-- Filters -->
                    <div class="filters">
                        <form action="" method="GET" class="filter-form">
                            <div class="form-group">
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tìm kiếm đơn hàng...">
                            </div>
                            
                            <div class="form-group">
                                <select name="status">
                                    <option value="">Tất cả trạng thái</option>
                                    <?php foreach (ORDER_STATUS as $key => $value): ?>
                                    <option value="<?php echo $key; ?>" <?php echo $status === $key ? 'selected' : ''; ?>>
                                        <?php echo $value; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <input type="date" name="date_from" value="<?php echo $date_from; ?>" placeholder="Từ ngày">
                            </div>
                            
                            <div class="form-group">
                                <input type="date" name="date_to" value="<?php echo $date_to; ?>" placeholder="Đến ngày">
                            </div>
                            
                            <div class="form-group">
                                <select name="sort">
                                    <option value="created_at_desc" <?php echo $sort === 'created_at_desc' ? 'selected' : ''; ?>>Mới nhất</option>
                                    <option value="created_at_asc" <?php echo $sort === 'created_at_asc' ? 'selected' : ''; ?>>Cũ nhất</option>
                                    <option value="total_asc" <?php echo $sort === 'total_asc' ? 'selected' : ''; ?>>Tổng tiền tăng dần</option>
                                    <option value="total_desc" <?php echo $sort === 'total_desc' ? 'selected' : ''; ?>>Tổng tiền giảm dần</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn-filter">
                                <i class="fas fa-filter"></i>
                                Lọc
                            </button>
                        </form>
                    </div>
                    
                    <!-- Orders Table -->
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Mã đơn</th>
                                    <th>Khách hàng</th>
                                    <th>Sản phẩm</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                    <th>Ngày đặt</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>
                                        <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="order-code">
                                            #<?php echo $order['order_code']; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="customer-info">
                                            <div class="customer-name"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                            <div class="customer-contact">
                                                <span><i class="fas fa-phone"></i> <?php echo $order['customer_phone']; ?></span>
                                                <?php if ($order['customer_email']): ?>
                                                <span><i class="fas fa-envelope"></i> <?php echo $order['customer_email']; ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="order-items">
                                            <span class="items-count"><?php echo $order['total_items']; ?> sản phẩm</span>
                                            <div class="items-preview" title="<?php echo htmlspecialchars($order['product_names']); ?>">
                                                <?php echo htmlspecialchars($order['product_names']); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="order-total">
                                            <?php echo number_format($order['total_amount']); ?> VNĐ
                                            <?php if ($order['payment_method']): ?>
                                            <span class="payment-method">
                                                <i class="fas fa-credit-card"></i>
                                                <?php echo PAYMENT_METHODS[$order['payment_method']] ?? $order['payment_method']; ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <select class="status-select" data-id="<?php echo $order['id']; ?>" data-type="orders" data-original-status="<?php echo $order['status']; ?>">
                                            <?php foreach (ORDER_STATUS as $key => $value): ?>
                                            <option value="<?php echo $key; ?>" <?php echo $order['status'] === $key ? 'selected' : ''; ?>>
                                                <?php echo $value; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="btn-view" title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($order['status'] === 'pending'): ?>
                                            <button class="btn-delete" data-id="<?php echo $order['id']; ?>" data-type="orders" title="Xóa">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total > $limit): ?>
                    <div class="pagination">
                        <?php
                        $totalPages = ceil($total / $limit);
                        $currentPage = $page;
                        $range = 2;
                        
                        // Previous button
                        if ($currentPage > 1) {
                            echo '<a href="?page=' . ($currentPage - 1) . '&search=' . urlencode($search) . '&status=' . $status . '&date_from=' . $date_from . '&date_to=' . $date_to . '&sort=' . $sort . '" class="page-link">';
                            echo '<i class="fas fa-chevron-left"></i>';
                            echo '</a>';
                        }
                        
                        // Page numbers
                        for ($i = max(1, $currentPage - $range); $i <= min($totalPages, $currentPage + $range); $i++) {
                            $active = $i === $currentPage ? 'active' : '';
                            echo '<a href="?page=' . $i . '&search=' . urlencode($search) . '&status=' . $status . '&date_from=' . $date_from . '&date_to=' . $date_to . '&sort=' . $sort . '" class="page-link ' . $active . '">' . $i . '</a>';
                        }
                        
                        // Next button
                        if ($currentPage < $totalPages) {
                            echo '<a href="?page=' . ($currentPage + 1) . '&search=' . urlencode($search) . '&status=' . $status . '&date_from=' . $date_from . '&date_to=' . $date_to . '&sort=' . $sort . '" class="page-link">';
                            echo '<i class="fas fa-chevron-right"></i>';
                            echo '</a>';
                        }
                        ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../Assets/js/admin.js"></script>
    <script>
    // Responsive sidebar giống index.php
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    menuToggle && menuToggle.addEventListener('click', function() {
        sidebar.classList.toggle('active');
    });
    // Đóng sidebar khi click ra ngoài (mobile)
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 1024 && sidebar.classList.contains('active')) {
            if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        }
    });
    </script>
</body>
</html> 