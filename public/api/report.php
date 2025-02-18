<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/rate_limiter.php';

header('Content-Type: application/json');

// Oturum kontrolü
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bu işlem için giriş yapmanız gerekiyor.']);
    exit;
}

// Şikayet sebepleri
$validReasons = [
    'hakaret' => 'Hakaret/Küfür',
    'spam' => 'Spam/Reklam',
    'yaniltici' => 'Yanıltıcı Bilgi',
    'nefret' => 'Nefret Söylemi',
    'taciz' => 'Taciz/Zorbalık',
    'telif' => 'Telif Hakkı İhlali',
    'diger' => 'Diğer'
];

// POST verilerini al
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['content_type']) || !isset($data['content_id']) || !isset($data['reason'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

$contentType = $data['content_type'];
$contentId = (int)$data['content_id'];
$reason = $data['reason'];
$description = $data['description'] ?? '';

// Şikayet sebebini kontrol et
if (!array_key_exists($reason, $validReasons)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz şikayet sebebi.']);
    exit;
}

// İçerik türünü kontrol et
if (!in_array($contentType, ['topic', 'comment'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz içerik türü.']);
    exit;
}

// Rate limiting kontrolü
$ip = Security::getIpAddress();
if (!$rateLimiter->checkLimit($ip, 'report')) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Çok fazla şikayet gönderdiniz. Lütfen bir süre bekleyin.']);
    exit;
}

try {
    $db = Database::getInstance();
    $userId = $auth->getEffectiveUserId();

    // İçeriğin var olduğunu kontrol et
    $table = $contentType . 's'; // topics veya comments
    $result = $db->fetchOne(
        "SELECT COUNT(*) as count FROM $table WHERE id = :id AND is_deleted = 0",
        [':id' => $contentId]
    );

    if (!$result || $result['count'] === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'İçerik bulunamadı.']);
        exit;
    }

    // Daha önce şikayet edilmiş mi kontrol et
    $result = $db->fetchOne(
        "SELECT COUNT(*) as count FROM reports 
        WHERE reporter_id = :reporter_id 
        AND content_type = :content_type 
        AND content_id = :content_id",
        [
            ':reporter_id' => $userId,
            ':content_type' => $contentType,
            ':content_id' => $contentId
        ]
    );

    if ($result['count'] > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Bu içeriği daha önce şikayet ettiniz.']);
        exit;
    }

    // Şikayeti kaydet
    $db->insert('reports', [
        'reporter_id' => $userId,
        'content_type' => $contentType,
        'content_id' => $contentId,
        'reason' => $reason,
        'description' => $description,
        'created_at' => date('Y-m-d H:i:s'),
        'status' => 'pending'
    ]);

    // Şikayet sayısını kontrol et
    $result = $db->fetchOne(
        "SELECT COUNT(*) as report_count FROM reports 
        WHERE content_type = :content_type 
        AND content_id = :content_id 
        AND status = 'pending'
        AND created_at > datetime('now', '-24 hours')",
        [
            ':content_type' => $contentType,
            ':content_id' => $contentId
        ]
    );

    // 5'ten fazla şikayet varsa içeriği gizle
    if ($result['report_count'] >= 5) {
        $db->update(
            $table,
            ['is_deleted' => 1],
            'id = :id',
            [':id' => $contentId]
        );
        
        // Aktivite loguna kaydet
        $db->insert('activity_logs', [
            'user_id' => null,
            'action' => 'auto_hide',
            'description' => "Çok sayıda şikayet nedeniyle $contentType #$contentId otomatik gizlendi",
            'ip_address' => $ip,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Şikayetiniz alınmıştır. Moderatörler en kısa sürede inceleyecektir.'
    ]);

} catch (Exception $e) {
    error_log('Şikayet kaydedilirken hata: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.']);
} 