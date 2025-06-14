<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Kiểm tra đăng nhập
checkAuth();

// Lấy thông tin người dùng
$user = getCurrentUser();

// Xử lý khi form được submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = $_POST['item_id'];
    $actual_quantity = $_POST['actual_quantity'];
    $note = $_POST['note'];
    
    // Lưu thông tin kiểm kho
    $db->insert('inventory_check', [
        'item_id' => $item_id,
        'actual_quantity' => $actual_quantity,
        'note' => $note,
        'created_by' => $user['id'],
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    // Cập nhật số lượng trong kho
    $db->update('inventory_in', 
        ['quantity' => $actual_quantity],
        ['id' => $item_id]
    );
    
    header('Location: inventory.php');
    exit;
}

// Lấy danh sách vật phẩm trong kho
$items = $db->select("
    SELECT 
        inventory_in.*,
        COALESCE(inventory_check.created_at, inventory_in.created_at) as last_check
    FROM inventory_in
    LEFT JOIN inventory_check ON inventory_in.id = inventory_check.item_id
    ORDER BY inventory_in.item_name ASC
");
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiểm kho - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../Assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .check-form {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .submit-btn {
            background: #2ecc71;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .submit-btn:hover {
            opacity: 0.9;
        }
        
        .last-check {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        @media (max-width: 546px) {
            .btn-add {
                padding: 6px 0 !important;
                font-size: 13px !important;
                width: 100% !important;
                min-width: 0 !important;
                box-sizing: border-box;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 4px;
            }
            .custom-table th, .custom-table td {
                padding: 6px 4px;
                font-size: 13px;
            }
            .check-form {
                flex-direction: column;
                gap: 4px;
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
                    <h2>Kiểm kho</h2>
                </div>
                
                <div class="header-right">
                    <div class="user-menu">
                        <span><?php echo $user['name']; ?></span>
                    </div>
                </div>
            </header>
            
            <div class="dashboard">
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Tên vật phẩm</th>
                                <th>Số lượng hiện tại</th>
                                <th>Số lượng thực tế</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($item['item_name']); ?>
                                    <div class="last-check" style="font-size:12px;color:#888;">
                                        Lần kiểm cuối: <?php echo date('d/m/Y H:i', strtotime($item['last_check'])); ?>
                                    </div>
                                </td>
                                <td><?php echo intval($item['quantity']); ?></td>
                                <td style="min-width:120px;">
                                    <form method="POST" class="check-form" style="display:flex;align-items:center;gap:8px;background:none;box-shadow:none;padding:0;margin:0;" onsubmit="return openNoteModal(this);">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <input type="number" name="actual_quantity" value="<?php echo intval($item['quantity']); ?>" min="0" step="1" required style="width:70px;padding:6px 8px;border:1px solid #ddd;border-radius:4px;font-size:15px;">
                                        <input type="hidden" name="note" value="">
                                </td>
                                <td>
                                        <button type="submit" class="btn-add" style="padding:7px 16px;font-size:15px;display:flex;align-items:center;gap:5px;">
                                            <i class="fas fa-save"></i> Lưu
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal nhập ghi chú -->
            <div id="noteModal" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.25);align-items:center;justify-content:center;">
                <div style="background:#fff;padding:24px 20px 16px 20px;border-radius:10px;max-width:95vw;width:350px;box-shadow:0 2px 16px rgba(0,0,0,0.18);position:relative;">
                    <h3 style="margin-top:0;font-size:1.15rem;font-weight:600;color:#222;margin-bottom:12px;">Nhập ghi chú kiểm kho</h3>
                    <textarea id="modalNoteInput" style="width:100%;height:70px;padding:8px 10px;border:1px solid #ddd;border-radius:6px;font-size:15px;resize:vertical;margin-bottom:16px;"></textarea>
                    <div style="display:flex;gap:10px;justify-content:flex-end;">
                        <button onclick="closeNoteModal()" style="background:#eee;color:#222;border:none;padding:7px 18px;border-radius:6px;font-size:15px;cursor:pointer;">Hủy</button>
                        <button onclick="submitNoteModal()" style="background:#2ecc71;color:#fff;border:none;padding:7px 18px;border-radius:6px;font-size:15px;cursor:pointer;">Xác nhận</button>
                    </div>
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

    let currentForm = null;
    function openNoteModal(form) {
        currentForm = form;
        document.getElementById('modalNoteInput').value = '';
        document.getElementById('noteModal').style.display = 'flex';
        setTimeout(()=>{document.getElementById('modalNoteInput').focus();}, 100);
        return false;
    }
    function closeNoteModal() {
        document.getElementById('noteModal').style.display = 'none';
        currentForm = null;
    }
    function submitNoteModal() {
        if(currentForm) {
            let note = document.getElementById('modalNoteInput').value;
            currentForm.querySelector('input[name="note"]').value = note;
            document.getElementById('noteModal').style.display = 'none';
            currentForm.submit();
        }
    }
    </script>
</body>
</html> 