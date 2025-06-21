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

$category_id = intval($_POST['category_id'] ?? 0);
$name = trim($_POST['name'] ?? '');

// Validate dữ liệu
if ($category_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID danh mục không hợp lệ']);
    exit;
}

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Tên danh mục không được để trống']);
    exit;
}

try {
    // Kiểm tra danh mục có tồn tại không
    $category = $db->selectOne("SELECT * FROM categories WHERE id = ? AND isDeleted = 0", [$category_id]);
    
    if (!$category) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy danh mục']);
        exit;
    }
    
    // Cập nhật thông tin danh mục
    $db->update('categories', [
        'name' => $name,
        'updated_at' => date('Y-m-d H:i:s')
    ], ['id' => $category_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật danh mục thành công!'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}
?> 