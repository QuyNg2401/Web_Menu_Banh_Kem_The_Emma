<?php
require_once __DIR__ . '/../config.php';

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

// Kiểm tra API key cho các request không phải GET
if ($method !== 'GET') {
    checkApiKey();
}

switch ($method) {
    case 'GET':
        // Lấy danh sách đơn hàng hoặc chi tiết đơn hàng
        if (isset($_GET['id'])) {
            // Lấy chi tiết đơn hàng
            $order = $db->selectOne(
                "SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                WHERE o.id = ?",
                [$_GET['id']]
            );
            
            if (!$order) {
                sendError('Không tìm thấy đơn hàng', 404);
            }
            
            // Lấy chi tiết các sản phẩm trong đơn hàng
            $order['items'] = $db->select(
                "SELECT oi.*, p.name as product_name, p.image as product_image 
                FROM order_items oi 
                LEFT JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?",
                [$order['id']]
            );
            
            sendResponse($order);
        } else {
            // Lấy danh sách đơn hàng
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $offset = ($page - 1) * $limit;
            
            $where = "WHERE 1=1";
            $params = [];
            
            if (isset($_GET['user_id'])) {
                $where .= " AND o.user_id = ?";
                $params[] = $_GET['user_id'];
            }
            
            if (isset($_GET['status'])) {
                $where .= " AND o.status = ?";
                $params[] = $_GET['status'];
            }
            
            if (isset($_GET['date_from'])) {
                $where .= " AND o.created_at >= ?";
                $params[] = $_GET['date_from'];
            }
            
            if (isset($_GET['date_to'])) {
                $where .= " AND o.created_at <= ?";
                $params[] = $_GET['date_to'];
            }
            
            $orderBy = "ORDER BY o.created_at DESC";
            
            $orders = $db->select(
                "SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                {$where} 
                {$orderBy} 
                LIMIT ? OFFSET ?",
                array_merge($params, [$limit, $offset])
            );
            
            $total = $db->selectOne(
                "SELECT COUNT(*) as total FROM orders o {$where}",
                $params
            )['total'];
            
            sendResponse([
                'orders' => $orders,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'total_pages' => ceil($total / $limit)
                ]
            ]);
        }
        break;
        
    case 'POST':
        // Tạo đơn hàng mới
        $data = json_decode(file_get_contents('php://input'), true);
        
        $errors = validateInput($data, [
            'user_id' => 'required|numeric',
            'items' => 'required|array',
            'items.*.product_id' => 'required|numeric',
            'items.*.quantity' => 'required|numeric|min:1',
            'shipping_address' => 'required',
            'payment_method' => 'required'
        ]);
        
        if (!empty($errors)) {
            sendError('Validation failed', 422, $errors);
        }
        
        try {
            $db->beginTransaction();
            
            // Tạo đơn hàng
            $orderId = $db->insert('orders', [
                'user_id' => $data['user_id'],
                'total_amount' => 0, // Sẽ cập nhật sau
                'shipping_address' => $data['shipping_address'],
                'payment_method' => $data['payment_method'],
                'status' => 'pending',
                'notes' => $data['notes'] ?? null
            ]);
            
            $totalAmount = 0;
            
            // Thêm các sản phẩm vào đơn hàng
            foreach ($data['items'] as $item) {
                // Kiểm tra sản phẩm tồn tại
                $product = $db->selectOne(
                    "SELECT * FROM products WHERE id = ? AND status = 'active'",
                    [$item['product_id']]
                );
                
                if (!$product) {
                    throw new Exception("Sản phẩm không tồn tại hoặc đã bị vô hiệu hóa");
                }
                
                // Tính giá sản phẩm (ưu tiên giá khuyến mãi nếu có)
                $price = $product['sale_price'] ?? $product['price'];
                $subtotal = $price * $item['quantity'];
                $totalAmount += $subtotal;
                
                $db->insert('order_items', [
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $price,
                    'subtotal' => $subtotal
                ]);
            }
            
            // Cập nhật tổng tiền đơn hàng
            $db->update('orders', [
                'total_amount' => $totalAmount
            ], 'id = ?', [$orderId]);
            
            $db->commit();
            
            sendResponse([
                'message' => 'Tạo đơn hàng thành công',
                'order_id' => $orderId
            ], 201);
            
        } catch (Exception $e) {
            $db->rollback();
            sendError($e->getMessage());
        }
        break;
        
    case 'PUT':
        // Cập nhật trạng thái đơn hàng
        if (!isset($_GET['id'])) {
            sendError('Order ID is required');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $errors = validateInput($data, [
            'status' => 'required'
        ]);
        
        if (!empty($errors)) {
            sendError('Validation failed', 422, $errors);
        }
        
        // Kiểm tra đơn hàng tồn tại
        $order = $db->selectOne(
            "SELECT * FROM orders WHERE id = ?",
            [$_GET['id']]
        );
        
        if (!$order) {
            sendError('Không tìm thấy đơn hàng', 404);
        }
        
        // Cập nhật trạng thái
        $db->update('orders', [
            'status' => $data['status'],
            'notes' => $data['notes'] ?? $order['notes']
        ], 'id = ?', [$_GET['id']]);
        
        sendResponse([
            'message' => 'Cập nhật đơn hàng thành công'
        ]);
        break;
        
    case 'DELETE':
        // Xóa đơn hàng (chỉ cho phép xóa đơn hàng ở trạng thái pending)
        if (!isset($_GET['id'])) {
            sendError('Order ID is required');
        }
        
        // Kiểm tra đơn hàng tồn tại
        $order = $db->selectOne(
            "SELECT * FROM orders WHERE id = ?",
            [$_GET['id']]
        );
        
        if (!$order) {
            sendError('Không tìm thấy đơn hàng', 404);
        }
        
        if ($order['status'] !== 'pending') {
            sendError('Chỉ có thể xóa đơn hàng ở trạng thái chờ xử lý');
        }
        
        try {
            $db->beginTransaction();
            
            // Xóa các sản phẩm trong đơn hàng
            $db->delete('order_items', 'order_id = ?', [$_GET['id']]);
            
            // Xóa đơn hàng
            $db->delete('orders', 'id = ?', [$_GET['id']]);
            
            $db->commit();
            
            sendResponse([
                'message' => 'Xóa đơn hàng thành công'
            ]);
            
        } catch (Exception $e) {
            $db->rollback();
            sendError($e->getMessage());
        }
        break;
        
    default:
        sendError('Method not allowed', 405);
} 