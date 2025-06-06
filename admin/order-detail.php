<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Kiểm tra đăng nhập
checkAuth();

// Lấy thông tin người dùng
$user = getCurrentUser();

// Lấy ID đơn hàng
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: orders.php');
    exit;
}

// Lấy thông tin đơn hàng
$order = $db->selectOne(
    "SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone
     FROM orders o
     LEFT JOIN users u ON o.user_id = u.id
     WHERE o.id = ?",
    [$id]
);

if (!$order) {
    header('Location: orders.php');
    exit;
}

// Lấy danh sách sản phẩm trong đơn hàng
$items = $db->select(
    "SELECT oi.*, p.name, p.image, p.price as original_price
     FROM order_items oi
     LEFT JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = ?",
    [$id]
);

// Xử lý cập nhật trạng thái
$success = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    
    if (empty($status)) {
        $error = 'Vui lòng chọn trạng thái đơn hàng';
    } else {
        // Cập nhật trạng thái
        $db->update('orders', [
            'status' => $status,
            'notes' => $notes
        ], ['id' => $id]);
        
        $success = true;
        $order['status'] = $status;
        $order['notes'] = $notes;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng #<?php echo $order['order_code']; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../Assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1><?php echo SITE_NAME; ?></h1>
                <p>Admin Panel</p>
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
                    <li class="active">
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
                    <h2>Chi tiết đơn hàng #<?php echo $order['order_code']; ?></h2>
                </div>
                
                <div class="header-right">
                    <div class="user-menu">
                        <img src="<?php echo $user['avatar'] ?? '../Assets/images/default-avatar.png'; ?>" alt="Avatar">
                        <span><?php echo $user['name']; ?></span>
                    </div>
                </div>
            </header>
            
            <div class="content-wrapper">
                <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    Cập nhật trạng thái đơn hàng thành công!
                </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <div class="order-detail">
                    <div class="detail-grid">
                        <!-- Thông tin đơn hàng -->
                        <div class="detail-section">
                            <h3>Thông tin đơn hàng</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Mã đơn hàng:</label>
                                    <span>#<?php echo $order['order_code']; ?></span>
                                </div>
                                
                                <div class="info-item">
                                    <label>Ngày đặt:</label>
                                    <span><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></span>
                                </div>
                                
                                <div class="info-item">
                                    <label>Trạng thái:</label>
                                    <span class="status-badge <?php echo $order['status']; ?>">
                                        <?php echo ORDER_STATUS[$order['status']]; ?>
                                    </span>
                                </div>
                                
                                <div class="info-item">
                                    <label>Phương thức thanh toán:</label>
                                    <span>
                                        <i class="fas fa-credit-card"></i>
                                        <?php echo PAYMENT_METHODS[$order['payment_method']] ?? $order['payment_method']; ?>
                                    </span>
                                </div>
                                
                                <div class="info-item">
                                    <label>Tổng tiền:</label>
                                    <span class="total-amount"><?php echo number_format($order['total_amount']); ?> VNĐ</span>
                                </div>
                                
                                <?php if ($order['notes']): ?>
                                <div class="info-item full-width">
                                    <label>Ghi chú:</label>
                                    <span><?php echo nl2br(htmlspecialchars($order['notes'])); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Thông tin khách hàng -->
                        <div class="detail-section">
                            <h3>Thông tin khách hàng</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Họ tên:</label>
                                    <span><?php echo htmlspecialchars($order['customer_name']); ?></span>
                                </div>
                                
                                <div class="info-item">
                                    <label>Số điện thoại:</label>
                                    <span><?php echo $order['customer_phone']; ?></span>
                                </div>
                                
                                <?php if ($order['customer_email']): ?>
                                <div class="info-item">
                                    <label>Email:</label>
                                    <span><?php echo $order['customer_email']; ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <div class="info-item full-width">
                                    <label>Địa chỉ:</label>
                                    <span><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Danh sách sản phẩm -->
                        <div class="detail-section full-width">
                            <h3>Danh sách sản phẩm</h3>
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Hình ảnh</th>
                                            <th>Sản phẩm</th>
                                            <th>Giá</th>
                                            <th>Số lượng</th>
                                            <th>Thành tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td>
                                                <img src="<?php echo $item['image'] ? '../uploads/' . $item['image'] : '../Assets/images/no-image.png'; ?>" 
                                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                     class="product-thumbnail">
                                            </td>
                                            <td>
                                                <div class="product-name">
                                                    <?php echo htmlspecialchars($item['name']); ?>
                                                    <?php if ($item['notes']): ?>
                                                    <div class="product-notes">
                                                        <i class="fas fa-info-circle"></i>
                                                        <?php echo htmlspecialchars($item['notes']); ?>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($item['price'] < $item['original_price']): ?>
                                                <div class="price-sale">
                                                    <span class="original-price"><?php echo number_format($item['original_price']); ?> VNĐ</span>
                                                    <span class="sale-price"><?php echo number_format($item['price']); ?> VNĐ</span>
                                                </div>
                                                <?php else: ?>
                                                <span class="price"><?php echo number_format($item['price']); ?> VNĐ</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td class="subtotal"><?php echo number_format($item['price'] * $item['quantity']); ?> VNĐ</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="4" class="text-right">Tổng tiền:</td>
                                            <td class="total"><?php echo number_format($order['total_amount']); ?> VNĐ</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Cập nhật trạng thái -->
                        <div class="detail-section full-width">
                            <h3>Cập nhật trạng thái</h3>
                            <form action="" method="POST" class="status-form">
                                <div class="form-group">
                                    <label for="status">Trạng thái:</label>
                                    <select id="status" name="status" required>
                                        <?php foreach (ORDER_STATUS as $key => $value): ?>
                                        <option value="<?php echo $key; ?>" <?php echo $order['status'] === $key ? 'selected' : ''; ?>>
                                            <?php echo $value; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="notes">Ghi chú:</label>
                                    <textarea id="notes" name="notes" rows="3"><?php echo htmlspecialchars($order['notes'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="form-actions">
                                    <a href="orders.php" class="btn-cancel">
                                        <i class="fas fa-arrow-left"></i>
                                        Quay lại
                                    </a>
                                    <button type="submit" class="btn-submit">
                                        <i class="fas fa-save"></i>
                                        Cập nhật
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="../Assets/js/admin.js"></script>
</body>
</html> 