<?php
require_once __DIR__ . '/../config.php';

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['action'])) {
            sendError('Action is required');
        }
        
        switch ($data['action']) {
            case 'login':
                // Đăng nhập
                $errors = validateInput($data, [
                    'email' => 'required|email',
                    'password' => 'required'
                ]);
                
                if (!empty($errors)) {
                    sendError('Validation failed', 422, $errors);
                }
                
                // Tìm người dùng theo email
                $user = $db->selectOne(
                    "SELECT * FROM users WHERE email = ?",
                    [$data['email']]
                );
                
                if (!$user || !password_verify($data['password'], $user['password'])) {
                    sendError('Email hoặc mật khẩu không đúng');
                }
                
                if ($user['status'] !== 'active') {
                    sendError('Tài khoản đã bị khóa');
                }
                
                // Tạo token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+7 days'));
                
                // Lưu token vào database
                $db->insert('user_tokens', [
                    'user_id' => $user['id'],
                    'token' => $token,
                    'expires_at' => $expires
                ]);
                
                // Xóa mật khẩu trước khi trả về
                unset($user['password']);
                
                sendResponse([
                    'message' => 'Đăng nhập thành công',
                    'token' => $token,
                    'user' => $user
                ]);
                break;
                
            case 'logout':
                // Đăng xuất
                if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
                    sendError('Token is required');
                }
                
                $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
                
                // Xóa token
                $db->delete('user_tokens', 'token = ?', [$token]);
                
                sendResponse([
                    'message' => 'Đăng xuất thành công'
                ]);
                break;
                
            case 'forgot-password':
                // Quên mật khẩu
                $errors = validateInput($data, [
                    'email' => 'required|email'
                ]);
                
                if (!empty($errors)) {
                    sendError('Validation failed', 422, $errors);
                }
                
                // Kiểm tra email tồn tại
                $user = $db->selectOne(
                    "SELECT * FROM users WHERE email = ?",
                    [$data['email']]
                );
                
                if (!$user) {
                    sendError('Email không tồn tại');
                }
                
                // Tạo mã reset password
                $resetToken = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Lưu mã reset vào database
                $db->insert('password_resets', [
                    'user_id' => $user['id'],
                    'token' => $resetToken,
                    'expires_at' => $expires
                ]);
                
                // Gửi email reset password
                $resetLink = SITE_URL . '/reset-password?token=' . $resetToken;
                $to = $user['email'];
                $subject = 'Reset mật khẩu';
                $message = "Xin chào {$user['name']},\n\n";
                $message .= "Bạn đã yêu cầu reset mật khẩu. Vui lòng click vào link sau để đặt lại mật khẩu:\n";
                $message .= $resetLink . "\n\n";
                $message .= "Link này sẽ hết hạn sau 1 giờ.\n\n";
                $message .= "Nếu bạn không yêu cầu reset mật khẩu, vui lòng bỏ qua email này.\n\n";
                $message .= "Trân trọng,\n";
                $message .= SITE_NAME;
                
                $headers = "From: " . ADMIN_EMAIL . "\r\n";
                $headers .= "Reply-To: " . ADMIN_EMAIL . "\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion();
                
                mail($to, $subject, $message, $headers);
                
                sendResponse([
                    'message' => 'Vui lòng kiểm tra email để reset mật khẩu'
                ]);
                break;
                
            case 'reset-password':
                // Reset mật khẩu
                $errors = validateInput($data, [
                    'token' => 'required',
                    'password' => 'required|min:6'
                ]);
                
                if (!empty($errors)) {
                    sendError('Validation failed', 422, $errors);
                }
                
                // Kiểm tra token hợp lệ
                $reset = $db->selectOne(
                    "SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()",
                    [$data['token']]
                );
                
                if (!$reset) {
                    sendError('Token không hợp lệ hoặc đã hết hạn');
                }
                
                // Cập nhật mật khẩu mới
                $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
                $db->update('users', [
                    'password' => $hashedPassword
                ], 'id = ?', [$reset['user_id']]);
                
                // Xóa token đã sử dụng
                $db->delete('password_resets', 'token = ?', [$data['token']]);
                
                sendResponse([
                    'message' => 'Đặt lại mật khẩu thành công'
                ]);
                break;
                
            default:
                sendError('Invalid action');
        }
        break;
        
    case 'GET':
        // Kiểm tra token
        if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
            sendError('Token is required');
        }
        
        $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
        
        // Kiểm tra token hợp lệ
        $userToken = $db->selectOne(
            "SELECT ut.*, u.* 
            FROM user_tokens ut 
            JOIN users u ON ut.user_id = u.id 
            WHERE ut.token = ? AND ut.expires_at > NOW()",
            [$token]
        );
        
        if (!$userToken) {
            sendError('Token không hợp lệ hoặc đã hết hạn');
        }
        
        // Xóa mật khẩu trước khi trả về
        unset($userToken['password']);
        
        sendResponse([
            'user' => $userToken
        ]);
        break;
        
    default:
        sendError('Method not allowed', 405);
} 