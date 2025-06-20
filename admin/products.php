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

// Sau khi lấy $products, lấy thêm size và giá cho từng sản phẩm
foreach ($products as &$product) {
    $product['sizes'] = $db->select("SELECT size, price FROM product_sizes WHERE product_id = ? ORDER BY size ASC", [$product['id']]);
}
unset($product);

// Lấy tổng số sản phẩm
$total = $db->selectOne(
    "SELECT COUNT(*) as total FROM products p {$where}",
    $params
)['total'];

// Lấy danh sách danh mục
$categories = $db->select("SELECT * FROM categories ORDER BY name ASC");

// Xử lý xóa sản phẩm
if (isset($_GET['action']) && $_GET['action'] === 'delete' && !empty($_GET['id'])) {
    $db->delete('products', 'id = ?', [$_GET['id']]);
    $_SESSION['success'] = 'Xóa sản phẩm thành công!';
    header('Location: products.php');
    exit;
}
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
<body class="products-page">
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="col-md-2 col-lg-2 px-0 sidebar" id="sidebar">
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
                        <a href="categories.php">
                            <i class="fas fa-tags"></i>
                            <span>Danh mục</span>
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
        </div>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <div class="header-left">
                    <button class="menu-toggle"><i class="fas fa-bars"></i></button>
                    <h2>Quản lý sản phẩm</h2>
                </div>
                
                <div class="header-right">
                    <div class="user-menu">
                        <span><?php echo $user['name']; ?></span>
                    </div>
                </div>
            </header>
            <div class="content-inner">
                <div class="content-wrapper">
                    <!-- Filters -->
                    <div class="filters">
                        <form action="" method="GET" class="filter-form">
                            <div class="filter-row">
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
                                    <select name="sort">
                                        <option value="created_at_desc" <?php echo $sort === 'created_at_desc' ? 'selected' : ''; ?>>Mới nhất</option>
                                        <option value="created_at_asc" <?php echo $sort === 'created_at_asc' ? 'selected' : ''; ?>>Cũ nhất</option>
                                        <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Tên A-Z</option>
                                        <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Tên Z-A</option>
                                        <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Giá tăng dần</option>
                                        <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Giá giảm dần</option>
                                    </select>
                                </div>
                            </div>
                            <div class="btn-row-bottom">
                                <button type="submit" class="btn-filter">
                                    <i class="fas fa-filter"></i>
                                    Lọc
                                </button>
                                <a href="product-form.php" class="btn-add">
                                    <i class="fas fa-plus"></i>
                                    Thêm sản phẩm
                                </a>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Products Table -->
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Hình ảnh</th>
                                    <th>Tên sản phẩm</th>
                                    <th>Danh mục</th>
                                    <th>Kích thước</th>
                                    <th>Giá</th>
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
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                    <td>
                                        <?php if (!empty($product['sizes'])): ?>
                                            <select class="size-select" data-prices='<?php echo json_encode(array_column($product['sizes'], 'price', 'size')); ?>'>
                                                <?php foreach ($product['sizes'] as $i => $sz): ?>
                                                    <option value="<?php echo htmlspecialchars($sz['size']); ?>" <?php echo $i === 0 ? 'selected' : ''; ?>><?php echo htmlspecialchars($sz['size']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php else: ?>
                                            <span style="color:#aaa;">Chưa có</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="price-col">
                                        <?php if (!empty($product['sizes'])): ?>
                                            <span class="price"><?php echo number_format($product['sizes'][0]['price'], 0, '', '.'); ?> VNĐ</span>
                                        <?php else: ?>
                                            <span style="color:#aaa;">Chưa có</span>
                                        <?php endif; ?>
                                    </td>
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
                            echo '<a href="?page=' . ($currentPage - 1) . '&search=' . urlencode($search) . '&category_id=' . $category_id . '&sort=' . $sort . '" class="page-link">';
                            echo '<i class="fas fa-chevron-left"></i>';
                            echo '</a>';
                        }
                        
                        // Page numbers
                        for ($i = max(1, $currentPage - $range); $i <= min($totalPages, $currentPage + $range); $i++) {
                            $active = $i === $currentPage ? 'active' : '';
                            echo '<a href="?page=' . $i . '&search=' . urlencode($search) . '&category_id=' . $category_id . '&sort=' . $sort . '" class="page-link ' . $active . '">' . $i . '</a>';
                        }
                        
                        // Next button
                        if ($currentPage < $totalPages) {
                            echo '<a href="?page=' . ($currentPage + 1) . '&search=' . urlencode($search) . '&category_id=' . $category_id . '&sort=' . $sort . '" class="page-link">';
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
    document.querySelectorAll('.size-select').forEach(function(select) {
        select.addEventListener('change', function() {
            var prices = JSON.parse(this.getAttribute('data-prices'));
            var size = this.value;
            var price = prices[size] || 0;
            price = parseInt(price, 10);
            var priceCol = this.closest('tr').querySelector('.price-col .price');
            if(priceCol) priceCol.textContent = price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ' VNĐ';
        });
    });
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

    <!-- Modal xác nhận xóa sản phẩm -->
    <div class="modal" id="deleteProductModal" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.18);align-items:center;justify-content:center;">
        <div class="modal-dialog" style="background:#fff;padding:32px 28px 18px 28px;border-radius:12px;max-width:95vw;width:400px;box-shadow:0 8px 32px rgba(0,0,0,0.18);position:relative;">
            <h3 style="margin-top:0;">Xác nhận xóa</h3>
            <p>Bạn có chắc chắn muốn xóa sản phẩm này không?</p>
            <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:18px;">
                <button id="cancelDeleteProductBtn" class="btn-cancel">Hủy</button>
                <button id="confirmDeleteProductBtn" class="btn-submit">Xóa</button>
            </div>
        </div>
    </div>
    <!-- Modal thông báo thành công -->
    <div class="modal" id="successModal" style="display:none;position:fixed;z-index:10000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.18);align-items:center;justify-content:center;">
        <div class="modal-dialog" style="background:#fff;padding:32px 28px 18px 28px;border-radius:12px;max-width:95vw;width:350px;box-shadow:0 8px 32px rgba(0,0,0,0.18);position:relative;text-align:center;">
            <h3 style="margin-top:0;color:#28a745;"><i class="fas fa-check-circle"></i> Thành công</h3>
            <div id="successModalMsg" style="margin:18px 0 12px 0;font-size:1.1rem;"></div>
            <div style="display:flex;justify-content:center;margin-top:10px;">
                <button id="closeSuccessModal" class="btn-submit">Đóng</button>
            </div>
        </div>
    </div>
    <style>
    .btn-cancel {
        background: #eee;
        color: #333;
        border: none;
        padding: 8px 18px;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none !important;
    }
    #successModal .btn-submit {
        min-width: 80px;
        font-size: 1.08rem;
    }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let deleteId = null;
        const deleteModal = document.getElementById('deleteProductModal');
        const cancelDeleteBtn = document.getElementById('cancelDeleteProductBtn');
        const confirmDeleteBtn = document.getElementById('confirmDeleteProductBtn');

        document.querySelectorAll('.btn-delete[data-type="products"]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                deleteId = this.getAttribute('data-id');
                deleteModal.style.display = 'flex';
            });
        });

        if (cancelDeleteBtn) {
            cancelDeleteBtn.onclick = function() {
                deleteModal.style.display = 'none';
                deleteId = null;
            };
        }
        if (confirmDeleteBtn) {
            confirmDeleteBtn.onclick = function() {
                if (deleteId) {
                    window.location.href = 'products.php?action=delete&id=' + deleteId;
                }
            };
        }
    });
    </script>

    <?php if (!empty($_SESSION['success'])): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var modal = document.getElementById('successModal');
        var msg = document.getElementById('successModalMsg');
        var closeBtn = document.getElementById('closeSuccessModal');
        if (modal && msg && closeBtn) {
            msg.innerHTML = "<?php echo addslashes($_SESSION['success']); ?>";
            modal.style.display = 'flex';
            closeBtn.onclick = function() {
                modal.style.display = 'none';
            };
            window.onclick = function(event) {
                if (event.target === modal) modal.style.display = 'none';
            };
        }
    });
    </script>
    <?php unset($_SESSION['success']); endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
    <script>
        showNotification("<?php echo addslashes($_SESSION['error']); ?>", "error");
    </script>
    <?php unset($_SESSION['error']); endif; ?>
</body>
</html> 