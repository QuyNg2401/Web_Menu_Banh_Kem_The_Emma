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
            c.name as customer_name,
            COUNT(oi.id) as total_items,
            GROUP_CONCAT(p.name SEPARATOR ', ') as product_names
     FROM orders o 
     LEFT JOIN customers c ON o.customer_id = c.id
     LEFT JOIN order_items oi ON o.id = oi.order_id
     LEFT JOIN products p ON oi.product_id = p.id
     {$where} AND o.isDeleted = 0
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
     {$where} AND o.isDeleted = 0",
    $params
)['total'];

// Xử lý xóa đơn hàng
if (isset($_GET['action']) && $_GET['action'] === 'delete' && !empty($_GET['id'])) {
    $db->update('orders', ['isDeleted' => 1], ['id' => $_GET['id']]);
    $_SESSION['success'] = 'Xóa đơn hàng thành công!';
    header('Location: orders.php');
    exit;
}
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
                        <a href="categories.php">
                            <i class="fas fa-tags"></i>
                            <span>Danh mục</span>
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
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
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
            <div class="content-inner">
                
                
                <div class="content-wrapper">
                    <!-- Filters -->
                    <div class="filters">
                        <form action="" method="GET" class="search-form" style="width: 100%;display:flex; margin-bottom:12px;">
                            <div class="form-group" style="flex:1; position:relative;">
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tìm kiếm đơn hàng..." style="padding-right:40px;">
                                <button type="submit" style="position:absolute; right:6px; top:50%; transform:translateY(-50%); background:none; border:none; color:#bbb; font-size:1.15em; cursor:pointer; padding:0;">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                        <form action="" method="GET" class="filter-form">
                            <div class="filter-row" style="display: flex; gap: 16px; align-items: flex-end; margin-bottom: 12px; flex-wrap: wrap;">
                                <div class="form-group" style="flex:1; min-width:160px;">
                                    <select name="status">
                                        <option value="">Tất cả trạng thái</option>
                                        <?php foreach (ORDER_STATUS as $key => $value): ?>
                                        <option value="<?php echo $key; ?>" <?php echo $status === $key ? 'selected' : ''; ?>>
                                            <?php echo $value; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group" style="flex:1; min-width:160px;">
                                    <select name="sort">
                                        <option value="created_at_desc" <?php echo $sort === 'created_at_desc' ? 'selected' : ''; ?>>Mới nhất</option>
                                        <option value="created_at_asc" <?php echo $sort === 'created_at_asc' ? 'selected' : ''; ?>>Cũ nhất</option>
                                        <option value="total_asc" <?php echo $sort === 'total_asc' ? 'selected' : ''; ?>>Tổng tiền tăng dần</option>
                                        <option value="total_desc" <?php echo $sort === 'total_desc' ? 'selected' : ''; ?>>Tổng tiền giảm dần</option>
                                    </select>
                                </div>
                            </div>
                            <div class="filter-row filter-dates-group" style="display: flex; gap: 16px; align-items: flex-end; margin-bottom: 16px; flex-wrap: wrap;">
                                <div class="filter-dates" style="flex:2; display: flex; gap: 16px; min-width:260px;">
                                    <div style="flex:1; display: flex; flex-direction: column;">
                                        <label for="date_from" style="margin-bottom:4px; font-size:0.97em; color:#444;">Từ ngày</label>
                                        <input type="date" id="date_from" name="date_from" value="<?php echo $date_from; ?>" placeholder="Từ ngày">
                                    </div>
                                    <div style="flex:1; display: flex; flex-direction: column;">
                                        <label for="date_to" style="margin-bottom:4px; font-size:0.97em; color:#444;">Đến ngày</label>
                                        <input type="date" id="date_to" name="date_to" value="<?php echo $date_to; ?>" placeholder="Đến ngày">
                                    </div>
                                </div>
                                <button type="submit" class="btn-filter" style="flex-basis:10%; max-width:10%; align-self:center; min-width:100px; height:44px; margin:0; border-radius:8px; font-size:1.08em; font-weight:600; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 8px rgba(0,0,0,0.04);">
                                    <i class="fas fa-filter"></i>
                                    Lọc
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Orders Table -->
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Mã đơn</th>
                                    <th>Khách hàng</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
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
                                        <?php echo htmlspecialchars($order['customer_name']); ?>
                                    </td>
                                    <td>
                                        <?php echo number_format($order['total_amount']); ?> VNĐ
                                    </td>
                                    <td>
                                        <button class="btn-status-toggle" data-id="<?php echo $order['id']; ?>" data-status="<?php echo $order['status']; ?>" style="border:none; background:none; cursor:pointer; outline:none;">
                                            <?php if ($order['status'] === 'pending'): ?>
                                                <i class="fas fa-check-circle" style="color:#2196f3; font-size:1.5em;"></i>
                                            <?php elseif ($order['status'] === 'confirmed'): ?>
                                                <i class="fas fa-check-circle" style="color:#4caf50; font-size:1.5em;"></i>
                                            <?php elseif ($order['status'] === 'completed'): ?>
                                                <i class="fas fa-check-circle" style="color:#aaa; font-size:1.5em;"></i>
                                            <?php else: ?>
                                                <i class="fas fa-times-circle" style="color:#e74c3c; font-size:1.5em;"></i>
                                            <?php endif; ?>
                                        </button>
                                        <span class="status-label"><?php echo ORDER_STATUS[$order['status']]; ?></span>
                                    </td>
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
    document.querySelectorAll('.btn-status-toggle').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var id = this.getAttribute('data-id');
            var status = this.getAttribute('data-status');
            var nextStatus = '';
            if (status === 'pending') nextStatus = 'confirmed';
            else if (status === 'confirmed') nextStatus = 'completed';
            else return;
            var btnEl = this;
            fetch('update-order-status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + encodeURIComponent(id) + '&status=' + encodeURIComponent(nextStatus)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    btnEl.setAttribute('data-status', nextStatus);
                    if (nextStatus === 'confirmed') {
                        btnEl.innerHTML = '<i class="fas fa-check-circle" style="color:#4caf50; font-size:1.5em;"></i>';
                        btnEl.nextElementSibling.textContent = 'Đã xác nhận';
                    } else if (nextStatus === 'completed') {
                        btnEl.innerHTML = '<i class="fas fa-check-circle" style="color:#aaa; font-size:1.5em;"></i>';
                        btnEl.nextElementSibling.textContent = 'Hoàn thành';
                        // Ẩn nút xóa trong cùng hàng
                        var row = btnEl.closest('tr');
                        if(row) {
                            var btnDelete = row.querySelector('.btn-delete');
                            if(btnDelete) btnDelete.style.display = 'none';
                        }
                    }
                } else {
                    alert('Cập nhật trạng thái thất bại!');
                }
            });
        });
    });
    </script>
    <?php if (!empty($_SESSION['error'])): ?>
    <script>
        showNotification("<?php echo addslashes($_SESSION['error']); ?>", "error");
    </script>
    <?php unset($_SESSION['error']); endif; ?>

    <!-- Modal xác nhận xóa đơn hàng -->
    <div class="modal" id="deleteOrderModal" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.18);align-items:center;justify-content:center;">
        <div class="modal-dialog" style="background:#fff;padding:32px 28px 18px 28px;border-radius:12px;max-width:95vw;width:400px;box-shadow:0 8px 32px rgba(0,0,0,0.18);position:relative;">
            <h3 style="margin-top:0;">Xác nhận xóa</h3>
            <p>Bạn có chắc chắn muốn xóa đơn hàng này không?</p>
            <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:18px;">
                <button id="cancelDeleteOrderBtn" class="btn-cancel">Hủy</button>
                <button id="confirmDeleteOrderBtn" class="btn-submit">Xóa</button>
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
        const deleteModal = document.getElementById('deleteOrderModal');
        const cancelDeleteBtn = document.getElementById('cancelDeleteOrderBtn');
        const confirmDeleteBtn = document.getElementById('confirmDeleteOrderBtn');

        document.querySelectorAll('.btn-delete[data-type="orders"]').forEach(btn => {
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
                    window.location.href = 'orders.php?action=delete&id=' + deleteId;
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
</body>
</html> 