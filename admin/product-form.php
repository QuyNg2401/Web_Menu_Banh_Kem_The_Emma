<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Kiểm tra đăng nhập
checkAuth();

// Lấy thông tin người dùng
$user = getCurrentUser();

// Lấy danh sách danh mục
$categories = $db->select("SELECT * FROM categories ORDER BY name ASC");

// Xử lý form
$id = $_GET['id'] ?? null;
$product = null;
$errors = [];
$success = false;

if ($id) {
    // Lấy thông tin sản phẩm
    $product = $db->selectOne(
        "SELECT * FROM products WHERE id = ?",
        [$id]
    );
    
    if (!$product) {
        header('Location: products.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate dữ liệu
    $name = trim($_POST['name'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $sale_price = !empty($_POST['sale_price']) ? floatval($_POST['sale_price']) : null;
    $description = trim($_POST['description'] ?? '');
    $ingredients = trim($_POST['ingredients'] ?? '');
    $sizeArr = $_POST['size'] ?? [];
    $size = is_array($sizeArr) ? implode(',', $sizeArr) : '';
    $featured = isset($_POST['featured']) ? 1 : 0;
    
    // Validate
    if (empty($name)) {
        $errors['name'] = 'Vui lòng nhập tên sản phẩm';
    }
    
    if ($category_id <= 0) {
        $errors['category_id'] = 'Vui lòng chọn danh mục';
    }
    
    if ($price <= 0) {
        $errors['price'] = 'Vui lòng nhập giá sản phẩm';
    }
    
    if ($sale_price !== null && $sale_price >= $price) {
        $errors['sale_price'] = 'Giá khuyến mãi phải nhỏ hơn giá gốc';
    }
    
    if (empty($description)) {
        $errors['description'] = 'Vui lòng nhập mô tả sản phẩm';
    }
    
    // Xử lý upload ảnh
    $image = $product['image'] ?? null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Kiểm tra định dạng
        if (!in_array($ext, ALLOWED_IMAGE_TYPES)) {
            $errors['image'] = 'Định dạng ảnh không được hỗ trợ';
        }
        
        // Kiểm tra kích thước
        if ($file['size'] > MAX_FILE_SIZE) {
            $errors['image'] = 'Kích thước ảnh quá lớn';
        }
        
        if (empty($errors['image'])) {
            // Tạo tên file mới
            $newName = uniqid() . '.' . $ext;
            $uploadPath = UPLOAD_DIR . '/' . $newName;
            
            // Upload file
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                // Xóa ảnh cũ nếu có
                if ($image && file_exists(UPLOAD_DIR . '/' . $image)) {
                    unlink(UPLOAD_DIR . '/' . $image);
                }
                $image = $newName;
            } else {
                $errors['image'] = 'Không thể upload ảnh';
            }
        }
    }
    
    // Lưu sản phẩm
    if (empty($errors)) {
        $data = [
            'name' => $name,
            'slug' => createSlug($name),
            'category_id' => $category_id,
            'price' => $price,
            'sale_price' => $sale_price,
            'description' => $description,
            'ingredients' => $ingredients,
            'size' => $size,
            'image' => $image,
            'featured' => $featured
        ];
        
        if ($id) {
            // Cập nhật sản phẩm
            $db->update('products', $data, ['id' => $id]);
            $success = true;
        } else {
            // Thêm sản phẩm mới
            $data['created_at'] = date('Y-m-d H:i:s');
            $db->insert('products', $data);
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $id ? 'Sửa' : 'Thêm'; ?> sản phẩm - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../Assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .back-link { margin-left:12px; margin-bottom:30px; white-space:nowrap; display:inline-flex; align-items:center; text-decoration:none; color:var(--primary-color); transition:color 0.2s; }
        .back-link .fa-arrow-left { transition: transform 0.2s; color: var(--primary-color); }
        .back-link:hover { color:var(--primary-color); }
        .back-link:hover span { text-decoration: underline; }
        .back-link:hover .fa-arrow-left { transform: translateX(-6px); }
    </style>
</head>
<body>
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
                    <h2><?php echo $id ? 'Sửa' : 'Thêm'; ?> sản phẩm</h2>
                </div>
                
                <div class="header-right">
                    <div class="user-menu">
                        <span><?php echo $user['name']; ?></span>
                    </div>
                </div>
            </header>
            <a href="products.php" class="back-link"><i class="fas fa-arrow-left" style="margin-right:6px;"></i><span>Quay lại</span></a>
            <div class="content-inner">

                
                <div class="content-wrapper">
                    <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        Sản phẩm đã được <?php echo $id ? 'cập nhật' : 'thêm mới'; ?> thành công!
                    </div>
                    <?php endif; ?>
                    
                    <form action="" method="POST" enctype="multipart/form-data" class="product-form">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="name">Tên sản phẩm <span class="required">*</span></label>
                                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" required>
                                <?php if (isset($errors['name'])): ?>
                                <span class="error"><?php echo $errors['name']; ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="category_id">Danh mục <span class="required">*</span></label>
                                <select id="category_id" name="category_id" required>
                                    <option value="">Chọn danh mục</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo ($product['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['category_id'])): ?>
                                <span class="error"><?php echo $errors['category_id']; ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="price">Giá gốc <span class="required">*</span></label>
                                <input type="number" id="price" name="price" value="<?php echo $product['price'] ?? ''; ?>" min="0" step="1000" required>
                                <?php if (isset($errors['price'])): ?>
                                <span class="error"><?php echo $errors['price']; ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="sale_price">Giá khuyến mãi</label>
                                <input type="number" id="sale_price" name="sale_price" value="<?php echo $product['sale_price'] ?? ''; ?>" min="0" step="1000">
                                <?php if (isset($errors['sale_price'])): ?>
                                <span class="error"><?php echo $errors['sale_price']; ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label>Kích thước</label>
                                <div class="size-checkbox-group" style="display: flex; gap: 24px;">
                                    <?php
                                    $sizeOptions = ['16cm', '18cm', '20cm'];
                                    $selectedSizes = isset($product['size']) ? explode(',', $product['size']) : [];
                                    foreach ($sizeOptions as $opt) {
                                        $checked = in_array($opt, $selectedSizes) ? 'checked' : '';
                                        echo '<label class="checkbox-label"><input type="checkbox" name="size[]" value="'.$opt.'" '.$checked.'> '.$opt.'</label>';
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="featured" value="1" <?php echo ($product['featured'] ?? 0) ? 'checked' : ''; ?>>
                                    <span>Sản phẩm nổi bật</span>
                                </label>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="description">Mô tả <span class="required">*</span></label>
                                <textarea id="description" name="description" rows="5" required><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                                <?php if (isset($errors['description'])): ?>
                                <span class="error"><?php echo $errors['description']; ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="ingredients">Thành phần</label>
                                <textarea id="ingredients" name="ingredients" rows="3"><?php echo htmlspecialchars($product['ingredients'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="image">Hình ảnh</label>
                                <?php if (!empty($product['image'])): ?>
                                <div class="current-image">
                                    <img src="../uploads/<?php echo $product['image']; ?>" alt="Current image">
                                    <span>Ảnh hiện tại</span>
                                </div>
                                <?php endif; ?>
                                <input style="background-color: #fff;" type="file" id="image" name="image" accept="image/*">
                                <?php if (isset($errors['image'])): ?>
                                <span class="error"><?php echo $errors['image']; ?></span>
                                <?php endif; ?>
                                <small>Định dạng: JPG, PNG, GIF. Kích thước tối đa: 2MB</small>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <a href="products.php" class="btn-cancel">
                                <i class="fas fa-times"></i>
                                Hủy
                            </a>
                            <button type="submit" class="btn-submit">
                                <i class="fas fa-save"></i>
                                <?php echo $id ? 'Cập nhật' : 'Thêm mới'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../Assets/js/admin.js"></script>
</body>
</html> 