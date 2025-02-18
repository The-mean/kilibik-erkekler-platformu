<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/rate_limiter.php';
require_once __DIR__ . '/../../includes/category_manager.php';

header('Content-Type: application/json');

$auth = new Auth();
$rateLimiter = new RateLimiter();
$categoryManager = new CategoryManager();

// Giriş kontrolü
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bu işlem için giriş yapmanız gerekiyor.']);
    exit;
}

// Rate limiting kontrolü
$ip = Security::getIpAddress();
if (!$rateLimiter->checkLimit($ip, 'topic')) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Çok fazla konu açtınız. Lütfen biraz bekleyin.']);
    exit;
}

// POST verilerini al
$data = json_decode(file_get_contents('php://input'), true);
$title = isset($data['title']) ? trim($data['title']) : '';
$content = isset($data['content']) ? trim($data['content']) : '';
$categoryId = isset($data['category_id']) ? (int)$data['category_id'] : 0;
$tags = isset($data['tags']) ? array_filter(array_map('trim', $data['tags'])) : [];

// Validasyon
if (empty($title) || strlen($title) < 5 || strlen($title) > 255) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Başlık 5-255 karakter arasında olmalıdır.']);
    exit;
}

if (empty($content) || strlen($content) < 20) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'İçerik en az 20 karakter olmalıdır.']);
    exit;
}

if (!$categoryId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Kategori seçmelisiniz.']);
    exit;
}

if (empty($tags)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'En az bir etiket eklemelisiniz.']);
    exit;
}

if (count($tags) > 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'En fazla 5 etiket ekleyebilirsiniz.']);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Kategori var mı kontrol et
    $category = $categoryManager->getCategory($categoryId);
    if (!$category) {
        throw new Exception('Geçersiz kategori.');
    }
    
    // Konuyu veritabanına ekle
    $db->beginTransaction();
    
    $topicId = $db->insert('topics', [
        'user_id' => $auth->getUserId(),
        'category_id' => $categoryId,
        'title' => Security::escape($title),
        'content' => Security::escape($content),
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
    // Etiketleri ekle
    $categoryManager->addTagsToTopic($topicId, $tags);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Konu başarıyla oluşturuldu.',
        'topic_id' => $topicId
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    error_log('Konu oluşturulurken hata: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.']);
} 