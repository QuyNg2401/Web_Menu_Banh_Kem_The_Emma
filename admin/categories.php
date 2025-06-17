<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
checkAuth();

// Xử lý thêm/sửa/xóa
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;
$name = '';
$errors = [];
$success = '';

// Thêm danh mục
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        $errors[] = 'Vui lòng nhập tên danh mục.';
    } else {
        $db->insert('categories', [
            'name' => $name,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        $success = 'Thêm danh mục thành công!';
        $name = '';
    }
}

// Sửa danh mục
if ($action === 'edit' && $id) {
    $category = $db->selectOne('SELECT * FROM categories WHERE id = ?', [$id]);
    if (!$category) {
        header('Location: categories.php');
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $errors[] = 'Vui lòng nhập tên danh mục.';
        } else {
            $db->update('categories', ['name' => $name, 'updated_at' => date('Y-m-d H:i:s')], ['id' => $id]);
            $success = 'Cập nhật danh mục thành công!';
            $category['name'] = $name;
        }
    } else {
        $name = $category['name'];
    }
}

// Xóa danh mục
if ($action === 'delete' && $id) {
    $db->delete('categories', 'id = ?', [$id]);
    header('Location: categories.php');
    exit;
}

// Lấy danh sách danh mục
$categories = $db->select('SELECT * FROM categories ORDER BY created_at DESC');
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý danh mục - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../Assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="admin-container">
    <!-- Sidebar giống các trang admin khác -->
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
                        <span>Nhân viên</span>
                    </a>
                </li>
                <li>
                    <a href="customers.php">
                        <i class="fas fa-user"></i>
                        <span>Khách hàng</span>
                    </a>
                </li>
                <li class="active">
                    <a href="categories.php">
                        <i class="fas fa-tags"></i>
                        <span>Danh mục</span>
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
    <!-- Main Content -->-
    <main class="main-content">
        <header class="main-header">
            <div class="header-left">
                <button class="menu-toggle"><i class="fas fa-bars"></i></button>
                <h2>Quản lý danh mục</h2>
            </div>
            <div class="header-right">
                <div class="user-menu">
                    <span><?php echo $user['name']; ?></span>
                </div>
            </div>
        </header>
        <div class="content-inner">
            
            <div class="content-wrapper">
                <div class="form-container" style="max-width: 500px; margin-bottom: 32px;">
                    <h3><?php echo $action === 'edit' ? 'Sửa' : 'Thêm'; ?> danh mục</h3>
                    <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
                    <?php if ($errors): ?><div class="alert alert-danger"><?php echo implode('<br>', $errors); ?></div><?php endif; ?>
                    <form method="POST">
                        <div class="form-group">
                            <label for="name">Tên danh mục</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-submit">
                                <i class="fas fa-save"></i> <?php echo $action === 'edit' ? 'Cập nhật' : 'Thêm mới'; ?>
                            </button>
                            <?php if ($action === 'edit'): ?>
                            <a href="categories.php" class="btn-cancel"><i class="fas fa-times"></i> Hủy</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên danh mục</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><?php echo $cat['id']; ?></td>
                                <td><?php echo htmlspecialchars($cat['name']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($cat['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="categories.php?action=edit&id=<?php echo $cat['id']; ?>" class="btn-edit" title="Sửa"><i class="fas fa-edit"></i></a>
                                        <a href="categories.php?action=delete&id=<?php echo $cat['id']; ?>" class="btn-delete" title="Xóa" onclick="return confirm('Bạn có chắc muốn xóa?');"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
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