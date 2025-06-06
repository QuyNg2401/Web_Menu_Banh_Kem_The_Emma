<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

// Xử lý CORS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Kiểm tra API key
function checkApiKey() {
    $headers = getallheaders();
    $apiKey = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;
    
    if (!$apiKey || $apiKey !== API_KEY) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }
}

// Xử lý response
function sendResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit();
}

// Xử lý error
function sendError($message, $status = 400) {
    http_response_code($status);
    echo json_encode(['error' => $message]);
    exit();
}

// Validate input
function validateInput($data, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $rule) {
        if (strpos($rule, 'required') !== false && (!isset($data[$field]) || empty($data[$field]))) {
            $errors[$field] = 'Trường này là bắt buộc';
            continue;
        }
        
        if (isset($data[$field]) && !empty($data[$field])) {
            if (strpos($rule, 'email') !== false && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = 'Email không hợp lệ';
            }
            
            if (strpos($rule, 'numeric') !== false && !is_numeric($data[$field])) {
                $errors[$field] = 'Giá trị phải là số';
            }
            
            if (strpos($rule, 'min:') !== false) {
                preg_match('/min:(\d+)/', $rule, $matches);
                $min = $matches[1];
                if (strlen($data[$field]) < $min) {
                    $errors[$field] = "Độ dài tối thiểu là {$min} ký tự";
                }
            }
            
            if (strpos($rule, 'max:') !== false) {
                preg_match('/max:(\d+)/', $rule, $matches);
                $max = $matches[1];
                if (strlen($data[$field]) > $max) {
                    $errors[$field] = "Độ dài tối đa là {$max} ký tự";
                }
            }
        }
    }
    
    return $errors;
}

// Upload file
function uploadFile($file, $allowedTypes = ALLOWED_IMAGE_TYPES, $maxSize = MAX_FILE_SIZE) {
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new Exception('Invalid file parameters');
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new Exception('File size exceeds limit');
        case UPLOAD_ERR_PARTIAL:
            throw new Exception('File was only partially uploaded');
        case UPLOAD_ERR_NO_FILE:
            throw new Exception('No file was uploaded');
        case UPLOAD_ERR_NO_TMP_DIR:
            throw new Exception('Missing a temporary folder');
        case UPLOAD_ERR_CANT_WRITE:
            throw new Exception('Failed to write file to disk');
        case UPLOAD_ERR_EXTENSION:
            throw new Exception('A PHP extension stopped the file upload');
        default:
            throw new Exception('Unknown upload error');
    }

    if ($file['size'] > $maxSize) {
        throw new Exception('File size exceeds limit');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $allowedTypes)) {
        throw new Exception('Invalid file type');
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $extension;
    $uploadPath = UPLOAD_DIR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Failed to move uploaded file');
    }

    return $filename;
} 