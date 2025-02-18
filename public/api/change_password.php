<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/security.php';

header('Content-Type: application/json');

// Oturum kontrolü
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bu işlem için giriş yapmanız gerekiyor.']);
    exit;
}

// POST verilerini al
$data = json_decode(file_get_contents('php://input'), true);
$currentPassword = $data['current_password'] ?? '';
$newPassword = $data['new_password'] ?? '';
$confirmPassword = $data['confirm_password'] ?? '';

// Validasyon
if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tüm alanları doldurun.']);
    exit;
}

if ($newPassword !== $confirmPassword) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Yeni şifreler eşleşmiyor.']);
    exit;
}

if (strlen($newPassword) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Şifre en az 6 karakter olmalıdır.']);
    exit;
}

try {
    $db = Database::getInstance();
    $userId = $auth->getEffectiveUserId();
    
    // Mevcut şifreyi kontrol et
    $user = $db->fetchOne(
        "SELECT password FROM users WHERE id = :id",
        [':id' => $userId]
    );
    
    if (!Security::verifyPassword($currentPassword, $user['password'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Mevcut şifre hatalı.']);
        exit;
    }
    
    // Yeni şifreyi hashle
    $hashedPassword = Security::hashPassword($newPassword);
    
    // Şifreyi güncelle
    $db->update(
        'users',
        [
            'password' => $hashedPassword,
            'updated_at' => date('Y-m-d H:i:s')
        ],
        'id = :id',
        [':id' => $userId]
    );
    
    // Aktivite loguna kaydet
    $db->insert('activity_logs', [
        'user_id' => $userId,
        'action' => 'password_change',
        'description' => 'Şifre değiştirildi',
        'ip_address' => Security::getIpAddress(),
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Şifreniz başarıyla değiştirildi.'
    ]);

} catch (Exception $e) {
    error_log('Şifre değiştirilirken hata: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.']);
} 