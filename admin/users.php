<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Kiểm tra đăng nhập
checkAuth();

// Lấy thông tin người dùng
$user = getCurrentUser();

// Xử lý tìm kiếm và lọc
$search = $_GET['search'] ?? '';
$status = '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Xây dựng câu query
$where = "WHERE 1=1";
$params = [];

if ($search) {
    $where .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Đã xóa xử lý sắp xếp
$orderBy = 'ORDER BY u.id ASC';

// Lấy danh sách người dùng
$users = $db->select(
    "SELECT u.*, 
            COUNT(DISTINCT o.id) as total_orders,
            SUM(CASE WHEN o.status = 'completed' THEN o.total_amount ELSE 0 END) as total_spent
     FROM users u
     LEFT JOIN orders o ON u.id = o.customer_id
     {$where} AND u.isDeleted = 0
     GROUP BY u.id
     {$orderBy}
     LIMIT ? OFFSET ?",
    array_merge($params, [$limit, $offset])
);

// Lấy tổng số người dùng
$total = $db->selectOne(
    "SELECT COUNT(*) as total FROM users {$where} AND isDeleted = 0",
    $params
)['total'];

// Xử lý xóa nhân viên
if (isset($_GET['action']) && $_GET['action'] === 'delete' && !empty($_GET['id'])) {
    $db->update('users', ['isDeleted' => 1], ['id' => $_GET['id']]);
    $_SESSION['success'] = 'Xóa nhân viên thành công!';
    header('Location: users.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý nhân viên - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../Assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .role-badge {
            display: inline-block;
            background: #e74c3c;
            color: #fff;
            border-radius: 16px;
            padding: 4px 14px;
            font-size: 14px;
            font-weight: 600;
            white-space: nowrap;
            line-height: 1.5;
            margin-top: 4px;
        }
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
</head>
<body class="users-page">
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
                    <li>
                        <a href="customers.php">
                            <i class="fas fa-user"></i>
                            <span>Khách hàng</span>
                        </a>
                    </li>
                    <li class="active">
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
                    <h2>Quản lý nhân viên</h2>
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
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Tìm kiếm nhân viên...">
                            </div>
                        </form>
                        <a href="#" id="showAddUserModal" class="btn-add" style="white-space:nowrap;"> <i class="fas fa-plus"></i> Thêm nhân viên </a>
                    </div>
                    
                    <!-- Users Table -->
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Thông tin</th>
                                    <th>Vai trò</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?php echo $u['id']; ?></td>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-details">
                                                <div class="user-name"><?php echo htmlspecialchars($u['name']); ?></div>
                                                <div class="user-contact">
                                                    <span><i class="fas fa-envelope"></i> <?php echo $u['email']; ?></span>
                                                    <?php if (!empty($u['phone'])): ?>
                                                    <span><i class="fas fa-phone"></i> <?php echo $u['phone']; ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="role-badge <?php echo $u['role']; ?>">
                                            <?php echo $u['role'] === 'admin' ? 'Quản trị viên' : 'Nhân viên'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button onclick="showEditUserModal(<?php echo $u['id']; ?>)" class="btn-edit" title="Sửa" 
                                                data-name="<?php echo htmlspecialchars($u['name']); ?>"
                                                data-email="<?php echo htmlspecialchars($u['email']); ?>"
                                                data-phone="<?php echo htmlspecialchars($u['phone'] ?? ''); ?>"
                                                data-role="<?php echo $u['role']; ?>"
                                                data-hourly-rate="<?php echo $u['hourly_rate']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($u['id'] !== $user['id']): ?>
                                            <button class="btn-delete" data-id="<?php echo $u['id']; ?>" data-type="users" title="Xóa">
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
    
    <!-- Modal thêm nhân viên -->
    <div id="addUserModal" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;">
        <div style="background:#fff;padding:24px;border-radius:12px;max-width:95vw;width:500px;max-height:90vh;overflow-y:auto;box-shadow:0 4px 20px rgba(0,0,0,0.15);position:relative;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;border-bottom:1px solid #eee;padding-bottom:15px;">
                <h3 style="margin:0;font-size:1.3rem;color:#333;">Thêm nhân viên mới</h3>
                <button onclick="closeAddUserModal()" style="background:none;border:none;font-size:24px;cursor:pointer;color:#666;padding:0;width:30px;height:30px;display:flex;align-items:center;justify-content:center;">&times;</button>
            </div>
            <form id="addUserForm" method="POST">
                <div style="margin-bottom:15px;">
                    <label style="display:block;margin-bottom:5px;font-weight:600;color:#333;">Tên nhân viên</label>
                    <input type="text" name="name" required style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-size:14px;">
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block;margin-bottom:5px;font-weight:600;color:#333;">Email</label>
                    <input type="email" name="email" required style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-size:14px;">
                </div>
                 <div style="margin-bottom:15px;">
                    <label style="display:block;margin-bottom:5px;font-weight:600;color:#333;">Mật khẩu</label>
                    <input type="password" name="password" required style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-size:14px;">
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block;margin-bottom:5px;font-weight:600;color:#333;">Số điện thoại</label>
                    <input type="text" name="phone" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-size:14px;">
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block;margin-bottom:5px;font-weight:600;color:#333;">Vai trò</label>
                    <select name="role" required style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-size:14px;">
                        <option value="user" selected>Nhân viên</option>
                        <option value="admin">Quản trị viên</option>
                    </select>
                </div>
                <div style="margin-bottom:20px;">
                    <label style="display:block;margin-bottom:5px;font-weight:600;color:#333;">Lương theo giờ (VNĐ)</label>
                    <input type="number" name="hourly_rate" min="0" value="0" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-size:14px;">
                </div>
                <div style="display:flex;justify-content:flex-end;gap:12px;">
                    <button type="button" onclick="closeAddUserModal()" style="background:#eee;color:#333;border:none;padding:8px 18px;border-radius:4px;cursor:pointer;">Hủy</button>
                    <button type="submit" style="background:#007bff;color:#fff;border:none;padding:8px 18px;border-radius:4px;cursor:pointer;">Lưu</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal chỉnh sửa nhân viên -->
    <div id="editUserModal" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;">
        <div style="background:#fff;padding:24px;border-radius:12px;max-width:95vw;width:500px;max-height:90vh;overflow-y:auto;box-shadow:0 4px 20px rgba(0,0,0,0.15);position:relative;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;border-bottom:1px solid #eee;padding-bottom:15px;">
                <h3 style="margin:0;font-size:1.3rem;color:#333;">Chỉnh sửa nhân viên</h3>
                <button onclick="closeEditUserModal()" style="background:none;border:none;font-size:24px;cursor:pointer;color:#666;padding:0;width:30px;height:30px;display:flex;align-items:center;justify-content:center;">&times;</button>
            </div>
            <form id="editUserForm" method="POST" action="../api/users/update.php">
                <input type="hidden" id="editUserId" name="user_id">
                <div style="margin-bottom:15px;">
                    <label style="display:block;margin-bottom:5px;font-weight:600;color:#333;">Tên nhân viên</label>
                    <input type="text" id="editUserName" name="name" required style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-size:14px;">
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block;margin-bottom:5px;font-weight:600;color:#333;">Email</label>
                    <input type="email" id="editUserEmail" name="email" required style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-size:14px;">
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block;margin-bottom:5px;font-weight:600;color:#333;">Số điện thoại</label>
                    <input type="text" id="editUserPhone" name="phone" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-size:14px;">
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block;margin-bottom:5px;font-weight:600;color:#333;">Vai trò</label>
                    <select id="editUserRole" name="role" required style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-size:14px;">
                        <option value="user">Nhân viên</option>
                        <option value="admin">Quản trị viên</option>
                    </select>
                </div>
                <div style="margin-bottom:20px;">
                    <label style="display:block;margin-bottom:5px;font-weight:600;color:#333;">Lương theo giờ (VNĐ)</label>
                    <input type="number" id="editUserHourlyRate" name="hourly_rate" min="0" step="1000" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-size:14px;">
                </div>
                <div style="display:flex;justify-content:flex-end;gap:12px;">
                    <button type="button" onclick="closeEditUserModal()" style="background:#eee;color:#333;border:none;padding:8px 18px;border-radius:4px;cursor:pointer;">Hủy</button>
                    <button type="submit" style="background:#007bff;color:#fff;border:none;padding:8px 18px;border-radius:4px;cursor:pointer;">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="../Assets/js/admin.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
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

        // Xử lý modal xóa nhân viên
        let deleteId = null;
        const deleteModal = document.getElementById('deleteUserModal');
        const cancelDeleteBtn = document.getElementById('cancelDeleteUserBtn');
        const confirmDeleteBtn = document.getElementById('confirmDeleteUserBtn');

        document.querySelectorAll('.btn-delete[data-type="users"]').forEach(btn => {
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
                    window.location.href = 'users.php?action=delete&id=' + deleteId;
                }
            };
        }

        // Xử lý form chỉnh sửa
        const editUserForm = document.getElementById('editUserForm');
        if (editUserForm) {
            editUserForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                fetch('../api/users/update.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        closeEditUserModal();
                        // Hiển thị thông báo thành công
                        var modal = document.getElementById('successModal');
                        var msg = document.getElementById('successModalMsg');
                        var closeBtn = document.getElementById('closeSuccessModal');
                        if (modal && msg) {
                            msg.innerHTML = data.message || 'Cập nhật nhân viên thành công!';
                            modal.style.display = 'flex';
                            
                            // Đếm ngược 3 giây trong nút button
                            let timeLeft = 3;
                            closeBtn.textContent = `Đóng (${timeLeft}s)`;
                            
                            const countdownTimer = setInterval(() => {
                                timeLeft--;
                                closeBtn.textContent = `Đóng (${timeLeft}s)`;
                                
                                if (timeLeft <= 0) {
                                    clearInterval(countdownTimer);
                                    window.location.reload();
                                }
                            }, 1000);
                            
                            // Lưu timer để có thể hủy khi đóng thủ công
                            modal.countdownTimer = countdownTimer;
                        }
                    } else {
                        alert(data.message || 'Có lỗi xảy ra khi cập nhật');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra khi cập nhật nhân viên');
                });
            });
        }

        // Đóng modal khi click bên ngoài
        const editUserModal = document.getElementById('editUserModal');
        if (editUserModal) {
            editUserModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeEditUserModal();
                }
            });
        }

        // Xử lý modal thêm nhân viên
        const addUserModal = document.getElementById('addUserModal');
        document.getElementById('showAddUserModal').addEventListener('click', () => {
            addUserModal.style.display = 'flex';
        });

        addUserModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddUserModal();
            }
        });
        
        // Xử lý form thêm nhân viên
        document.getElementById('addUserForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('../api/users/create.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeAddUserModal();
                    var modal = document.getElementById('successModal');
                    var msg = document.getElementById('successModalMsg');
                    if (modal && msg) {
                        msg.innerHTML = data.message || 'Thêm nhân viên thành công!';
                        modal.style.display = 'flex';
                        // Logic đếm ngược và reload đã có sẵn
                    }
                } else {
                    alert(data.message || 'Có lỗi xảy ra.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Không thể thêm nhân viên. Vui lòng thử lại.');
            });
        });
    });

    function closeAddUserModal() {
        const modal = document.getElementById('addUserModal');
        modal.style.display = 'none';
        document.getElementById('addUserForm').reset();
    }

    // Hàm hiển thị modal chỉnh sửa nhân viên
    function showEditUserModal(userId) {
        const button = event.target.closest('button');
        const name = button.getAttribute('data-name');
        const email = button.getAttribute('data-email');
        const phone = button.getAttribute('data-phone');
        const role = button.getAttribute('data-role');
        const hourlyRate = button.getAttribute('data-hourly-rate');
        
        document.getElementById('editUserId').value = userId;
        document.getElementById('editUserName').value = name;
        document.getElementById('editUserEmail').value = email;
        document.getElementById('editUserPhone').value = phone;
        document.getElementById('editUserRole').value = role;
        document.getElementById('editUserHourlyRate').value = hourlyRate;
        document.getElementById('editUserModal').style.display = 'flex';
    }

    // Hàm đóng modal chỉnh sửa
    function closeEditUserModal() {
        document.getElementById('editUserModal').style.display = 'none';
    }
    </script>

    <?php if (!empty($_SESSION['error'])): ?>
    <script>
        showNotification("<?php echo addslashes($_SESSION['error']); ?>", "error");
    </script>
    <?php unset($_SESSION['error']); endif; ?>

    <!-- Modal xác nhận xóa nhân viên -->
    <div class="modal" id="deleteUserModal" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.18);align-items:center;justify-content:center;">
        <div class="modal-dialog" style="background:#fff;padding:32px 28px 18px 28px;border-radius:12px;max-width:95vw;width:400px;box-shadow:0 8px 32px rgba(0,0,0,0.18);position:relative;">
            <h3 style="margin-top:0;">Xác nhận xóa</h3>
            <p>Bạn có chắc chắn muốn xóa nhân viên này không?</p>
            <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:18px;">
                <button id="cancelDeleteUserBtn" class="btn-cancel">Hủy</button>
                <button id="confirmDeleteUserBtn" class="btn-submit">Xóa</button>
            </div>
        </div>
    </div>
    <!-- Modal thông báo thành công -->
    <div class="modal" id="successModal" style="display:none;position:fixed;z-index:10000;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.18);align-items:center;justify-content:center;">
        <div class="modal-dialog" style="background:#fff;padding:32px 28px 18px 28px;border-radius:12px;max-width:95vw;width:350px;box-shadow:0 8px 32px rgba(0,0,0,0.18);position:relative;text-align:center;">
            <h3 style="margin-top:0;color:#28a745;"><i class="fas fa-check-circle"></i> Thành công</h3>
            <div id="successModalMsg" style="margin:18px 0 12px 0;font-size:1.1rem;"></div>
            <div style="display:flex;justify-content:center;margin-top:10px;">
                <button id="closeSuccessModal" class="btn-submit">Đóng (3s)</button>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Xử lý modal thông báo thành công
        var modal = document.getElementById('successModal');
        var msg = document.getElementById('successModalMsg');
        var closeBtn = document.getElementById('closeSuccessModal');
        if (modal && msg && closeBtn) {
            closeBtn.onclick = function() {
                // Hủy timer nếu có
                if (modal.countdownTimer) {
                    clearInterval(modal.countdownTimer);
                }
                modal.style.display = 'none';
                window.location.reload();
            };
            window.onclick = function(event) {
                if (event.target === modal) {
                    // Hủy timer nếu có
                    if (modal.countdownTimer) {
                        clearInterval(modal.countdownTimer);
                    }
                    modal.style.display = 'none';
                    window.location.reload();
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
        if (modal && msg) {
            msg.innerHTML = "<?php echo addslashes($_SESSION['success']); ?>";
            modal.style.display = 'flex';
        }
    });
    </script>
    <?php unset($_SESSION['success']); endif; ?>
</body>
</html> 