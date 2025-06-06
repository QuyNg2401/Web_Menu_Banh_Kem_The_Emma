<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Kiểm tra đăng nhập
checkAuth();

// Lấy thông tin người dùng
$user = getCurrentUser();

// Xử lý tìm kiếm và lọc
$search = $_GET['search'] ?? '';
$category_id = $_GET['category_id'] ?? '';
$status = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'created_at_desc';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Xây dựng câu query
$where = "WHERE 1=1";
$params = [];

if ($search) {
    $where .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($category_id) {
    $where .= " AND p.category_id = ?";
    $params[] = $category_id;
}

if ($status) {
    $where .= " AND p.status = ?";
    $params[] = $status;
}

// Xử lý sắp xếp
$orderBy = match($sort) {
    'name_asc' => 'ORDER BY p.name ASC',
    'name_desc' => 'ORDER BY p.name DESC',
    'price_asc' => 'ORDER BY p.price ASC',
    'price_desc' => 'ORDER BY p.price DESC',
    'created_at_asc' => 'ORDER BY p.created_at ASC',
    default => 'ORDER BY p.created_at DESC'
};

// Lấy danh sách sản phẩm
$products = $db->select(
    "SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    {$where} 
    {$orderBy} 
    LIMIT ? OFFSET ?",
    array_merge($params, [$limit, $offset])
);

// Lấy tổng số sản phẩm
$total = $db->selectOne(
    "SELECT COUNT(*) as total FROM products p {$where}",
    $params
)['total'];

// Lấy danh sách danh mục
$categories = $db->select("SELECT * FROM categories ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sản phẩm - <?php echo SITE_NAME; ?></title>
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
                    <li>
                        <a href="index.php">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="active">
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
                    <h2>Quản lý sản phẩm</h2>
                </div>
                
                <div class="header-right">
                    <div class="user-menu">
                        <img src="<?php echo $user['avatar'] ?? '../Assets/images/default-avatar.png'; ?>" alt="Avatar">
                        <span><?php echo $user['name']; ?></span>
                    </div>
                </div>
            </header>
            
            <div class="content-wrapper">
                <!-- Filters -->
                <div class="filters">
                    <form action="" method="GET" class="filter-form">
                        <div class="form-group">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tìm kiếm sản phẩm...">
                        </div>
                        
                        <div class="form-group">
                            <select name="category_id">
                                <option value="">Tất cả danh mục</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <select name="status">
                                <option value="">Tất cả trạng thái</option>
                                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Đang bán</option>
                                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Ngừng bán</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <select name="sort">
                                <option value="created_at_desc" <?php echo $sort === 'created_at_desc' ? 'selected' : ''; ?>>Mới nhất</option>
                                <option value="created_at_asc" <?php echo $sort === 'created_at_asc' ? 'selected' : ''; ?>>Cũ nhất</option>
                                <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Tên A-Z</option>
                                <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Tên Z-A</option>
                                <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Giá tăng dần</option>
                                <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Giá giảm dần</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn-filter">
                            <i class="fas fa-filter"></i>
                            Lọc
                        </button>
                    </form>
                    
                    <a href="product-form.php" class="btn-add">
                        <i class="fas fa-plus"></i>
                        Thêm sản phẩm
                    </a>
                </div>
                
                <!-- Products Table -->
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Hình ảnh</th>
                                <th>Tên sản phẩm</th>
                                <th>Danh mục</th>
                                <th>Giá</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td>
                                    <img src="<?php echo $product['image'] ? '../uploads/' . $product['image'] : '../Assets/images/no-image.png'; ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         class="product-thumbnail">
                                </td>
                                <td>
                                    <div class="product-name">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                        <?php if ($product['featured']): ?>
                                        <span class="featured-badge">Nổi bật</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                <td>
                                    <?php if ($product['sale_price']): ?>
                                    <div class="price-sale">
                                        <span class="original-price"><?php echo number_format($product['price']); ?> VNĐ</span>
                                        <span class="sale-price"><?php echo number_format($product['sale_price']); ?> VNĐ</span>
                                    </div>
                                    <?php else: ?>
                                    <span class="price"><?php echo number_format($product['price']); ?> VNĐ</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <select class="status-select" data-id="<?php echo $product['id']; ?>" data-type="products" data-original-status="<?php echo $product['status']; ?>">
                                        <option value="active" <?php echo $product['status'] === 'active' ? 'selected' : ''; ?>>Đang bán</option>
                                        <option value="inactive" <?php echo $product['status'] === 'inactive' ? 'selected' : ''; ?>>Ngừng bán</option>
                                    </select>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($product['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="product-form.php?id=<?php echo $product['id']; ?>" class="btn-edit" title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn-delete" data-id="<?php echo $product['id']; ?>" data-type="products" title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </button>
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
                        echo '<a href="?page=' . ($currentPage - 1) . '&search=' . urlencode($search) . '&category_id=' . $category_id . '&status=' . $status . '&sort=' . $sort . '" class="page-link">';
                        echo '<i class="fas fa-chevron-left"></i>';
                        echo '</a>';
                    }
                    
                    // Page numbers
                    for ($i = max(1, $currentPage - $range); $i <= min($totalPages, $currentPage + $range); $i++) {
                        $active = $i === $currentPage ? 'active' : '';
                        echo '<a href="?page=' . $i . '&search=' . urlencode($search) . '&category_id=' . $category_id . '&status=' . $status . '&sort=' . $sort . '" class="page-link ' . $active . '">' . $i . '</a>';
                    }
                    
                    // Next button
                    if ($currentPage < $totalPages) {
                        echo '<a href="?page=' . ($currentPage + 1) . '&search=' . urlencode($search) . '&category_id=' . $category_id . '&status=' . $status . '&sort=' . $sort . '" class="page-link">';
                        echo '<i class="fas fa-chevron-right"></i>';
                        echo '</a>';
                    }
                    ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script src="../Assets/js/admin.js"></script>
</body>
</html> 