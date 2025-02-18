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

// POST verilerini al
$data = json_decode(file_get_contents('php://input'), true);
$commentId = (int)($data['comment_id'] ?? 0);
$type = $data['type'] ?? ''; // 'like' veya 'dislike'

// Validasyon
if (!$commentId || !in_array($type, ['like', 'dislike'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

try {
    $db = Database::getInstance();
    $userId = $auth->getEffectiveUserId();

    // Yorumun var olduğunu kontrol et
    $comment = $db->fetchOne(
        "SELECT id FROM comments WHERE id = :id AND is_deleted = 0",
        [':id' => $commentId]
    );

    if (!$comment) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Yorum bulunamadı.']);
        exit;
    }

    // Kullanıcının önceki beğeni/dislike durumunu kontrol et
    $existingReaction = $db->fetchOne(
        "SELECT type FROM comment_reactions 
        WHERE user_id = :user_id AND comment_id = :comment_id",
        [
            ':user_id' => $userId,
            ':comment_id' => $commentId
        ]
    );

    if ($existingReaction) {
        if ($existingReaction['type'] === $type) {
            // Aynı tip tekrar tıklanmışsa, reaksiyonu kaldır
            $db->delete(
                'comment_reactions',
                'user_id = :user_id AND comment_id = :comment_id',
                [
                    ':user_id' => $userId,
                    ':comment_id' => $commentId
                ]
            );
        } else {
            // Farklı tip tıklanmışsa, tipi güncelle
            $db->update(
                'comment_reactions',
                ['type' => $type],
                'user_id = :user_id AND comment_id = :comment_id',
                [
                    ':user_id' => $userId,
                    ':comment_id' => $commentId
                ]
            );
        }
    } else {
        // Yeni reaksiyon ekle
        $db->insert('comment_reactions', [
            'user_id' => $userId,
            'comment_id' => $commentId,
            'type' => $type,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    // Güncel beğeni/dislike sayılarını getir
    $stats = $db->fetchOne(
        "SELECT 
            (SELECT COUNT(*) FROM comment_reactions WHERE comment_id = :id AND type = 'like') as likes,
            (SELECT COUNT(*) FROM comment_reactions WHERE comment_id = :id AND type = 'dislike') as dislikes",
        [':id' => $commentId]
    );

    echo json_encode([
        'success' => true,
        'message' => 'İşlem başarılı.',
        'stats' => [
            'likes' => (int)$stats['likes'],
            'dislikes' => (int)$stats['dislikes']
        ],
        'userReaction' => $existingReaction ? null : $type // Reaksiyon kaldırıldıysa null, yeni eklendiyse tipi
    ]);

} catch (Exception $e) {
    error_log('Yorum reaksiyonu eklenirken hata: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.']);
} 