<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

// Kiểm tra đăng nhập
checkAuth();

header('Content-Type: application/json');

$customer_id = intval($_GET['customer_id'] ?? 0);

if ($customer_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID khách hàng không hợp lệ']);
    exit;
}

try {
    // Lấy thông tin khách hàng
    $customer = $db->selectOne("SELECT * FROM customers WHERE id = ?", [$customer_id]);
    
    if (!$customer) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy khách hàng']);
        exit;
    }
    
    // Lấy lịch sử đơn hàng
    $orders = $db->select("
        SELECT o.*, 
               COUNT(oi.id) as total_items
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.customer_id = ? AND o.isDeleted = 0
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ", [$customer_id]);
    
    echo json_encode([
        'success' => true,
        'customer' => $customer,
        'orders' => $orders
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}
?> 