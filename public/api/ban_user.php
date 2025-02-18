<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

// Oturum ve yetki kontrolü
$auth = new Auth();
if (!$auth->isLoggedIn() || !($auth->getCurrentUser()['is_admin'] ?? false)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bu işlem için yetkiniz yok.']);
    exit;
}

// POST verilerini al
$data = json_decode(file_get_contents('php://input'), true);
$userId = (int)($data['user_id'] ?? 0);

// Validasyon
if (!$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

try {
    $db = Database::getInstance();
    $moderatorId = $auth->getEffectiveUserId();
    $ip = Security::getIpAddress();

    // Kullanıcıyı getir
    $user = $db->fetchOne(
        "SELECT * FROM users WHERE id = :id",
        [':id' => $userId]
    );

    if (!$user) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Kullanıcı bulunamadı.']);
        exit;
    }

    // Kullanıcı zaten banlı mı kontrol et
    if ($user['is_banned']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Kullanıcı zaten banlı.']);
        exit;
    }

    // Kullanıcıyı banla
    $db->update(
        'users',
        [
            'is_banned' => 1,
            'banned_at' => date('Y-m-d H:i:s'),
            'banned_by' => $moderatorId
        ],
        'id = :id',
        [':id' => $userId]
    );

    // Aktivite loguna kaydet
    $db->insert('activity_logs', [
        'user_id' => $moderatorId,
        'action' => 'user_ban',
        'description' => "Moderatör #{$user['username']} kullanıcısını banladı",
        'ip_address' => $ip,
        'created_at' => date('Y-m-d H:i:s')
    ]);

    echo json_encode(['success' => true, 'message' => 'Kullanıcı banlandı.']);

} catch (Exception $e) {
    error_log('Kullanıcı banlanırken hata: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.']);
} 