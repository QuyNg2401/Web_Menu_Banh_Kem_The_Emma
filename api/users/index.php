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
        // Lấy danh sách người dùng hoặc chi tiết người dùng
        if (isset($_GET['id'])) {
            // Lấy chi tiết người dùng
            $user = $db->selectOne(
                "SELECT id, name, email, phone, address, role, status, created_at 
                FROM users WHERE id = ?",
                [$_GET['id']]
            );
            
            if (!$user) {
                sendError('Không tìm thấy người dùng', 404);
            }
            
            sendResponse($user);
        } else {
            // Lấy danh sách người dùng
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $offset = ($page - 1) * $limit;
            
            $where = "WHERE 1=1";
            $params = [];
            
            if (isset($_GET['role'])) {
                $where .= " AND role = ?";
                $params[] = $_GET['role'];
            }
            
            if (isset($_GET['status'])) {
                $where .= " AND status = ?";
                $params[] = $_GET['status'];
            }
            
            if (isset($_GET['search'])) {
                $where .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
                $search = "%{$_GET['search']}%";
                $params[] = $search;
                $params[] = $search;
                $params[] = $search;
            }
            
            $orderBy = "ORDER BY created_at DESC";
            
            $users = $db->select(
                "SELECT id, name, email, phone, address, role, status, created_at 
                FROM users 
                {$where} 
                {$orderBy} 
                LIMIT ? OFFSET ?",
                array_merge($params, [$limit, $offset])
            );
            
            $total = $db->selectOne(
                "SELECT COUNT(*) as total FROM users {$where}",
                $params
            )['total'];
            
            sendResponse([
                'users' => $users,
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
        // Đăng ký người dùng mới
        $data = json_decode(file_get_contents('php://input'), true);
        
        $errors = validateInput($data, [
            'name' => 'required|min:3|max:100',
            'email' => 'required|email',
            'password' => 'required|min:6',
            'phone' => 'required|phone',
            'address' => 'required'
        ]);
        
        if (!empty($errors)) {
            sendError('Validation failed', 422, $errors);
        }
        
        // Kiểm tra email đã tồn tại
        $existingUser = $db->selectOne(
            "SELECT id FROM users WHERE email = ?",
            [$data['email']]
        );
        
        if ($existingUser) {
            sendError('Email đã được sử dụng');
        }
        
        // Mã hóa mật khẩu
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $userId = $db->insert('users', [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $hashedPassword,
            'phone' => $data['phone'],
            'address' => $data['address'],
            'role' => $data['role'] ?? 'customer',
            'status' => $data['status'] ?? 'active'
        ]);
        
        sendResponse([
            'message' => 'Đăng ký thành công',
            'user_id' => $userId
        ], 201);
        break;
        
    case 'PUT':
        // Cập nhật thông tin người dùng
        if (!isset($_GET['id'])) {
            sendError('User ID is required');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $errors = validateInput($data, [
            'name' => 'min:3|max:100',
            'email' => 'email',
            'phone' => 'phone'
        ]);
        
        if (!empty($errors)) {
            sendError('Validation failed', 422, $errors);
        }
        
        // Kiểm tra người dùng tồn tại
        $user = $db->selectOne(
            "SELECT * FROM users WHERE id = ?",
            [$_GET['id']]
        );
        
        if (!$user) {
            sendError('Không tìm thấy người dùng', 404);
        }
        
        // Kiểm tra email mới có bị trùng không
        if (isset($data['email']) && $data['email'] !== $user['email']) {
            $existingUser = $db->selectOne(
                "SELECT id FROM users WHERE email = ? AND id != ?",
                [$data['email'], $_GET['id']]
            );
            
            if ($existingUser) {
                sendError('Email đã được sử dụng');
            }
        }
        
        // Cập nhật mật khẩu nếu có
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        $db->update('users', $data, 'id = ?', [$_GET['id']]);
        
        sendResponse([
            'message' => 'Cập nhật thông tin thành công'
        ]);
        break;
        
    case 'DELETE':
        // Xóa người dùng
        if (!isset($_GET['id'])) {
            sendError('User ID is required');
        }
        
        // Kiểm tra người dùng tồn tại
        $user = $db->selectOne(
            "SELECT * FROM users WHERE id = ?",
            [$_GET['id']]
        );
        
        if (!$user) {
            sendError('Không tìm thấy người dùng', 404);
        }
        
        // Không cho phép xóa tài khoản admin
        if ($user['role'] === 'admin') {
            sendError('Không thể xóa tài khoản admin');
        }
        
        try {
            $db->beginTransaction();
            
            // Xóa các đơn hàng của người dùng
            $orders = $db->select(
                "SELECT id FROM orders WHERE user_id = ?",
                [$_GET['id']]
            );
            
            foreach ($orders as $order) {
                $db->delete('order_items', 'order_id = ?', [$order['id']]);
                $db->delete('orders', 'id = ?', [$order['id']]);
            }
            
            // Xóa người dùng
            $db->delete('users', 'id = ?', [$_GET['id']]);
            
            $db->commit();
            
            sendResponse([
                'message' => 'Xóa người dùng thành công'
            ]);
            
        } catch (Exception $e) {
            $db->rollback();
            sendError($e->getMessage());
        }
        break;
        
    default:
        sendError('Method not allowed', 405);
} 