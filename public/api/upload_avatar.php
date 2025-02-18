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

// Dosya kontrolü
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dosya yüklenirken bir hata oluştu.']);
    exit;
}

// Dosya validasyonu
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxSize = 5 * 1024 * 1024; // 5MB

if (!Security::validateUpload($_FILES['avatar'], $allowedTypes, $maxSize)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz dosya. Sadece JPG, PNG, GIF veya WEBP formatında, en fazla 5MB boyutunda dosya yükleyebilirsiniz.']);
    exit;
}

try {
    $db = Database::getInstance();
    $userId = $auth->getEffectiveUserId();
    
    // Yükleme dizinini oluştur
    $uploadDir = __DIR__ . '/../../public/uploads/avatars';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Eski avatarı sil
    $user = $db->fetchOne(
        "SELECT avatar FROM users WHERE id = :id",
        [':id' => $userId]
    );
    
    if ($user['avatar'] && file_exists($uploadDir . '/' . basename($user['avatar']))) {
        unlink($uploadDir . '/' . basename($user['avatar']));
    }
    
    // Yeni dosya adı oluştur
    $extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
    $fileName = Security::sanitizeFileName("avatar-{$userId}-" . uniqid() . ".{$extension}");
    $filePath = $uploadDir . '/' . $fileName;
    
    // Dosyayı taşı
    if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $filePath)) {
        throw new Exception('Dosya yüklenemedi.');
    }
    
    // Veritabanını güncelle
    $avatarUrl = '/uploads/avatars/' . $fileName;
    $db->update(
        'users',
        [
            'avatar' => $avatarUrl,
            'updated_at' => date('Y-m-d H:i:s')
        ],
        'id = :id',
        [':id' => $userId]
    );
    
    // Aktivite loguna kaydet
    $db->insert('activity_logs', [
        'user_id' => $userId,
        'action' => 'avatar_update',
        'description' => 'Profil fotoğrafı güncellendi',
        'ip_address' => Security::getIpAddress(),
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Profil fotoğrafınız başarıyla güncellendi.',
        'avatar_url' => $avatarUrl
    ]);

} catch (Exception $e) {
    error_log('Avatar yüklenirken hata: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.']);
} 