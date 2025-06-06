<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Kiểm tra xem người dùng đã đăng nhập chưa
 */
function isLoggedIn() {
    return isset($_SESSION['token']) && isset($_SESSION['user_id']);
}

/**
 * Kiểm tra quyền truy cập admin
 */
function checkAuth() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
    
    global $db;
    
    try {
        // Kiểm tra token
        $token = $db->selectOne(
            "SELECT * FROM user_tokens 
            WHERE token = ? AND expires_at > NOW()",
            [$_SESSION['token']]
        );
        
        if (!$token) {
            throw new Exception('Token không hợp lệ hoặc đã hết hạn');
        }
        
        // Lấy thông tin user
        $user = $db->selectOne(
            "SELECT * FROM users WHERE id = ? AND role = 'admin' AND status = 'active'",
            [$_SESSION['user_id']]
        );
        
        if (!$user) {
            throw new Exception('Tài khoản không tồn tại hoặc đã bị khóa');
        }
        
        return $user;
    } catch (Exception $e) {
        // Xóa session nếu có lỗi
        session_destroy();
        header('Location: login.php');
        exit;
    }
}

/**
 * Lấy thông tin người dùng hiện tại
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $db;
    
    try {
        return $db->selectOne(
            "SELECT * FROM users WHERE id = ?",
            [$_SESSION['user_id']]
        );
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Đăng xuất
 */
function logout() {
    global $db;
    
    if (isset($_SESSION['token'])) {
        // Xóa token trong database
        $db->delete(
            "DELETE FROM user_tokens WHERE token = ?",
            [$_SESSION['token']]
        );
    }
    
    // Xóa session
    session_destroy();
}
