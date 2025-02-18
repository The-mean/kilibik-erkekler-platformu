<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/rate_limiter.php';

header('Content-Type: application/json');

// POST verilerini al
$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

// Validasyon
if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Kullanıcı adı ve şifre gereklidir.']);
    exit;
}

// Rate limiting kontrolü
$rateLimiter = new RateLimiter();
$ip = Security::getIpAddress();
if (!$rateLimiter->checkLimit($ip, 'login')) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Çok fazla giriş denemesi yaptınız. Lütfen bir süre bekleyin.']);
    exit;
}

try {
    $auth = new Auth();
    
    if ($auth->login($username, $password)) {
        $user = $auth->getCurrentUser();
        echo json_encode([
            'success' => true,
            'message' => 'Giriş başarılı.',
            'user' => [
                'username' => $user['username'],
                'email' => $user['email'],
                'avatar' => $user['avatar'],
                'is_admin' => (bool)$user['is_admin']
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Kullanıcı adı veya şifre hatalı.']);
    }
} catch (Exception $e) {
    error_log('Giriş hatası: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.']);
} 