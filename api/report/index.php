<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'summary':
        $period = $_GET['period'] ?? 'today';
        $from_date = $_GET['from_date'] ?? null;
        $to_date = $_GET['to_date'] ?? null;

        // Tính toán thời gian
        $date_condition = getDateCondition($period, $from_date, $to_date);
        $prev_date_condition = getPrevDateCondition($period, $from_date, $to_date);

        // Tổng doanh thu
        $revenue_query = "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status != 'cancelled' AND $date_condition";
        $revenue_result = $conn->query($revenue_query);
        $total_revenue = $revenue_result->fetch_assoc()['total'];

        // Doanh thu kỳ trước
        $prev_revenue_query = "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status != 'cancelled' AND $prev_date_condition";
        $prev_revenue_result = $conn->query($prev_revenue_query);
        $prev_revenue = $prev_revenue_result->fetch_assoc()['total'];

        // Tính % thay đổi doanh thu
        $revenue_trend = $prev_revenue > 0 ? round(($total_revenue - $prev_revenue) / $prev_revenue * 100, 1) : 0;

        // Số đơn hàng
        $order_query = "SELECT COUNT(*) as count FROM orders WHERE $date_condition";
        $order_result = $conn->query($order_query);
        $order_count = $order_result->fetch_assoc()['count'];

        // Số đơn hàng kỳ trước
        $prev_order_query = "SELECT COUNT(*) as count FROM orders WHERE $prev_date_condition";
        $prev_order_result = $conn->query($prev_order_query);
        $prev_order_count = $prev_order_result->fetch_assoc()['count'];

        // Tính % thay đổi số đơn hàng
        $order_trend = $prev_order_count > 0 ? round(($order_count - $prev_order_count) / $prev_order_count * 100, 1) : 0;

        // Số sản phẩm đã bán
        $product_query = "SELECT COALESCE(SUM(oi.quantity), 0) as count 
                         FROM order_items oi 
                         JOIN orders o ON o.id = oi.order_id 
                         WHERE o.status != 'cancelled' AND $date_condition";
        $product_result = $conn->query($product_query);
        $product_count = $product_result->fetch_assoc()['count'];

        // Số sản phẩm đã bán kỳ trước
        $prev_product_query = "SELECT COALESCE(SUM(oi.quantity), 0) as count 
                             FROM order_items oi 
                             JOIN orders o ON o.id = oi.order_id 
                             WHERE o.status != 'cancelled' AND $prev_date_condition";
        $prev_product_result = $conn->query($prev_product_query);
        $prev_product_count = $prev_product_result->fetch_assoc()['count'];

        // Tính % thay đổi số sản phẩm
        $product_trend = $prev_product_count > 0 ? round(($product_count - $prev_product_count) / $prev_product_count * 100, 1) : 0;

        // Tổng kho
        $inventory_query = "SELECT COUNT(*) as count FROM inventory_in";
        $inventory_result = $conn->query($inventory_query);
        $inventory_count = $inventory_result->fetch_assoc()['count'];

        echo json_encode([
            'success' => true,
            'data' => [
                'total_revenue' => $total_revenue,
                'revenue_trend' => $revenue_trend,
                'order_count' => $order_count,
                'order_trend' => $order_trend,
                'product_count' => $product_count,
                'product_trend' => $product_trend,
                'inventory_count' => $inventory_count
            ]
        ]);
        break;

    case 'orders':
        $period = $_GET['period'] ?? 'today';
        $from_date = $_GET['from_date'] ?? null;
        $to_date = $_GET['to_date'] ?? null;
        $date_condition = getDateCondition($period, $from_date, $to_date);

        $query = "SELECT o.*, u.name as full_name, u.phone 
                 FROM orders o 
                 LEFT JOIN users u ON o.user_id = u.id 
                 WHERE $date_condition 
                 ORDER BY o.created_at DESC";
        $result = $conn->query($query);
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }

        echo json_encode(['success' => true, 'data' => $orders]);
        break;

    case 'order_detail':
        $order_id = $_GET['order_id'] ?? 0;
        
        $query = "SELECT o.*, u.name as full_name, u.phone, u.email 
                 FROM orders o 
                 LEFT JOIN users u ON o.user_id = u.id 
                 WHERE o.id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();

        if ($order) {
            $items_query = "SELECT oi.*, p.name, p.image 
                           FROM order_items oi 
                           JOIN products p ON oi.product_id = p.id 
                           WHERE oi.order_id = ?";
            $stmt = $conn->prepare($items_query);
            $stmt->bind_param('i', $order_id);
            $stmt->execute();
            $items_result = $stmt->get_result();
            $items = [];
            while ($row = $items_result->fetch_assoc()) {
                $items[] = $row;
            }
            $order['items'] = $items;
        }

        echo json_encode(['success' => true, 'data' => $order]);
        break;

    case 'chart_data':
        $type = $_GET['type'] ?? 'daily';
        $period = $_GET['period'] ?? 'today';
        $from_date = $_GET['from_date'] ?? null;
        $to_date = $_GET['to_date'] ?? null;

        $labels = [];
        $data = [];

        switch ($type) {
            case 'daily':
                $query = "SELECT DATE(created_at) as date, SUM(total_amount) as total 
                         FROM orders 
                         WHERE status != 'cancelled' AND " . getDateCondition($period, $from_date, $to_date) . "
                         GROUP BY DATE(created_at) 
                         ORDER BY date";
                $result = $conn->query($query);
                while ($row = $result->fetch_assoc()) {
                    $labels[] = date('d/m', strtotime($row['date']));
                    $data[] = $row['total'] / 1000000; // Convert to millions
                }
                break;

            case 'weekly':
                $query = "SELECT YEARWEEK(created_at) as week, SUM(total_amount) as total 
                         FROM orders 
                         WHERE status != 'cancelled' AND " . getDateCondition($period, $from_date, $to_date) . "
                         GROUP BY YEARWEEK(created_at) 
                         ORDER BY week";
                $result = $conn->query($query);
                while ($row = $result->fetch_assoc()) {
                    $labels[] = 'Tuần ' . $row['week'];
                    $data[] = $row['total'] / 1000000;
                }
                break;

            case 'monthly':
                $query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total_amount) as total 
                         FROM orders 
                         WHERE status != 'cancelled' AND " . getDateCondition($period, $from_date, $to_date) . "
                         GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
                         ORDER BY month";
                $result = $conn->query($query);
                while ($row = $result->fetch_assoc()) {
                    $labels[] = date('m/Y', strtotime($row['month'] . '-01'));
                    $data[] = $row['total'] / 1000000;
                }
                break;
        }

        echo json_encode(['success' => true, 'data' => ['labels' => $labels, 'data' => $data]]);
        break;

    case 'top_products':
        $period = $_GET['period'] ?? 'today';
        $from_date = $_GET['from_date'] ?? null;
        $to_date = $_GET['to_date'] ?? null;
        $date_condition = getDateCondition($period, $from_date, $to_date);

        $query = "SELECT p.name, c.name as category, 
                        SUM(oi.quantity) as quantity, 
                        SUM(oi.quantity * oi.price) as revenue 
                 FROM order_items oi 
                 JOIN products p ON oi.product_id = p.id 
                 JOIN categories c ON p.category_id = c.id 
                 JOIN orders o ON oi.order_id = o.id 
                 WHERE o.status != 'cancelled' AND $date_condition 
                 GROUP BY p.id 
                 ORDER BY quantity DESC 
                 LIMIT 5";
        $result = $conn->query($query);
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }

        echo json_encode(['success' => true, 'data' => $products]);
        break;

    case 'inventory':
        $query = "SELECT i.*, u.name as created_by_name 
                 FROM inventory_in i 
                 LEFT JOIN users u ON i.created_by = u.id 
                 ORDER BY i.created_at DESC";
        $result = $conn->query($query);
        $inventory = [];
        while ($row = $result->fetch_assoc()) {
            $row['total_value'] = $row['quantity'] * $row['price'];
            $inventory[] = $row;
        }

        echo json_encode(['success' => true, 'data' => $inventory]);
        break;

    case 'top_customers':
        $period = $_GET['period'] ?? 'today';
        $from_date = $_GET['from_date'] ?? null;
        $to_date = $_GET['to_date'] ?? null;
        $date_condition = getDateCondition($period, $from_date, $to_date);

        $query = "SELECT u.name as full_name, u.phone, COUNT(o.id) as order_count, SUM(o.total_amount) as total_spent
                  FROM orders o
                  JOIN users u ON o.user_id = u.id
                  WHERE o.status != 'cancelled' AND $date_condition
                  GROUP BY o.user_id
                  ORDER BY total_spent DESC
                  LIMIT 5";
        $result = $conn->query($query);
        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'SQL Error: ' . $conn->error]);
            exit;
        }
        $customers = [];
        while ($row = $result->fetch_assoc()) {
            $customers[] = $row;
        }

        echo json_encode(['success' => true, 'data' => $customers]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getDateCondition($period, $from_date = null, $to_date = null) {
    if ($from_date && $to_date) {
        return "created_at BETWEEN '$from_date 00:00:00' AND '$to_date 23:59:59'";
    }

    switch ($period) {
        case 'today':
            return "DATE(created_at) = CURDATE()";
        case 'yesterday':
            return "DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        case 'week':
            return "created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        case 'month':
            return "MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
        default:
            return "1=1";
    }
}

function getPrevDateCondition($period, $from_date = null, $to_date = null) {
    if ($from_date && $to_date) {
        $days = (strtotime($to_date) - strtotime($from_date)) / (60 * 60 * 24);
        $prev_from = date('Y-m-d', strtotime($from_date . " -$days days"));
        $prev_to = date('Y-m-d', strtotime($to_date . " -$days days"));
        return "created_at BETWEEN '$prev_from 00:00:00' AND '$prev_to 23:59:59'";
    }

    switch ($period) {
        case 'today':
            return "DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        case 'yesterday':
            return "DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 2 DAY)";
        case 'week':
            return "created_at BETWEEN DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND DATE_SUB(CURDATE(), INTERVAL 8 DAY)";
        case 'month':
            return "MONTH(created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";
        default:
            return "1=1";
    }
}
?> 