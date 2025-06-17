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

// Lấy lịch sử kiểm kho gần đây (luôn hiển thị tất cả vật phẩm trong kho, kể cả chưa kiểm kho)
$recentTransactions = $db->select("
    SELECT ii.*, ic.actual_quantity, ic.note, ic.created_at as check_time
    FROM inventory_in ii
    LEFT JOIN (
        SELECT * FROM inventory_check WHERE (item_id, created_at) IN (
            SELECT item_id, MAX(created_at) FROM inventory_check GROUP BY item_id
        )
    ) ic ON ii.id = ic.item_id
    ORDER BY ii.created_at DESC
    LIMIT 10
");

// Lấy lịch sử kiểm kho cho tất cả vật phẩm (group theo item_id, order by created_at desc)
$inventoryCheckHistory = [];
$rows = $db->select("SELECT ic.*, u.name as user_name FROM inventory_check ic LEFT JOIN users u ON ic.created_by = u.id ORDER BY ic.item_id, ic.created_at ASC");
foreach ($rows as $row) {
    $inventoryCheckHistory[$row['item_id']][] = $row;
}
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
        
        .action-button.check {
            background: #2ecc71;
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
        
        #detailModal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0; top: 0;
            width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.25);
            align-items: center;
            justify-content: center;
            transition: background 0.3s;
        }
        #detailModal.active {
            display: flex;
            animation: fadeInBg 0.3s;
        }
        @keyframes fadeInBg {
            from { background: rgba(0,0,0,0); }
            to { background: rgba(0,0,0,0.25); }
        }
        .modal-content-detail {
            background: #fff;
            padding: 28px 24px 18px 24px;
            border-radius: 14px;
            max-width: 95vw;
            width: 350px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.18);
            position: relative;
            animation: modalZoomIn 0.35s cubic-bezier(.68,-0.55,.27,1.55);
        }
        @keyframes modalZoomIn {
            0% { transform: scale(0.7) translateY(40px); opacity: 0; }
            100% { transform: scale(1) translateY(0); opacity: 1; }
        }
        .modal-content-detail h3 {
            margin-top: 0;
            font-size: 1.15rem;
            font-weight: 600;
            color: #222;
            margin-bottom: 12px;
            text-align: center;
        }
        .modal-content-detail .modalDetailContent {
            font-size: 15px;
            color: #222;
            white-space: pre-line;
            margin-bottom: 10px;
            max-height: 120px;
            overflow-y: auto;
        }
        .modal-content-detail .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 18px;
        }
        .modal-content-detail button {
            background: #eee;
            color: #222;
            border: none;
            padding: 7px 18px;
            border-radius: 6px;
            font-size: 15px;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }
        .modal-content-detail button:hover {
            background: #2ecc71;
            color: #fff;
        }
        .modal-close-x {
            position: absolute;
            top: 10px;
            right: 14px;
            background: none;
            border: none;
            font-size: 1.7rem;
            color: #888;
            cursor: pointer;
            z-index: 2;
            transition: color 0.2s;
            padding: 0 6px;
            line-height: 1;
        }
        .modal-close-x:hover {
            color: #f7f7f7 !important;
            background:  #e74c3c !important;
        }
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
        </div>
        
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
                    <button class="action-button check" onclick="location.href='inventory-check.php'">
                        <i class="fas fa-clipboard-check"></i>
                        Kiểm kho
                    </button>
                </div>
                
                <!-- Bảng lịch sử nhập kho -->
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Tên vật phẩm</th>
                                <th>Giá tiền</th>
                                <th>Số lượng</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentTransactions as $transaction): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($transaction['item_name']); ?></td>
                                <td><?php echo number_format($transaction['price']); ?> VNĐ</td>
                                <td><?php echo (intval($transaction['quantity']) == $transaction['quantity']) ? intval($transaction['quantity']) : $transaction['quantity']; ?></td>
                                <td>
                                    <a href="#" class="btn-view" title="Xem chi tiết" onclick='showDetails(<?php echo json_encode([
                                        "item_id" => $transaction["id"],
                                        "item_name" => $transaction["item_name"],
                                        "quantity" => $transaction["quantity"],
                                        "actual_quantity" => $transaction["actual_quantity"],
                                        "note" => $transaction["note"],
                                        "check_time" => $transaction["check_time"],
                                        "user_name" => $transaction["user_name"] ?? ""
                                    ]); ?>); return false;'><i class="fas fa-eye"></i></a>
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
    
    <!-- Modal chi tiết vật phẩm -->
    <div id="detailModal">
        <div class="modal-content-detail">
            <button onclick="closeDetailModal()" class="modal-close-x" title="Đóng">&times;</button>
            <h3>Chi tiết vật phẩm</h3>
            <div id="modalDetailContent" class="modalDetailContent"></div>
        </div>
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

    // Hiển thị thông tin chi tiết bằng modal
    function showDetails(data) {
        let message = `Tên vật phẩm: ${data.item_name}\n`;
        if (data.actual_quantity !== null && data.check_time !== null) {
            message += `Thời gian kiểm gần nhất: ${data.check_time ? new Date(data.check_time).toLocaleString('vi-VN') : 'Chưa kiểm kho'}\n`;
            message += `Người thực hiện: ${data.user_name || 'Chưa kiểm kho'}\n`;
        } else {
            message += 'Chưa kiểm kho!';
        }
        // Thêm lịch sử kiểm kho
        if (data.item_id && window.inventoryCheckHistory && window.inventoryCheckHistory[data.item_id]) {
            message += `\n--- Lịch sử kiểm kho ---\n`;
            let html = '';
            window.inventoryCheckHistory[data.item_id].forEach(function(his) {
                let change = null;
                if (his.before_quantity !== null && his.actual_quantity !== null) {
                    change = his.actual_quantity - his.before_quantity;
                }
                let color = '';
                if (change !== null) {
                    if (change < 0) color = 'red';
                    else if (change > 0) color = 'green';
                }
                html += `Ngày: ${new Date(his.created_at).toLocaleDateString('vi-VN')}`;
                if (change !== null) html += ` | Số lượng thay đổi: <span style=\"color:${color};font-weight:bold;\">${change}</span>`;
                if (his.note && his.note.trim() !== '') html += `<br/><span style=\"font-style:italic;\">Ghi chú: ${his.note}</span>`;
                html += `<br/>`;
                html += `<br/>`;
            });
            document.getElementById('modalDetailContent').innerHTML = message.replace(/\n/g, '<br/>') + html;
            document.getElementById('detailModal').classList.add('active');
            return;
        }
        document.getElementById('modalDetailContent').textContent = message;
        document.getElementById('detailModal').classList.add('active');
    }
    function closeDetailModal() {
        document.getElementById('detailModal').classList.remove('active');
    }
    </script>
    <script>
    // Đưa dữ liệu lịch sử kiểm kho sang JS
    window.inventoryCheckHistory = <?php echo json_encode($inventoryCheckHistory); ?>;
    </script>
</body>
</html> 