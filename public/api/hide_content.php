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
$contentType = $data['content_type'] ?? '';
$contentId = (int)($data['content_id'] ?? 0);

// Validasyon
if (!$contentId || !in_array($contentType, ['topic', 'comment'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

try {
    $db = Database::getInstance();
    $userId = $auth->getEffectiveUserId();
    $ip = Security::getIpAddress();

    // İçeriği gizle
    $table = $contentType . 's';
    $db->update(
        $table,
        ['is_deleted' => 1],
        'id = :id',
        [':id' => $contentId]
    );

    // Aktivite loguna kaydet
    $db->insert('activity_logs', [
        'user_id' => $userId,
        'action' => 'content_hide',
        'description' => "Moderatör $contentType #$contentId içeriğini gizledi",
        'ip_address' => $ip,
        'created_at' => date('Y-m-d H:i:s')
    ]);

    echo json_encode(['success' => true, 'message' => 'İçerik gizlendi.']);

} catch (Exception $e) {
    error_log('İçerik gizlenirken hata: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.']);
} 