<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cấu hình database
define('DB_HOST', 'localhost');
define('DB_NAME', 'theemma');
define('DB_USER', 'root');
define('DB_PASS', '');

// Cấu hình website
define('SITE_NAME', 'The Emma');
define('SITE_URL', 'http://localhost/theemma');
define('ADMIN_URL', SITE_URL . '/admin');
define('UPLOAD_DIR', __DIR__ . '/../uploads');

// Tạo thư mục uploads nếu chưa tồn tại
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Class Database
class Database {
    private $pdo;
    
    public function __construct() {
        try {
            $this->pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Lỗi kết nối database: " . $e->getMessage());
        }
    }
    
    public function select($query, $params = []) {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function selectOne($query, $params = []) {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    public function insert($table, $data = []) {
        $fields = array_keys($data);
        $placeholders = array_map(function($f) { return ':' . $f; }, $fields);
        $sql = "INSERT INTO `$table` (" . implode(',', $fields) . ") VALUES (" . implode(',', $placeholders) . ")";
        $stmt = $this->pdo->prepare($sql);
        foreach ($data as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->execute();
        return $this->pdo->lastInsertId();
    }
    
    public function update($table, $data = [], $where = []) {
        $fields = array_keys($data);
        $set = implode(', ', array_map(function($f) { return "`$f` = :set_$f"; }, $fields));
        $whereFields = array_keys($where);
        $whereClause = implode(' AND ', array_map(function($f) { return "`$f` = :where_$f"; }, $whereFields));
        $sql = "UPDATE `$table` SET $set WHERE $whereClause";
        $stmt = $this->pdo->prepare($sql);
        foreach ($data as $key => $value) {
            $stmt->bindValue(':set_' . $key, $value);
        }
        foreach ($where as $key => $value) {
            $stmt->bindValue(':where_' . $key, $value);
        }
        return $stmt->execute();
    }
    
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM `$table` WHERE $where";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }
}

// Khởi tạo kết nối database
$db = new Database();

// Site configuration
define('ADMIN_EMAIL', 'theemma1905@gmail.com');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Time zone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Security
define('HASH_SALT', 'the_emma_salt_2024');

// File upload configuration
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

// API configuration
define('API_KEY', 'the_emma_api_key_2024');
define('API_URL', 'http://localhost/the_emma/api');

// Cart configuration
define('CART_EXPIRY', 24 * 60 * 60); // 24 hours

// Order status
define('ORDER_STATUS', [
    'pending' => 'Chờ xác nhận',
    'confirmed' => 'Đã xác nhận',
    'completed' => 'Hoàn thành',
    'cancelled' => 'Hủy đơn hàng'
]);

// Payment methods
define('PAYMENT_METHODS', [
    'cod' => 'Thanh toán khi nhận hàng',
    'banking' => 'Chuyển khoản ngân hàng',
    'momo' => 'Ví MoMo',
    'zalopay' => 'Ví ZaloPay'
]);

// Khởi tạo kết nối MySQLi cho các API sử dụng MySQLi
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Kết nối MySQLi thất bại: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
