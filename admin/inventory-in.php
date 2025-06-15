<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Kiểm tra đăng nhập
checkAuth();

// Lấy thông tin người dùng
$user = getCurrentUser();

// Xử lý form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        $itemType = $_POST['item_type'];
        $itemName = $_POST['item_name'];
        $quantity = $_POST['quantity'];
        $price = $_POST['price'];
        $notes = $_POST['notes'];
        
        // Thêm vào lịch sử nhập kho
        $db->insert('inventory_in', [
            'item_type' => $itemType,
            'item_name' => $itemName,
            'quantity' => $quantity,
            'price' => $price,
            'notes' => $notes,
            'created_by' => $user['id'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $db->commit();
        $_SESSION['success'] = 'Nhập kho thành công!';
        header('Location: inventory.php');
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = 'Có lỗi xảy ra: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhập kho - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../Assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .btn-submit {
            background: var(--primary-color);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .btn-submit:hover {
            opacity: 0.9;
        }
        
        .item-select {
            margin-bottom: 20px;
        }
        
        .item-select select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
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
                            <span>Người dùng</span>
                        </a>
                    </li>
                    <li>
                        <a href="categories.php">
                            <i class="fas fa-tags"></i>
                            <span>Danh mục</span>
                        </a>
                    </li>
                    <li class="active">
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
                    <h2>Nhập kho</h2>
                </div>
                
                <div class="header-right">
                    <div class="user-menu">
                        <span><?php echo $user['name']; ?></span>
                    </div>
                </div>
            </header>
            
            <div class="form-container">
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Phân loại</label>
                        <select name="item_type" class="form-control" required>
                            <option value="ingredient">Nguyên liệu</option>
                            <option value="packaging">Vật phẩm đóng gói</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tên vật phẩm</label>
                        <input type="text" name="item_name" class="form-control" required placeholder="Nhập tên vật phẩm">
                    </div>
                    
                    <div class="form-group">
                        <label>Số lượng</label>
                        <input type="number" name="quantity" class="form-control" required min="0" step="0.01">
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Giá tiền (VNĐ)</label>
                        <input type="number" id="price" name="price" class="form-control" min="0" step="1000" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Ghi chú</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i>
                        Lưu
                    </button>
                </form>
            </div>
        </main>
    </div>
    
    <script src="../Assets/js/admin.js"></script>
    <script>
    // Load danh mục dựa trên loại vật phẩm
    function loadItems(itemType) {
        const categorySelect = document.querySelector('select[name="category_id"]');
        categorySelect.innerHTML = '<option value="">Chọn danh mục</option>';
        
        if (itemType === 'ingredient') {
            <?php foreach ($ingredientCategories as $category): ?>
            categorySelect.innerHTML += `
                <option value="<?php echo $category['id']; ?>">
                    <?php echo htmlspecialchars($category['name']); ?>
                </option>
            `;
            <?php endforeach; ?>
        } else if (itemType === 'packaging') {
            <?php foreach ($packagingCategories as $category): ?>
            categorySelect.innerHTML += `
                <option value="<?php echo $category['id']; ?>">
                    <?php echo htmlspecialchars($category['name']); ?>
                </option>
            `;
            <?php endforeach; ?>
        }
    }
    
    // Load vật phẩm dựa trên danh mục
    function loadItemsByCategory(categoryId) {
        const itemType = document.querySelector('select[name="item_type"]').value;
        const itemSelect = document.querySelector('select[name="item_id"]');
        
        // Gọi API để lấy danh sách vật phẩm
        fetch(`../api/inventory/get-items.php?type=${itemType}&category_id=${categoryId}`)
            .then(response => response.json())
            .then(data => {
                itemSelect.innerHTML = '<option value="">Chọn vật phẩm</option>';
                data.forEach(item => {
                    itemSelect.innerHTML += `
                        <option value="${item.id}">
                            ${item.name} (${item.sku})
                        </option>
                    `;
                });
            })
            .catch(error => console.error('Error:', error));
    }
    
    // Responsive sidebar
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