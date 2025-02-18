<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/rate_limiter.php';
require_once __DIR__ . '/../../includes/profanity_filter.php';

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
$topicId = (int)($data['topic_id'] ?? 0);
$content = trim($data['content'] ?? '');
$parentId = (int)($data['parent_id'] ?? 0);

// Validasyon
if (empty($content)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Yorum içeriği boş olamaz.']);
    exit;
}

if (strlen($content) < 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Yorum en az 5 karakter olmalıdır.']);
    exit;
}

// Rate limiting kontrolü
$rateLimiter = new RateLimiter();
$ip = Security::getIpAddress();
if (!$rateLimiter->checkLimit($ip, 'comment')) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Çok fazla yorum yaptınız. Lütfen bir süre bekleyin.']);
    exit;
}

try {
    // Küfür filtresi
    $profanityFilter = new ProfanityFilter();
    $content = $profanityFilter->filter($content);

    // Yorumu veritabanına ekle
    $db = Database::getInstance();
    $userId = $auth->getEffectiveUserId();
    
    $commentId = $db->insert('comments', [
        'topic_id' => $topicId,
        'user_id' => $userId,
        'content' => $content,
        'parent_id' => $parentId ?: null,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);

    // Yorum bilgilerini getir
    $comment = $db->fetchOne(
        "SELECT c.*, u.username, u.avatar 
        FROM comments c 
        LEFT JOIN users u ON c.user_id = u.id 
        WHERE c.id = :id",
        [':id' => $commentId]
    );

    // Yanıt için HTML oluştur
    $html = '<div class="comment mb-3" id="comment-' . $commentId . '">
        <div class="d-flex">
            <div class="flex-shrink-0">
                ' . ($comment['avatar'] 
                    ? '<img src="' . htmlspecialchars($comment['avatar']) . '" alt="Avatar" class="rounded-circle" width="40" height="40">'
                    : '<div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white" style="width: 40px; height: 40px;">
                        <i class="bi bi-person-fill"></i>
                       </div>'
                ) . '
            </div>
            <div class="flex-grow-1 ms-3">
                <div class="d-flex align-items-center mb-1">
                    <a href="/profile.php?username=' . htmlspecialchars($comment['username']) . '" class="fw-bold text-decoration-none me-2">
                        ' . htmlspecialchars($comment['username']) . '
                    </a>
                    <small class="text-muted">Şimdi</small>
                </div>
                <div class="comment-content">
                    ' . nl2br(htmlspecialchars($comment['content'])) . '
                </div>
                <div class="comment-actions mt-2">
                    <button class="btn btn-sm btn-link text-muted p-0 me-3" onclick="likeComment(' . $commentId . ')">
                        <i class="bi bi-heart"></i> <span>0</span>
                    </button>
                    <button class="btn btn-sm btn-link text-muted p-0 me-3" onclick="replyToComment(' . $commentId . ')">
                        <i class="bi bi-reply"></i> Yanıtla
                    </button>
                    <button class="btn btn-sm btn-link text-muted p-0" onclick="reportComment(' . $commentId . ')">
                        <i class="bi bi-flag"></i> Şikayet Et
                    </button>
                </div>
            </div>
        </div>
    </div>';

    echo json_encode([
        'success' => true,
        'message' => 'Yorum başarıyla eklendi.',
        'comment' => [
            'id' => $commentId,
            'html' => $html
        ]
    ]);

} catch (Exception $e) {
    error_log('Yorum eklenirken hata: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.']);
} 