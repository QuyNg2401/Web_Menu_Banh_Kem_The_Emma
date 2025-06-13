<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Kiểm tra đăng nhập
checkAuth();

// Lấy thông tin người dùng
$user = getCurrentUser();

// Thống kê tổng số nguyên liệu nhập kho
$total_ingredient = $db->selectOne("SELECT COUNT(DISTINCT item_name) as total FROM inventory_in WHERE item_type = 'ingredient'")['total'];
// Thống kê tổng số vật phẩm đóng gói nhập kho
$total_packaging = $db->selectOne("SELECT COUNT(*) as total FROM inventory_in WHERE item_type = 'packaging'")['total'];

// Lấy lịch sử nhập kho gần đây
$recentTransactions = $db->select("
    SELECT 
        inventory_in.created_at,
        inventory_in.item_name,
        inventory_in.quantity,
        u.name as user_name
    FROM inventory_in
    LEFT JOIN users u ON inventory_in.created_by = u.id
    ORDER BY inventory_in.created_at DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý kho - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../Assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .inventory-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .inventory-card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .inventory-card .card-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .inventory-card .card-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        
        .inventory-card .card-title {
            font-size: 16px;
            font-weight: 600;
            margin: 0;
        }
        
        .inventory-card .card-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
            margin: 10px 0;
        }
        
        .inventory-card .card-footer {
            font-size: 14px;
            color: #666;
        }
        
        .inventory-tabs {
            margin-bottom: 20px;
        }
        
        .inventory-tabs .tab-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .inventory-tabs .tab-button {
            padding: 10px 20px;
            border: none;
            background: #f5f5f5;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .inventory-tabs .tab-button.active {
            background: var(--primary-color);
            color: #fff;
        }
        
        .inventory-tabs .tab-content {
            display: none;
        }
        
        .inventory-tabs .tab-content.active {
            display: block;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .action-button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            background: var(--primary-color);
            color: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }
        
        .action-button:hover {
            opacity: 0.9;
        }
        
        .warning-badge {
            background: #ff4444;
            color: #fff;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
        }
        
        @media (max-width: 768px) {
            .inventory-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .action-button {
                width: 100%;
                justify-content: center;
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
                    <h2>Quản lý kho</h2>
                </div>
                
                <div class="header-right">
                    <div class="user-menu">
                        <span><?php echo $user['name']; ?></span>
                    </div>
                </div>
            </header>
            
            <div class="dashboard">
                <!-- Thống kê kho -->
                <div class="inventory-grid">
                    <div class="inventory-card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-box"></i>
                            </div>
                            <h3 class="card-title">Tổng nguyên liệu</h3>
                        </div>
                        <div class="card-value"><?php echo number_format($total_ingredient); ?></div>
                        <div class="card-footer">Số loại nguyên liệu đã từng nhập</div>
                    </div>
                    <div class="inventory-card">
                        <div class="card-header">
                            <div class="card-icon">
                                <i class="fas fa-plus"></i>
                            </div>
                            <h3 class="card-title">Tổng vật phẩm đóng gói</h3>
                        </div>
                        <div class="card-value"><?php echo number_format($total_packaging); ?></div>
                        <div class="card-footer">Tổng số vật phẩm đóng gói đã nhập</div>
                    </div>
                </div>
                
                <!-- Nút thao tác -->
                <div class="action-buttons">
                    <button class="action-button" onclick="location.href='inventory-in.php'">
                        <i class="fas fa-plus"></i>
                        Nhập kho
                    </button>
                </div>
                
                <!-- Bảng lịch sử nhập kho -->
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Thời gian</th>
                                <th>Tên vật phẩm</th>
                                <th>Số lượng</th>
                                <th>Người thực hiện</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentTransactions as $transaction): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($transaction['item_name']); ?></td>
                                <td><?php echo (intval($transaction['quantity']) == $transaction['quantity']) ? intval($transaction['quantity']) : $transaction['quantity']; ?></td>
                                <td><?php echo htmlspecialchars($transaction['user_name']); ?></td>
                                <td>
                                    <a href="#" class="btn-view" title="Xem chi tiết"><i class="fas fa-eye"></i></a>
                                    <a href="#" class="btn-action btn-delete" title="Xóa"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../Assets/js/admin.js"></script>
    <script>
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