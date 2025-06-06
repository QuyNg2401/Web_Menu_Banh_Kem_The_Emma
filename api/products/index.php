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
        // Lấy danh sách sản phẩm hoặc chi tiết sản phẩm
        if (isset($_GET['id'])) {
            // Lấy chi tiết sản phẩm
            $product = $db->selectOne(
                "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.id = ? AND p.status = 'active'",
                [$_GET['id']]
            );
            
            if (!$product) {
                sendError('Không tìm thấy sản phẩm', 404);
            }
            
            sendResponse($product);
        } else {
            // Lấy danh sách sản phẩm
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $offset = ($page - 1) * $limit;
            
            $where = "WHERE p.status = 'active'";
            $params = [];
            
            if (isset($_GET['category_id'])) {
                $where .= " AND p.category_id = ?";
                $params[] = $_GET['category_id'];
            }
            
            if (isset($_GET['search'])) {
                $where .= " AND (p.name LIKE ? OR p.description LIKE ?)";
                $search = "%{$_GET['search']}%";
                $params[] = $search;
                $params[] = $search;
            }
            
            if (isset($_GET['featured'])) {
                $where .= " AND p.featured = 1";
            }
            
            $orderBy = "ORDER BY p.created_at DESC";
            if (isset($_GET['sort'])) {
                switch ($_GET['sort']) {
                    case 'price_asc':
                        $orderBy = "ORDER BY p.price ASC";
                        break;
                    case 'price_desc':
                        $orderBy = "ORDER BY p.price DESC";
                        break;
                    case 'name_asc':
                        $orderBy = "ORDER BY p.name ASC";
                        break;
                    case 'name_desc':
                        $orderBy = "ORDER BY p.name DESC";
                        break;
                }
            }
            
            $products = $db->select(
                "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                {$where} 
                {$orderBy} 
                LIMIT ? OFFSET ?",
                array_merge($params, [$limit, $offset])
            );
            
            $total = $db->selectOne(
                "SELECT COUNT(*) as total FROM products p {$where}",
                $params
            )['total'];
            
            sendResponse([
                'products' => $products,
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
        // Thêm sản phẩm mới
        $data = json_decode(file_get_contents('php://input'), true);
        
        $errors = validateInput($data, [
            'name' => 'required|min:3|max:100',
            'category_id' => 'required|numeric',
            'price' => 'required|numeric',
            'description' => 'required|min:10'
        ]);
        
        if (!empty($errors)) {
            sendError('Validation failed', 422, $errors);
        }
        
        // Xử lý upload ảnh
        $image = null;
        if (isset($_FILES['image'])) {
            try {
                $image = uploadFile($_FILES['image']);
            } catch (Exception $e) {
                sendError($e->getMessage());
            }
        }
        
        // Tạo slug từ tên sản phẩm
        $slug = createSlug($data['name']);
        
        $productId = $db->insert('products', [
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'],
            'price' => $data['price'],
            'sale_price' => $data['sale_price'] ?? null,
            'image' => $image,
            'ingredients' => $data['ingredients'] ?? null,
            'weight' => $data['weight'] ?? null,
            'size' => $data['size'] ?? null,
            'status' => $data['status'] ?? 'active',
            'featured' => $data['featured'] ?? false
        ]);
        
        sendResponse([
            'message' => 'Thêm sản phẩm thành công',
            'product_id' => $productId
        ], 201);
        break;
        
    case 'PUT':
        // Cập nhật sản phẩm
        if (!isset($_GET['id'])) {
            sendError('Product ID is required');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $errors = validateInput($data, [
            'name' => 'min:3|max:100',
            'category_id' => 'numeric',
            'price' => 'numeric'
        ]);
        
        if (!empty($errors)) {
            sendError('Validation failed', 422, $errors);
        }
        
        // Kiểm tra sản phẩm tồn tại
        $product = $db->selectOne(
            "SELECT * FROM products WHERE id = ?",
            [$_GET['id']]
        );
        
        if (!$product) {
            sendError('Không tìm thấy sản phẩm', 404);
        }
        
        // Xử lý upload ảnh mới
        if (isset($_FILES['image'])) {
            try {
                $image = uploadFile($_FILES['image']);
                $data['image'] = $image;
                
                // Xóa ảnh cũ
                if ($product['image'] && file_exists(UPLOAD_DIR . $product['image'])) {
                    unlink(UPLOAD_DIR . $product['image']);
                }
            } catch (Exception $e) {
                sendError($e->getMessage());
            }
        }
        
        // Cập nhật slug nếu tên thay đổi
        if (isset($data['name']) && $data['name'] !== $product['name']) {
            $data['slug'] = createSlug($data['name']);
        }
        
        $db->update('products', $data, 'id = ?', [$_GET['id']]);
        
        sendResponse([
            'message' => 'Cập nhật sản phẩm thành công'
        ]);
        break;
        
    case 'DELETE':
        // Xóa sản phẩm
        if (!isset($_GET['id'])) {
            sendError('Product ID is required');
        }
        
        // Kiểm tra sản phẩm tồn tại
        $product = $db->selectOne(
            "SELECT * FROM products WHERE id = ?",
            [$_GET['id']]
        );
        
        if (!$product) {
            sendError('Không tìm thấy sản phẩm', 404);
        }
        
        // Xóa ảnh
        if ($product['image'] && file_exists(UPLOAD_DIR . $product['image'])) {
            unlink(UPLOAD_DIR . $product['image']);
        }
        
        $db->delete('products', 'id = ?', [$_GET['id']]);
        
        sendResponse([
            'message' => 'Xóa sản phẩm thành công'
        ]);
        break;
        
    default:
        sendError('Method not allowed', 405);
}

// Hàm tạo slug
function createSlug($str) {
    $str = mb_strtolower($str, 'UTF-8');
    $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
    $str = preg_replace('/[\s-]+/', ' ', $str);
    $str = preg_replace('/\s/', '-', $str);
    return $str;
} 