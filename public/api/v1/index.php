<?php
require_once '../../../includes/init.php';
require_once '../../../includes/jwt_auth.php';
require_once '../../../includes/rate_limiter.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Rate limiting
$rateLimiter = new RateLimiter();
$clientIp = $_SERVER['REMOTE_ADDR'];

if (!$rateLimiter->checkLimit($clientIp)) {
    http_response_code(429);
    echo json_encode([
        'error' => 'Too many requests',
        'reset_time' => $rateLimiter->getResetTime($clientIp),
        'remaining_requests' => $rateLimiter->getRemainingRequests($clientIp)
    ]);
    exit();
}

// JWT doğrulama
$jwtAuth = new JWTAuth();
$protected_routes = ['POST', 'PUT', 'DELETE'];

if (in_array($_SERVER['REQUEST_METHOD'], $protected_routes)) {
    $headers = getallheaders();
    $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;

    if (!$token || !($userId = $jwtAuth->validateToken($token))) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }
    $_SESSION['user_id'] = $userId;
}

// Route handling
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim(str_replace('/api/v1', '', $path), '/');
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($path) {
        case 'topics':
            require 'topics.php';
            break;
            
        case 'auth/login':
            require 'auth/login.php';
            break;
            
        case 'auth/register':
            require 'auth/register.php';
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
} 