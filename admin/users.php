<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Kiểm tra đăng nhập
checkAuth();

// Lấy thông tin người dùng
$user = getCurrentUser();

// Xử lý tìm kiếm và lọc
$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? '';
$status = '';
$sort = $_GET['sort'] ?? 'created_at_desc';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Xây dựng câu query
$where = "WHERE 1=1";
$params = [];

if ($search) {
    $where .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($role) {
    $where .= " AND role = ?";
    $params[] = $role;
}

// Xử lý sắp xếp
$orderBy = match($sort) {
    'name_asc' => 'ORDER BY name ASC',
    'name_desc' => 'ORDER BY name DESC',
    'email_asc' => 'ORDER BY email ASC',
    'email_desc' => 'ORDER BY email DESC',
    'created_at_asc' => 'ORDER BY created_at ASC',
    default => 'ORDER BY created_at DESC'
};

// Lấy danh sách người dùng
$users = $db->select(
    "SELECT u.*, 
            COUNT(DISTINCT o.id) as total_orders,
            SUM(CASE WHEN o.status = 'completed' THEN o.total_amount ELSE 0 END) as total_spent
     FROM users u
     LEFT JOIN orders o ON u.id = o.user_id
     {$where}
     GROUP BY u.id
     {$orderBy}
     LIMIT ? OFFSET ?",
    array_merge($params, [$limit, $offset])
);

// Lấy tổng số người dùng
$total = $db->selectOne(
    "SELECT COUNT(*) as total FROM users {$where}",
    $params
)['total'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý người dùng - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../Assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .role-badge {
            display: inline-block;
            background: #e74c3c;
            color: #fff;
            border-radius: 16px;
            padding: 4px 14px;
            font-size: 14px;
            font-weight: 600;
            white-space: nowrap;
            line-height: 1.5;
            margin-top: 4px;
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
                    <li>
                        <a href="orders.php">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Đơn hàng</span>
                        </a>
                    </li>
                    <li class="active">
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
                        <h2>Quản lý người dùng</h2>
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
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tìm kiếm người dùng...">
                            </div>
                            
                            <div class="form-group">
                                <select name="role">
                                    <option value="">Tất cả vai trò</option>
                                    <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Quản trị viên</option>
                                    <option value="customer" <?php echo $role === 'customer' ? 'selected' : ''; ?>>Khách hàng</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <select name="sort">
                                    <option value="created_at_desc" <?php echo $sort === 'created_at_desc' ? 'selected' : ''; ?>>Mới nhất</option>
                                    <option value="created_at_asc" <?php echo $sort === 'created_at_asc' ? 'selected' : ''; ?>>Cũ nhất</option>
                                    <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Tên A-Z</option>
                                    <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Tên Z-A</option>
                                    <option value="email_asc" <?php echo $sort === 'email_asc' ? 'selected' : ''; ?>>Email A-Z</option>
                                    <option value="email_desc" <?php echo $sort === 'email_desc' ? 'selected' : ''; ?>>Email Z-A</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn-filter">
                                <i class="fas fa-filter"></i>
                                Lọc
                            </button>
                        </form>
                        
                        <a href="user-form.php" class="btn-add">
                            <i class="fas fa-plus"></i>
                            Thêm người dùng
                        </a>
                    </div>
                    
                    <!-- Users Table -->
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Thông tin</th>
                                    <th>Vai trò</th>
                                    <th>Đơn hàng</th>
                                    <th>Tổng chi tiêu</th>
                                    <th>Ngày tạo</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?php echo $u['id']; ?></td>
                                    <td>
                                        <div class="user-info">
                                            <img src="<?php echo !empty($u['avatar']) ? '../uploads/' . $u['avatar'] : '../Assets/images/default-avatar.png'; ?>" 
                                                 alt="<?php echo htmlspecialchars($u['name']); ?>"
                                                 class="user-avatar">
                                            <div class="user-details">
                                                <div class="user-name"><?php echo htmlspecialchars($u['name']); ?></div>
                                                <div class="user-contact">
                                                    <span><i class="fas fa-envelope"></i> <?php echo $u['email']; ?></span>
                                                    <?php if (!empty($u['phone'])): ?>
                                                    <span><i class="fas fa-phone"></i> <?php echo $u['phone']; ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="role-badge <?php echo $u['role']; ?>">
                                            <?php echo $u['role'] === 'admin' ? 'Quản trị viên' : 'Khách hàng'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="orders.php?user_id=<?php echo $u['id']; ?>" class="order-count">
                                            <?php echo $u['total_orders']; ?> đơn
                                        </a>
                                    </td>
                                    <td>
                                        <?php if ($u['total_spent']): ?>
                                        <span class="total-spent"><?php echo number_format($u['total_spent']); ?> VNĐ</span>
                                        <?php else: ?>
                                        <span class="no-spent">Chưa có</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($u['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="user-form.php?id=<?php echo $u['id']; ?>" class="btn-edit" title="Sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($u['id'] !== $user['id']): ?>
                                            <button class="btn-delete" data-id="<?php echo $u['id']; ?>" data-type="users" title="Xóa">
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
                            echo '<a href="?page=' . ($currentPage - 1) . '&search=' . urlencode($search) . '&role=' . $role . '&sort=' . $sort . '" class="page-link">';
                            echo '<i class="fas fa-chevron-left"></i>';
                            echo '</a>';
                        }
                        
                        // Page numbers
                        for ($i = max(1, $currentPage - $range); $i <= min($totalPages, $currentPage + $range); $i++) {
                            $active = $i === $currentPage ? 'active' : '';
                            echo '<a href="?page=' . $i . '&search=' . urlencode($search) . '&role=' . $role . '&sort=' . $sort . '" class="page-link ' . $active . '">' . $i . '</a>';
                        }
                        
                        // Next button
                        if ($currentPage < $totalPages) {
                            echo '<a href="?page=' . ($currentPage + 1) . '&search=' . urlencode($search) . '&role=' . $role . '&sort=' . $sort . '" class="page-link">';
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
</body>
</html> 