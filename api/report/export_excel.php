<?php
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=bao_cao_doanh_thu.csv');

// Thêm BOM để Excel nhận đúng UTF-8
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');
$delimiter = ';';
fputcsv($output, ['Ngày', 'Doanh thu', 'Số đơn hàng', 'Sản phẩm đã bán', 'Tổng chi phí'], $delimiter);

$month = $_GET['month'] ?? date('m');
$year = date('Y');

$sql = "SELECT DATE(created_at) as ngay, 
               SUM(total_amount) as doanh_thu, 
               COUNT(*) as so_don, 
               (SELECT SUM(oi.quantity) FROM order_items oi JOIN orders o2 ON o2.id = oi.order_id WHERE o2.status = 'completed' AND DATE(o2.created_at) = DATE(o.created_at)) as so_san_pham
        FROM orders o
        WHERE status = 'completed' AND MONTH(created_at) = ? AND YEAR(created_at) = ?
        GROUP BY DATE(created_at)
        ORDER BY ngay";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $month, $year);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $sql2 = "SELECT SUM(price) as chi_phi FROM inventory_in WHERE DATE(created_at) = ?";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("s", $row['ngay']);
    $stmt2->execute();
    $cost = $stmt2->get_result()->fetch_assoc();
    fputcsv($output, [
        $row['ngay'],
        $row['doanh_thu'],
        $row['so_don'],
        $row['so_san_pham'],
        $cost['chi_phi'] ?? 0
    ], $delimiter);
}
fclose($output);
exit; 