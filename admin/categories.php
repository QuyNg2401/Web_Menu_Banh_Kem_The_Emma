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

// AJAX add category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_add_category'])) {
    $name = trim($_POST['name'] ?? '');
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập tên danh mục.']);
    } else {
        // Lấy id lớn nhất từ các mục chưa xóa để xác định id tiếp theo
        $maxVisibleId = $db->selectOne('SELECT MAX(id) as max_id FROM categories WHERE isDeleted = 0');
        $nextId = ($maxVisibleId && $maxVisibleId['max_id']) ? $maxVisibleId['max_id'] + 1 : 1;

        // Kiểm tra xem id này có bị chiếm bởi một mục đã xóa không
        $existingRecord = $db->selectOne('SELECT id FROM categories WHERE id = ?', [$nextId]);
        if ($existingRecord) {
            // Nếu có, dời mục đã xóa ra một id khác
            $absoluteMaxId = $db->selectOne('SELECT MAX(id) as max_id FROM categories');
            $newIdForOldRecord = $absoluteMaxId['max_id'] + 1;
            $db->update('categories', ['id' => $newIdForOldRecord], ['id' => $nextId]);
        }
        
        // Chèn mục mới vào id đã được giải phóng
        $db->insert('categories', [
            'id' => $nextId,
            'name' => $name,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'isDeleted' => 0
        ]);
        echo json_encode(['success' => true, 'message' => 'Thêm danh mục thành công!']);
    }
    exit;
}

// Thêm danh mục (luôn cho phép khi POST và không phải edit/delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'edit' && $action !== 'delete') {
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        $errors[] = 'Vui lòng nhập tên danh mục.';
    } else {
        // Lấy id lớn nhất từ các mục chưa xóa để xác định id tiếp theo
        $maxVisibleId = $db->selectOne('SELECT MAX(id) as max_id FROM categories WHERE isDeleted = 0');
        $nextId = ($maxVisibleId && $maxVisibleId['max_id']) ? $maxVisibleId['max_id'] + 1 : 1;
        
        // Kiểm tra xem id này có bị chiếm bởi một mục đã xóa không
        $existingRecord = $db->selectOne('SELECT id FROM categories WHERE id = ?', [$nextId]);
        if ($existingRecord) {
            // Nếu có, dời mục đã xóa ra một id khác
            $absoluteMaxId = $db->selectOne('SELECT MAX(id) as max_id FROM categories');
            $newIdForOldRecord = $absoluteMaxId['max_id'] + 1;
            $db->update('categories', ['id' => $newIdForOldRecord], ['id' => $nextId]);
        }
        
        // Chèn mục mới vào id đã được giải phóng
        $db->insert('categories', [
            'id' => $nextId,
            'name' => $name,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'isDeleted' => 0
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
    $db->update('categories', ['isDeleted' => 1], ['id' => $id]);
    $_SESSION['success'] = 'Xóa danh mục thành công!';
    header('Location: categories.php');
    exit;
}

// Lấy danh sách danh mục
$categories = $db->select('SELECT * FROM categories WHERE isDeleted = 0 ORDER BY id ASC');
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
    <style>
        #addCategoryAlert .alert { padding: 10px 15px; margin-bottom: 15px; border-radius: 5px; text-align: center; }
        #addCategoryAlert .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        #addCategoryAlert .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .btn-cancel {
            background: #eee;
            color: #333;
            border: none;
            padding: 8px 18px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none !important;
        }
        .modal-action-row {
            display: flex;
            flex-direction: row;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 18px;
        }
        .modal-action-row .btn-cancel, .modal-action-row .btn-submit {
            min-width: 80px;
            font-size: 1.08rem;
        }
        #successModal .btn-submit {
            min-width: 80px;
            font-size: 1.08rem;
        }
    </style>
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
                <li class="active">
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
                <button class="btn-submit" id="openAddCategoryModal" style="margin-bottom: 18px;"><i class="fas fa-plus"></i> Thêm danh mục</button>
                
                <!-- Modal Thêm danh mục -->
                <div class="modal" id="addCategoryModal" tabindex="-1" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.18);align-items:center;justify-content:center;">
                    <div class="modal-dialog" style="background:#fff;padding:32px 28px 18px 28px;border-radius:12px;max-width:95vw;width:400px;box-shadow:0 8px 32px rgba(0,0,0,0.18);position:relative;">
                        <button id="closeAddCategoryModal" style="position:absolute;top:10px;right:14px;background:none;border:none;font-size:1.7rem;color:#888;cursor:pointer;z-index:2;">&times;</button>
                        <h3 style="margin-top:0;">Thêm danh mục</h3>
                        <div id="addCategoryAlert"></div>
                        <form id="addCategoryForm" autocomplete="off">
                            <div class="form-group">
                                <label for="modal_category_name">Tên danh mục</label>
                                <input type="text" id="modal_category_name" name="name" class="form-control" required>
                            </div>
                            <div class="form-actions" style="margin-top:18px;">
                                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Thêm mới</button>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- Kết thúc modal -->

                <div class="table-responsive">
                    <table class="custom-table">
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
                                        <button onclick="showEditCategoryModal(<?php echo $cat['id']; ?>)" class="btn-edit" title="Sửa" 
                                            data-name="<?php echo htmlspecialchars($cat['name']); ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="#" class="btn-delete" data-id="<?php echo $cat['id']; ?>" title="Xóa"><i class="fas fa-trash"></i></a>
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
<!-- Modal xác nhận xóa danh mục -->
<div class="modal" id="deleteCategoryModal" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.18);align-items:center;justify-content:center;">
    <div class="modal-dialog" style="background:#fff;padding:32px 28px 18px 28px;border-radius:12px;max-width:95vw;width:400px;box-shadow:0 8px 32px rgba(0,0,0,0.18);position:relative;">
        <h3 style="margin-top:0;">Xác nhận xóa</h3>
        <p>Bạn có chắc chắn muốn xóa danh mục này không?</p>
        <div class="modal-action-row">
            <button id="cancelDeleteBtn" class="btn-cancel">Hủy</button>
            <button id="confirmDeleteBtn" class="btn-submit">Xóa</button>
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
<!-- Modal chỉnh sửa danh mục -->
<div id="editCategoryModal" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100vw;height:100vh;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;">
    <div style="background:#fff;padding:24px;border-radius:12px;max-width:95vw;width:400px;box-shadow:0 4px 20px rgba(0,0,0,0.15);position:relative;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;border-bottom:1px solid #eee;padding-bottom:15px;">
            <h3 style="margin:0;font-size:1.3rem;color:#333;">Chỉnh sửa danh mục</h3>
            <button onclick="closeEditCategoryModal()" style="background:none;border:none;font-size:24px;cursor:pointer;color:#666;padding:0;width:30px;height:30px;display:flex;align-items:center;justify-content:center;">&times;</button>
        </div>
        <form id="editCategoryForm" method="POST" action="../api/categories/update.php">
            <input type="hidden" id="editCategoryId" name="category_id">
            <div style="margin-bottom:15px;">
                <label style="display:block;margin-bottom:5px;font-weight:600;color:#333;">Tên danh mục</label>
                <input type="text" id="editCategoryName" name="name" required style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-size:14px;">
            </div>
            <div style="display:flex;justify-content:flex-end;gap:12px;">
                <button type="button" onclick="closeEditCategoryModal()" style="background:#eee;color:#333;border:none;padding:8px 18px;border-radius:4px;cursor:pointer;">Hủy</button>
                <button type="submit" style="background:#007bff;color:#fff;border:none;padding:8px 18px;border-radius:4px;cursor:pointer;">Cập nhật</button>
            </div>
        </form>
    </div>
</div>
<script src="../Assets/js/admin.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Responsive sidebar giống index.php
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    menuToggle && menuToggle.addEventListener('click', function() {
        sidebar.classList.toggle('active');
    });
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 1024 && sidebar.classList.contains('active')) {
            if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        }
    });

    // Modal logic
    const openBtn = document.getElementById('openAddCategoryModal');
    const modal = document.getElementById('addCategoryModal');
    const closeBtn = document.getElementById('closeAddCategoryModal');

    if(openBtn && modal && closeBtn) {
        openBtn.onclick = () => { 
            modal.style.display = 'flex'; 
            document.getElementById('addCategoryAlert').innerHTML = ''; 
            document.getElementById('addCategoryForm').reset(); 
        };
        closeBtn.onclick = () => { modal.style.display = 'none'; };
        window.onclick = function(event) { if (event.target === modal) modal.style.display = 'none'; };
    }

    // AJAX submit
    const addCategoryForm = document.getElementById('addCategoryForm');
    addCategoryForm.onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(addCategoryForm);
        formData.append('ajax_add_category', 1);
        fetch('categories.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            const alertDiv = document.getElementById('addCategoryAlert');
            if (data.success) {
                alertDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> ' + data.message + '</div>';
                addCategoryForm.reset();
                setTimeout(() => { modal.style.display = 'none'; location.reload(); }, 1200);
            } else {
                alertDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ' + data.message + '</div>';
            }
        })
        .catch(() => {
            document.getElementById('addCategoryAlert').innerHTML = '<div class="alert alert-danger">Lỗi kết nối máy chủ!</div>';
        });
    };

    var categoryForm = document.getElementById('categoryForm');
    if (categoryForm) {
        categoryForm.addEventListener('submit', function(e) {
            // Chỉ xử lý AJAX cho form thêm mới (không có action=edit)
            if (this.action.includes('action=edit')) {
                return;
            }
            
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('ajax_add_category', '1');
            
            fetch('categories.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    // Reset form và reload trang sau 1s
                    document.getElementById('categoryForm').reset();
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Lỗi kết nối máy chủ!', 'error');
            });
        });
    }

    // Modal xác nhận xóa danh mục
    let deleteId = null;
    const deleteModal = document.getElementById('deleteCategoryModal');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

    document.querySelectorAll('.btn-delete').forEach(btn => {
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
                window.location.href = 'categories.php?action=delete&id=' + deleteId;
            }
        };
    }
});
</script>

<script>
// Hàm hiển thị modal chỉnh sửa danh mục
function showEditCategoryModal(categoryId) {
    const button = event.target.closest('button');
    const name = button.getAttribute('data-name');
    
    document.getElementById('editCategoryId').value = categoryId;
    document.getElementById('editCategoryName').value = name;
    document.getElementById('editCategoryModal').style.display = 'flex';
}

// Hàm đóng modal chỉnh sửa
function closeEditCategoryModal() {
    document.getElementById('editCategoryModal').style.display = 'none';
}

// Xử lý form chỉnh sửa
document.addEventListener('DOMContentLoaded', function() {
    const editCategoryForm = document.getElementById('editCategoryForm');
    if (editCategoryForm) {
        editCategoryForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('../api/categories/update.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeEditCategoryModal();
                    // Hiển thị thông báo thành công
                    var modal = document.getElementById('successModal');
                    var msg = document.getElementById('successModalMsg');
                    var closeBtn = document.getElementById('closeSuccessModal');
                    if (modal && msg) {
                        msg.innerHTML = data.message || 'Cập nhật danh mục thành công!';
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
                alert('Có lỗi xảy ra khi cập nhật danh mục');
            });
        });
    }

    // Đóng modal khi click bên ngoài
    const editCategoryModal = document.getElementById('editCategoryModal');
    if (editCategoryModal) {
        editCategoryModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditCategoryModal();
            }
        });
    }

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