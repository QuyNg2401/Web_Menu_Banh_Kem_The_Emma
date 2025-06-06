-- Tạo tài khoản admin
INSERT INTO users (
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
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
    '0123456789',
    '123 Đường ABC, Quận XYZ, TP.HCM',
    'admin',
    'active',
    NOW()
); 