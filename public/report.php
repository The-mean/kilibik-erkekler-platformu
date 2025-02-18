<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/rate_limiter.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$auth = new Auth();
$db = Database::getInstance();
$rateLimiter = new RateLimiter();

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

// Giriş yapmamış kullanıcıları reddet
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Bu işlem için giriş yapmanız gerekiyor.']);
    exit;
}

// JSON verisini al
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['content_type']) || !isset($data['content_id']) || !isset($data['reason'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

$contentType = $data['content_type'];
$contentId = (int)$data['content_id'];
$reason = $data['reason'];
$description = $data['description'] ?? '';

// Şikayet sebebini kontrol et
if (!array_key_exists($reason, $validReasons)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz şikayet sebebi.']);
    exit;
}

// İçerik türünü kontrol et
if (!in_array($contentType, ['topic', 'comment'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz içerik türü.']);
    exit;
}

// Rate limiting kontrolü
$ip = Security::getIpAddress();
if (!$rateLimiter->checkLimit($ip, 'report')) {
    echo json_encode(['success' => false, 'message' => 'Çok fazla şikayet gönderdiniz. Lütfen bir süre bekleyin.']);
    exit;
}

// İçeriğin var olduğunu kontrol et
$table = $contentType . 's'; // topics veya comments
$result = $db->query(
    "SELECT COUNT(*) as count FROM $table WHERE id = :id AND is_deleted = 0",
    [':id' => $contentId]
);
$row = $result->fetchArray(SQLITE3_ASSOC);

if ($row['count'] === 0) {
    echo json_encode(['success' => false, 'message' => 'İçerik bulunamadı.']);
    exit;
}

// Daha önce şikayet edilmiş mi kontrol et
$result = $db->query(
    "SELECT COUNT(*) as count FROM reports 
    WHERE reporter_id = :reporter_id 
    AND content_type = :content_type 
    AND content_id = :content_id",
    [
        ':reporter_id' => $_SESSION['user_id'],
        ':content_type' => $contentType,
        ':content_id' => $contentId
    ]
);
$row = $result->fetchArray(SQLITE3_ASSOC);

if ($row['count'] > 0) {
    echo json_encode(['success' => false, 'message' => 'Bu içeriği daha önce şikayet ettiniz.']);
    exit;
}

try {
    // Şikayeti kaydet
    $db->query(
        "INSERT INTO reports (reporter_id, content_type, content_id, reason, description, created_at) 
        VALUES (:reporter_id, :content_type, :content_id, :reason, :description, CURRENT_TIMESTAMP)",
        [
            ':reporter_id' => $_SESSION['user_id'],
            ':content_type' => $contentType,
            ':content_id' => $contentId,
            ':reason' => $reason,
            ':description' => $description
        ]
    );

    // Şikayet sayısını kontrol et
    $result = $db->query(
        "SELECT COUNT(*) as report_count, 
        (SELECT is_deleted FROM $table WHERE id = :content_id) as is_deleted
        FROM reports 
        WHERE content_type = :content_type 
        AND content_id = :content_id 
        AND status = 'pending'
        AND created_at > datetime('now', '-24 hours')",
        [
            ':content_type' => $contentType,
            ':content_id' => $contentId
        ]
    );
    $row = $result->fetchArray(SQLITE3_ASSOC);

    // 5'ten fazla şikayet varsa ve içerik zaten gizli değilse
    if ($row['report_count'] >= 5 && !$row['is_deleted']) {
        // İçeriği gizle
        $db->query(
            "UPDATE $table SET is_deleted = 1 WHERE id = :id",
            [':id' => $contentId]
        );
        
        // Aktivite loguna kaydet
        $db->query(
            "INSERT INTO activity_logs (user_id, action, description, ip_address) 
            VALUES (NULL, 'auto_hide', :description, :ip)",
            [
                ':description' => "Çok sayıda şikayet nedeniyle $contentType #$contentId otomatik gizlendi",
                ':ip' => $ip
            ]
        );
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Şikayetiniz alınmıştır. Moderatörler en kısa sürede inceleyecektir.'
    ]);
} catch (Exception $e) {
    error_log('Şikayet kaydedilirken hata: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu, lütfen daha sonra tekrar deneyin.']);
} 