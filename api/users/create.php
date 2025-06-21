<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

// Chỉ cho phép admin thực hiện
if (getCurrentUser()['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện hành động này.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ.']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$phone = trim($_POST['phone'] ?? null);
$role = $_POST['role'] ?? 'user';
$hourly_rate = $_POST['hourly_rate'] ?? 0;

if (empty($name) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ các trường bắt buộc (Tên, Email, Mật khẩu).']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Địa chỉ email không hợp lệ.']);
    exit;
}

// Kiểm tra email đã tồn tại chưa
$existingUser = $db->selectOne("SELECT id FROM users WHERE email = ? AND isDeleted = 0", [$email]);
if ($existingUser) {
    echo json_encode(['success' => false, 'message' => 'Email này đã được sử dụng. Vui lòng chọn email khác.']);
    exit;
}

// Mã hóa mật khẩu
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    $db->insert('users', [
        'name' => $name,
        'email' => $email,
        'password' => $hashedPassword,
        'phone' => $phone,
        'role' => $role,
        'hourly_rate' => $hourly_rate,
        'isDeleted' => 0
    ]);

    // Không cần set session ở đây vì JS đã xử lý thông báo
    echo json_encode(['success' => true, 'message' => 'Thêm nhân viên thành công!']);

} catch (Exception $e) {
    // Log lỗi để debug
    error_log('User creation error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau.']);
} 