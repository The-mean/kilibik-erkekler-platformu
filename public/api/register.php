<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/rate_limiter.php';

header('Content-Type: application/json');

// POST verilerini al
$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'] ?? '';
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';
$confirm_password = $data['confirm_password'] ?? '';

// Validasyon
if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tüm alanlar gereklidir.']);
    exit;
}

if ($password !== $confirm_password) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Şifreler eşleşmiyor.']);
    exit;
}

if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Şifre en az 6 karakter olmalıdır.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz e-posta adresi.']);
    exit;
}

// Rate limiting kontrolü
$rateLimiter = new RateLimiter();
$ip = Security::getIpAddress();
if (!$rateLimiter->checkLimit($ip, 'register')) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Çok fazla kayıt denemesi yaptınız. Lütfen bir süre bekleyin.']);
    exit;
}

try {
    $auth = new Auth();
    
    if ($auth->register($username, $email, $password)) {
        // Otomatik giriş yap
        $auth->login($username, $password);
        $user = $auth->getCurrentUser();
        
        echo json_encode([
            'success' => true,
            'message' => 'Kayıt başarılı.',
            'user' => [
                'username' => $user['username'],
                'email' => $user['email'],
                'avatar' => $user['avatar'],
                'is_admin' => (bool)$user['is_admin']
            ]
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Bu kullanıcı adı veya e-posta adresi zaten kullanılıyor.']);
    }
} catch (Exception $e) {
    error_log('Kayıt hatası: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.']);
} 