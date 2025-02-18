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
$reportId = (int)($data['report_id'] ?? 0);
$action = $data['action'] ?? '';

// Validasyon
if (!$reportId || !in_array($action, ['resolve', 'reject'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

try {
    $db = Database::getInstance();
    $userId = $auth->getEffectiveUserId();
    $ip = Security::getIpAddress();

    // Şikayeti getir
    $report = $db->fetchOne(
        "SELECT * FROM reports WHERE id = :id",
        [':id' => $reportId]
    );

    if (!$report) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Şikayet bulunamadı.']);
        exit;
    }

    // Şikayet durumunu güncelle
    $db->update(
        'reports',
        [
            'status' => $action === 'resolve' ? 'resolved' : 'rejected',
            'moderator_id' => $userId,
            'resolved_at' => date('Y-m-d H:i:s')
        ],
        'id = :id',
        [':id' => $reportId]
    );

    // Aktivite loguna kaydet
    $db->insert('activity_logs', [
        'user_id' => $userId,
        'action' => 'report_' . $action,
        'description' => "Moderatör #{$report['content_type']} #{$report['content_id']} şikayetini " . 
                        ($action === 'resolve' ? 'çözdü' : 'reddetti'),
        'ip_address' => $ip,
        'created_at' => date('Y-m-d H:i:s')
    ]);

    echo json_encode(['success' => true, 'message' => 'Şikayet durumu güncellendi.']);

} catch (Exception $e) {
    error_log('Şikayet işlenirken hata: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.']);
} 