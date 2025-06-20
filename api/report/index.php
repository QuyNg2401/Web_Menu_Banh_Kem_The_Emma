<?php
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$value = $_GET['value'] ?? date('m'); // Tháng hiện tại nếu không truyền

if ($action === 'summary') {
    // Tổng doanh thu và số đơn hàng
    $sql = "SELECT COALESCE(SUM(total_amount),0) as total_revenue, COUNT(*) as order_count
            FROM orders WHERE status = 'completed' AND MONTH(created_at) = ? AND YEAR(created_at) = YEAR(CURDATE())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $value);
    $stmt->execute();
    $summary = $stmt->get_result()->fetch_assoc();

    // Số sản phẩm đã bán
    $sql2 = "SELECT COALESCE(SUM(oi.quantity),0) as product_count
             FROM order_items oi
             JOIN orders o ON o.id = oi.order_id
             WHERE o.status = 'completed' AND MONTH(o.created_at) = ? AND YEAR(o.created_at) = YEAR(CURDATE())";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("i", $value);
    $stmt2->execute();
    $product = $stmt2->get_result()->fetch_assoc();

    // Tổng chi phí dựa vào xuất kho (kiểm kho)
    $sql3 = "SELECT 
                SUM(ABS(ic.actual_quantity - ic.before_quantity) * ii.price) AS total_cost
             FROM inventory_check ic
             JOIN inventory_in ii ON ic.item_id = ii.id
             WHERE 
                MONTH(ic.created_at) = ? 
                AND YEAR(ic.created_at) = YEAR(CURDATE())
                AND ic.actual_quantity < ic.before_quantity";
    $stmt3 = $conn->prepare($sql3);
    $stmt3->bind_param("i", $value);
    $stmt3->execute();
    $cost = $stmt3->get_result()->fetch_assoc();

    echo json_encode([
        'success' => true,
        'data' => [
            'total_revenue' => $summary['total_revenue'],
            'order_count' => $summary['order_count'],
            'product_count' => $product['product_count'],
            'total_cost' => $cost['total_cost']
        ],
        'previous_data' => [
            'total_revenue' => 0,
            'order_count' => 0,
            'product_count' => 0,
            'total_cost' => 0
        ]
    ]);
    exit;
}

// Biểu đồ doanh thu theo ngày trong tháng
if ($action === 'revenue_chart') {
    $sql = "SELECT DATE(created_at) as date, SUM(total_amount) as revenue
            FROM orders
            WHERE status = 'completed' AND MONTH(created_at) = ? AND YEAR(created_at) = YEAR(CURDATE())
            GROUP BY DATE(created_at)
            ORDER BY date";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $value);
    $stmt->execute();
    $result = $stmt->get_result();

    $labels = [];
    $values = [];
    while ($row = $result->fetch_assoc()) {
        $labels[] = date('d/m', strtotime($row['date']));
        $values[] = $row['revenue'];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'labels' => $labels,
            'values' => $values
        ]
    ]);
    exit;
}

// Biểu đồ chi phí
if ($action === 'expense_chart') {
    $sql = "SELECT 
                ii.item_type,
                SUM(ABS(ic.actual_quantity - ic.before_quantity) * ii.price) AS total_cost
            FROM inventory_check ic
            JOIN inventory_in ii ON ic.item_id = ii.id
            WHERE 
                MONTH(ic.created_at) = ? 
                AND YEAR(ic.created_at) = YEAR(CURDATE())
                AND ic.actual_quantity < ic.before_quantity
            GROUP BY ii.item_type";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $value);
    $stmt->execute();
    $result = $stmt->get_result();

    $ingredients_cost = 0;
    $packaging_cost = 0;
    while ($row = $result->fetch_assoc()) {
        if ($row['item_type'] === 'ingredient') {
            $ingredients_cost = $row['total_cost'];
        } elseif ($row['item_type'] === 'packaging') {
            $packaging_cost = $row['total_cost'];
        }
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'ingredients_cost' => $ingredients_cost,
            'packaging_cost' => $packaging_cost
        ]
    ]);
    exit;
}

// Biểu đồ top sản phẩm bán chạy
if ($action === 'top_products_chart') {
    $sql = "SELECT p.name, SUM(oi.quantity) as total_quantity
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.status = 'completed' 
            AND MONTH(o.created_at) = ? 
            AND YEAR(o.created_at) = YEAR(CURDATE())
            GROUP BY p.id, p.name
            ORDER BY total_quantity DESC
            LIMIT 10";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $value);
    $stmt->execute();
    $result = $stmt->get_result();

    $labels = [];
    $values = [];
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['name'];
        $values[] = (int)$row['total_quantity'];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'labels' => $labels,
            'values' => $values
        ]
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']); 