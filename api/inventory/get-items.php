<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

// Kiểm tra đăng nhập
checkAuth();

// Lấy tham số
$type = $_GET['type'] ?? '';
$categoryId = $_GET['category_id'] ?? 0;

// Validate tham số
if (!in_array($type, ['ingredient', 'packaging'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Loại vật phẩm không hợp lệ']);
    exit;
}

if (!is_numeric($categoryId)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID danh mục không hợp lệ']);
    exit;
}

try {
    // Lấy danh sách vật phẩm
    if ($type === 'ingredient') {
        $items = $db->select("
            SELECT id, name, sku, unit, current_quantity, min_quantity
            FROM ingredients
            WHERE category_id = ?
            ORDER BY name
        ", [$categoryId]);
    } else {
        $items = $db->select("
            SELECT id, name, sku, unit, current_quantity, min_quantity
            FROM packaging_items
            WHERE category_id = ?
            ORDER BY name
        ", [$categoryId]);
    }
    
    // Trả về kết quả
    header('Content-Type: application/json');
    echo json_encode($items);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
} 