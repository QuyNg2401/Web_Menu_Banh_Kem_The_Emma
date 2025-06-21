<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Kiểm tra đăng nhập
checkAuth();

// Lấy thông tin người dùng
$user = getCurrentUser();

// Xử lý tìm kiếm và lọc
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Xây dựng câu query
$where = "WHERE 1=1";
$params = [];

if ($search) {
    $where .= " AND (c.name LIKE ? OR c.phone LIKE ? OR c.address LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Bỏ cột created_at trong ORDER BY
$orderBy = 'ORDER BY c.id DESC';

// Lấy danh sách khách hàng
$customers = $db->select(
    "SELECT c.*, 
            COUNT(DISTINCT o.id) as total_orders,
            SUM(CASE WHEN o.status = 'completed' THEN o.total_amount ELSE 0 END) as total_spent
     FROM customers c
     LEFT JOIN orders o ON c.id = o.customer_id
     {$where}
     GROUP BY c.id
     {$orderBy}
     LIMIT ? OFFSET ?",
    array_merge($params, [$limit, $offset])
);

// Lấy tổng số khách hàng
$total = $db->selectOne(
    "SELECT COUNT(*) as total FROM customers c {$where}",
    $params
)['total'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý khách hàng - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../Assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .role-badge {
            display: inline-block;
            background: #3498db;
            color: #fff;
            border-radius: 16px;
            padding: 4px 14px;
            font-size: 14px;
            font-weight: 600;
            white-space: nowrap;
            line-height: 1.5;
            margin-top: 4px;
        }
        .status-pending { background:#ffc107; color:#000; padding:4px 8px; border-radius:4px; font-size:12px; }
        .status-processing { background:#17a2b8; color:#fff; padding:4px 8px; border-radius:4px; font-size:12px; }
        .status-completed { background:#28a745; color:#fff; padding:4px 8px; border-radius:4px; font-size:12px; }
        .status-cancelled { background:#dc3545; color:#fff; padding:4px 8px; border-radius:4px; font-size:12px; }
        .modal-view-btn { background:#007bff; color:#fff; border:none; padding:5px 10px; border-radius:4px; cursor:pointer; font-size:12px; transition:background 0.2s; }
        .modal-view-btn:hover { background:#0056b3; cursor:pointer; }
    </style>
</head>
<body class="customers-page">
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
                    <h2>Quản lý khách hàng</h2>
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
                    <div class="filters-row" style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap; justify-content: space-between; margin-bottom: 18px;">
                        <form action="" method="GET" class="filter-form" style="flex:1; min-width:220px;">
                            <div class="form-group" style="margin-bottom:0;">
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tìm kiếm khách hàng...">
                            </div>
                        </form>
                    </div>
                    
                    <!-- Customers Table -->
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Thông tin</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customers as $c): ?>
                                <tr>
                                    <td><?php echo $c['id']; ?></td>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-details">
                                                <div class="user-name"><?php echo htmlspecialchars($c['name']); ?></div>
                                                <div class="user-contact">
                                                    <span><i class="fas fa-phone"></i> <?php echo $c['phone']; ?></span>
                                                    <span><i class="fas fa-map-marker-alt"></i> <?php echo $c['address']; ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="#" onclick="showCustomerHistory(<?php echo $c['id']; ?>, '<?php echo htmlspecialchars($c['name']); ?>')" class="btn btn-view btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
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
                            echo '<a href="?page=' . ($currentPage - 1) . '&search=' . urlencode($search) . '" class="page-link">';
                            echo '<i class="fas fa-chevron-left"></i>';
                            echo '</a>';
                        }
                        
                        // Page numbers
                        for ($i = max(1, $currentPage - $range); $i <= min($totalPages, $currentPage + $range); $i++) {
                            $active = $i === $currentPage ? 'active' : '';
                            echo '<a href="?page=' . $i . '&search=' . urlencode($search) . '" class="page-link ' . $active . '">' . $i . '</a>';
                        }
                        
                        // Next button
                        if ($currentPage < $totalPages) {
                            echo '<a href="?page=' . ($currentPage + 1) . '&search=' . urlencode($search) . '" class="page-link">';
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
    
    <!-- Modal Lịch sử mua hàng -->
    <div id="customerHistoryModal" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;">
        <div style="background:#fff;padding:24px;border-radius:12px;max-width:95vw;width:800px;max-height:90vh;overflow-y:auto;box-shadow:0 4px 20px rgba(0,0,0,0.15);position:relative;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;border-bottom:1px solid #eee;padding-bottom:15px;">
                <h3 style="margin:0;font-size:1.3rem;color:#333;" id="modalTitle">Lịch sử mua hàng</h3>
                <button onclick="closeCustomerHistoryModal()" style="background:none;border:none;font-size:24px;cursor:pointer;color:#666;padding:0;width:30px;height:30px;display:flex;align-items:center;justify-content:center;">&times;</button>
            </div>
            <div id="customerHistoryContent">
                <!-- Nội dung sẽ được load bằng AJAX -->
            </div>
        </div>
    </div>
    
    <script src="../Assets/js/admin.js"></script>
    <script>
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

    // Hàm hiển thị modal lịch sử mua hàng
    function showCustomerHistory(customerId, customerName) {
        document.getElementById('modalTitle').textContent = `Lịch sử mua hàng - ${customerName}`;
        document.getElementById('customerHistoryModal').style.display = 'flex';
        
        // Load dữ liệu lịch sử mua hàng
        loadCustomerHistory(customerId);
    }

    // Hàm đóng modal
    function closeCustomerHistoryModal() {
        document.getElementById('customerHistoryModal').style.display = 'none';
    }

    // Hàm load lịch sử mua hàng bằng AJAX
    function loadCustomerHistory(customerId) {
        const contentDiv = document.getElementById('customerHistoryContent');
        contentDiv.innerHTML = '<div style="text-align:center;padding:20px;"><i class="fas fa-spinner fa-spin" style="font-size:24px;color:#666;"></i><br>Đang tải...</div>';
        
        fetch(`../api/customers/get-history.php?customer_id=${customerId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayCustomerHistory(data.orders, data.customer);
                } else {
                    contentDiv.innerHTML = '<div style="text-align:center;padding:20px;color:#666;">Không thể tải dữ liệu</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                contentDiv.innerHTML = '<div style="text-align:center;padding:20px;color:#666;">Có lỗi xảy ra khi tải dữ liệu</div>';
            });
    }

    // Hàm hiển thị lịch sử mua hàng
    function displayCustomerHistory(orders, customer) {
        const contentDiv = document.getElementById('customerHistoryContent');
        
        let html = `
            <div style="margin-bottom:20px;padding:15px;background:#f8f9fa;border-radius:8px;">
                <h4 style="margin:0 0 10px 0;color:#333;">Thông tin khách hàng</h4>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;font-size:14px;">
                    <div><strong>Tên:</strong> ${customer.name}</div>
                    <div><strong>SĐT:</strong> ${customer.phone}</div>
                    <div style="grid-column:1/-1;"><strong>Địa chỉ:</strong> ${customer.address}</div>
                </div>
            </div>
        `;
        
        if (orders.length > 0) {
            html += `
                <h4 style="margin:20px 0 15px 0;color:#333;">Lịch sử đơn hàng (${orders.length} đơn)</h4>
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:14px;">
                        <thead>
                            <tr style="background:#f8f9fa;">
                                <th style="padding:10px;text-align:left;border-bottom:1px solid #ddd;">Mã đơn</th>
                                <th style="padding:10px;text-align:left;border-bottom:1px solid #ddd;">Ngày đặt</th>
                                <th style="padding:10px;text-align:left;border-bottom:1px solid #ddd;">Tổng tiền</th>
                                <th style="padding:10px;text-align:left;border-bottom:1px solid #ddd;">Trạng thái</th>
                                <th style="padding:10px;text-align:left;border-bottom:1px solid #ddd;">Chi tiết</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            orders.forEach(order => {
                const statusClass = getStatusClass(order.status);
                html += `
                    <tr>
                        <td style="padding:10px;border-bottom:1px solid #eee;">${order.order_code}</td>
                        <td style="padding:10px;border-bottom:1px solid #eee;">${formatDate(order.created_at)}</td>
                        <td style="padding:10px;border-bottom:1px solid #eee;">${formatCurrency(order.total_amount)}</td>
                        <td style="padding:10px;border-bottom:1px solid #eee;"><span class="${statusClass}">${getStatusText(order.status)}</span></td>
                        <td style="padding:10px;border-bottom:1px solid #eee;">
                            <button onclick="showOrderDetails(${order.id})" class="modal-view-btn">Xem</button>
                        </td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
        } else {
            html += '<div style="text-align:center;padding:20px;color:#666;">Khách hàng chưa có đơn hàng nào</div>';
        }
        
        contentDiv.innerHTML = html;
    }

    // Hàm hỗ trợ
    function getStatusClass(status) {
        const classes = {
            'pending': 'status-pending',
            'processing': 'status-processing', 
            'completed': 'status-completed',
            'cancelled': 'status-cancelled'
        };
        return classes[status] || 'status-pending';
    }

    function getStatusText(status) {
        const texts = {
            'pending': 'Chờ xử lý',
            'processing': 'Đang xử lý',
            'completed': 'Hoàn thành',
            'cancelled': 'Đã hủy'
        };
        return texts[status] || status;
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN', {hour: '2-digit', minute: '2-digit'});
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN', {style: 'currency', currency: 'VND'}).format(amount);
    }

    function showOrderDetails(orderId) {
        // Có thể mở modal chi tiết đơn hàng ở đây
        alert('Chi tiết đơn hàng ' + orderId);
    }

    // Đóng modal khi click bên ngoài
    document.getElementById('customerHistoryModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeCustomerHistoryModal();
        }
    });
    </script>

    <?php if (!empty($_SESSION['success'])): ?>
    <script>
        showNotification("<?php echo addslashes($_SESSION['success']); ?>", "success");
    </script>
    <?php unset($_SESSION['success']); endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
    <script>
        showNotification("<?php echo addslashes($_SESSION['error']); ?>", "error");
    </script>
    <?php unset($_SESSION['error']); endif; ?>
</body>
</html> 