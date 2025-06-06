<?php
require_once '../includes/config.php';

// Tạo mật khẩu mới
$password = '123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Xóa tài khoản admin cũ nếu có
$sql = "DELETE FROM users WHERE email = 'admin@theemma.com'";
$conn->query($sql);

// Tạo tài khoản admin mới
$sql = "INSERT INTO users (
    name,
    email,
    password,
    phone,
    address,
    role,
    status,
    created_at
) VALUES (
    'Admin',
    'admin@theemma.com',
    ?,
    '0123456789',
    '123 Đường ABC, Quận XYZ, TP.HCM',
    'admin',
    'active',
    NOW()
)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $hashed_password);

if ($stmt->execute()) {
    echo "Tài khoản admin đã được tạo thành công!<br>";
    echo "Email: admin@theemma.com<br>";
    echo "Password: 123";
} else {
    echo "Lỗi khi tạo tài khoản admin: " . $conn->error;
}

$stmt->close();
$conn->close();
?> 