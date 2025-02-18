<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/auth.php';

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
$bio = trim($data['bio'] ?? '');
$website = trim($data['website'] ?? '');
$location = trim($data['location'] ?? '');

try {
    $db = Database::getInstance();
    $userId = $auth->getEffectiveUserId();
    
    // Website URL'sini doğrula
    if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Geçersiz website adresi.']);
        exit;
    }
    
    // Profili güncelle
    $db->update(
        'users',
        [
            'bio' => $bio,
            'website' => $website,
            'location' => $location,
            'updated_at' => date('Y-m-d H:i:s')
        ],
        'id = :id',
        [':id' => $userId]
    );
    
    // Güncel kullanıcı bilgilerini getir
    $user = $db->fetchOne(
        "SELECT username, email, bio, website, location, avatar 
        FROM users WHERE id = :id",
        [':id' => $userId]
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Profil başarıyla güncellendi.',
        'user' => $user
    ]);

} catch (Exception $e) {
    error_log('Profil güncellenirken hata: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.']);
} 