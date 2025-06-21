<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

// Kiểm tra đăng nhập
checkAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
    exit;
}

$user_id = intval($_POST['user_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$role = $_POST['role'] ?? 'user';
$hourly_rate = floatval($_POST['hourly_rate'] ?? 0);

// Validate dữ liệu
if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID nhân viên không hợp lệ']);
    exit;
}

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Tên nhân viên không được để trống']);
    exit;
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email không hợp lệ']);
    exit;
}

if (!in_array($role, ['admin', 'user'])) {
    echo json_encode(['success' => false, 'message' => 'Vai trò không hợp lệ']);
    exit;
}

try {
    // Kiểm tra email đã tồn tại chưa (trừ nhân viên hiện tại)
    $existingUser = $db->selectOne(
        "SELECT id FROM users WHERE email = ? AND id != ? AND isDeleted = 0",
        [$email, $user_id]
    );
    
    if ($existingUser) {
        echo json_encode(['success' => false, 'message' => 'Email đã được sử dụng bởi nhân viên khác']);
        exit;
    }
    
    // Cập nhật thông tin nhân viên
    $updateData = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'role' => $role,
        'hourly_rate' => $hourly_rate
    ];
    
    $db->update('users', $updateData, ['id' => $user_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật nhân viên thành công!'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}
?> 