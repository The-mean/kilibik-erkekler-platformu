<?php
require_once '../includes/init.php';
require_once '../includes/header.php';
require_once '../includes/navbar.php';

// Başlık ID'sini al
$topicId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

// Başlığı getir
$topic_query = "SELECT 
    t.*, 
    u.username,
    u.avatar,
    COUNT(DISTINCT l.id) as like_count,
    COUNT(DISTINCT b.id) as bookmark_count,
    CASE WHEN ul.id IS NOT NULL THEN 1 ELSE 0 END as is_liked,
    CASE WHEN ub.id IS NOT NULL THEN 1 ELSE 0 END as is_bookmarked
FROM topics t
LEFT JOIN users u ON t.user_id = u.id
LEFT JOIN likes l ON t.id = l.topic_id
LEFT JOIN bookmarks b ON t.id = b.topic_id
LEFT JOIN likes ul ON t.id = ul.topic_id AND ul.user_id = :user_id
LEFT JOIN bookmarks ub ON t.id = ub.topic_id AND ub.user_id = :user_id
WHERE t.id = :topic_id AND t.is_deleted = 0
GROUP BY t.id";

$stmt = $db->prepare($topic_query);
$stmt->execute([
    ':topic_id' => $topicId,
    ':user_id' => $auth->getEffectiveUserId()
]);
$topic = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$topic) {
    header('Location: /404.php');
    exit;
}

// Görüntülenme sayısını artır
$db->query(
    "UPDATE topics SET view_count = view_count + 1 WHERE id = :id",
    [':id' => $topicId]
);

// Yorumları getir
$comments_query = "SELECT 
    c.*,
    u.username,
    u.avatar,
    COUNT(DISTINCT CASE WHEN cr.type = 'like' THEN cr.id END) as like_count,
    COUNT(DISTINCT CASE WHEN cr.type = 'dislike' THEN cr.id END) as dislike_count,
    MAX(CASE WHEN cr.user_id = :user_id THEN cr.type END) as user_reaction
FROM comments c
LEFT JOIN users u ON c.user_id = u.id
LEFT JOIN comment_reactions cr ON c.id = cr.comment_id
WHERE c.topic_id = :topic_id AND c.is_deleted = 0
GROUP BY c.id
ORDER BY c.created_at ASC";

$stmt = $db->prepare($comments_query);
$stmt->execute([
    ':topic_id' => $topicId,
    ':user_id' => $auth->getEffectiveUserId()
]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="container py-4">
    <div class="row">
        <!-- Ana İçerik -->
        <div class="col-lg-8">
            <!-- Başlık Kartı -->
            <div class="card topic-card mb-4">
                <div class="card-body">
                    <div class="d-flex">
                        <!-- Kullanıcı Avatarı -->
                        <div class="flex-shrink-0">
                            <a href="/profile.php?username=<?= htmlspecialchars($topic['username']) ?>" class="d-block">
                                <img src="<?= htmlspecialchars($topic['avatar'] ?: '/assets/images/default-avatar.png') ?>" 
                                     class="rounded-circle" 
                                     width="48" 
                                     height="48"
                                     alt="<?= htmlspecialchars($topic['username']) ?>">
                            </a>
                        </div>
                        
                        <!-- Başlık İçeriği -->
                        <div class="flex-grow-1 ms-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <h1 class="h4 mb-1"><?= htmlspecialchars($topic['title']) ?></h1>
                                <div class="dropdown">
                                    <button class="btn btn-link text-muted p-0" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $topic['user_id']): ?>
                                        <li>
                                            <a class="dropdown-item" href="/edit-topic.php?id=<?= $topic['id'] ?>">
                                                <i class="bi bi-pencil me-2"></i>Düzenle
                                            </a>
                                        </li>
                                        <li>
                                            <button class="dropdown-item text-danger" onclick="deleteTopic(<?= $topic['id'] ?>)">
                                                <i class="bi bi-trash me-2"></i>Sil
                                            </button>
                                        </li>
                                        <?php endif; ?>
                                        <li>
                                            <button class="dropdown-item" onclick="reportTopic(<?= $topic['id'] ?>)">
                                                <i class="bi bi-flag me-2"></i>Şikayet Et
                                            </button>
                                        </li>
                                        <li>
                                            <button class="dropdown-item" onclick="shareTopic(<?= $topic['id'] ?>)">
                                                <i class="bi bi-share me-2"></i>Paylaş
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center text-muted small mb-3">
                                <a href="/profile.php?username=<?= htmlspecialchars($topic['username']) ?>" 
                                   class="text-decoration-none me-2">
                                    @<?= htmlspecialchars($topic['username']) ?>
                                </a>
                                <span class="me-2">•</span>
                                <time datetime="<?= $topic['created_at'] ?>" class="timeago me-2">
                                    <?= htmlspecialchars($topic['created_at']) ?>
                                </time>
                                <?php if ($topic['updated_at'] != $topic['created_at']): ?>
                                <span class="me-2">•</span>
                                <span class="text-muted" title="Son düzenleme: <?= htmlspecialchars($topic['updated_at']) ?>">
                                    Düzenlendi
                                </span>
                                <?php endif; ?>
                                <span class="me-2">•</span>
                                <span title="Görüntülenme">
                                    <i class="bi bi-eye me-1"></i><?= number_format($topic['view_count']) ?>
                                </span>
                            </div>
                            
                            <div class="topic-content mb-3">
                                <?= nl2br(htmlspecialchars($topic['content'])) ?>
                            </div>
                            
                            <!-- Etkileşim Butonları -->
                            <div class="d-flex align-items-center gap-3">
                                <button class="btn btn-sm <?= $topic['is_liked'] ? 'btn-danger' : 'btn-outline-danger' ?>" 
                                        onclick="likeTopic(<?= $topic['id'] ?>)">
                                    <i class="bi bi-heart<?= $topic['is_liked'] ? '-fill' : '' ?>"></i>
                                    <span class="ms-1"><?= $topic['like_count'] ?></span>
                                </button>
                                
                                <button class="btn btn-sm <?= $topic['is_bookmarked'] ? 'btn-primary' : 'btn-outline-primary' ?>" 
                                        onclick="bookmarkTopic(<?= $topic['id'] ?>)">
                                    <i class="bi bi-bookmark<?= $topic['is_bookmarked'] ? '-fill' : '' ?>"></i>
                                    <span class="ms-1"><?= $topic['bookmark_count'] ?></span>
                                </button>
                                
                                <button class="btn btn-sm btn-outline-secondary" onclick="shareTopic(<?= $topic['id'] ?>)">
                                    <i class="bi bi-share"></i>
                                    <span class="ms-1">Paylaş</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Yorumlar -->
            <div class="card mb-4">
                <div class="card-header bg-transparent">
                    <h2 class="h5 mb-0">
                        Yorumlar
                        <span class="text-muted">(<?= count($comments) ?>)</span>
                    </h2>
                </div>
                
                <!-- Yorum Formu -->
                <?php if ($auth->isLoggedIn()): ?>
                <div class="card-body">
                    <h5 class="card-title mb-3">Yorum Yap</h5>
                    <form id="commentForm" onsubmit="submitComment(event)">
                        <input type="hidden" name="topic_id" value="<?= $topic['id'] ?>">
                        <input type="hidden" name="parent_id" id="parentCommentId" value="">
                        
                        <div class="mb-3">
                            <textarea class="form-control" name="content" id="commentContent" 
                                      rows="3" required minlength="5" 
                                      placeholder="Yorumunuzu yazın..."></textarea>
                            <div class="invalid-feedback">
                                Yorum en az 5 karakter olmalıdır.
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div id="replyingTo" class="text-muted" style="display: none;">
                                <small>
                                    <i class="bi bi-reply"></i> 
                                    <span></span>
                                    <button type="button" class="btn btn-link btn-sm p-0 ms-2" onclick="cancelReply()">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </small>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send"></i> Gönder
                            </button>
                        </div>
                    </form>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    Yorum yapabilmek için <a href="/login.php">giriş yapın</a> veya 
                    <a href="/register.php">kayıt olun</a>.
                </div>
                <?php endif; ?>
                
                <!-- Yorum Listesi -->
                <div id="comments">
                    <?php foreach ($comments as $comment): ?>
                    <div class="comment mb-3" id="comment-<?= $comment['id'] ?>">
                        <div class="d-flex">
                            <!-- Kullanıcı Avatarı -->
                            <div class="flex-shrink-0">
                                <a href="/profile.php?username=<?= htmlspecialchars($comment['username']) ?>">
                                    <img src="<?= htmlspecialchars($comment['avatar'] ?: '/assets/images/default-avatar.png') ?>" 
                                         class="rounded-circle" 
                                         width="40" 
                                         height="40"
                                         alt="<?= htmlspecialchars($comment['username']) ?>">
                                </a>
                            </div>
                            
                            <!-- Yorum İçeriği -->
                            <div class="flex-grow-1 ms-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div>
                                        <a href="/profile.php?username=<?= htmlspecialchars($comment['username']) ?>" 
                                           class="fw-bold text-decoration-none">
                                            <?= htmlspecialchars($comment['username']) ?>
                                        </a>
                                        <small class="text-muted ms-2">
                                            <?= timeAgo($comment['created_at']) ?>
                                        </small>
                                    </div>
                                    
                                    <div class="dropdown">
                                        <button class="btn btn-link text-muted p-0" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <?php if ($auth->isLoggedIn() && $auth->getEffectiveUserId() == $comment['user_id']): ?>
                                            <li>
                                                <button class="dropdown-item" onclick="editComment(<?= $comment['id'] ?>)">
                                                    <i class="bi bi-pencil me-2"></i>Düzenle
                                                </button>
                                            </li>
                                            <li>
                                                <button class="dropdown-item text-danger" 
                                                        onclick="deleteComment(<?= $comment['id'] ?>)">
                                                    <i class="bi bi-trash me-2"></i>Sil
                                                </button>
                                            </li>
                                            <?php endif; ?>
                                            <?php if ($auth->isLoggedIn()): ?>
                                            <li>
                                                <button class="dropdown-item" onclick="reportComment(<?= $comment['id'] ?>)">
                                                    <i class="bi bi-flag me-2"></i>Şikayet Et
                                                </button>
                                            </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="comment-content mb-2">
                                    <?= nl2br(htmlspecialchars($comment['content'])) ?>
                                </div>
                                
                                <div class="d-flex align-items-center gap-3">
                                    <?php if ($auth->isLoggedIn()): ?>
                                    <div class="btn-group">
                                        <button class="btn btn-sm <?= $comment['user_reaction'] === 'like' ? 'btn-primary' : 'btn-outline-primary' ?>"
                                                onclick="reactToComment(<?= $comment['id'] ?>, 'like')"
                                                data-type="like">
                                            <i class="bi bi-hand-thumbs-up<?= $comment['user_reaction'] === 'like' ? '-fill' : '' ?>"></i>
                                            <span class="like-count"><?= $comment['like_count'] ?></span>
                                        </button>
                                        <button class="btn btn-sm <?= $comment['user_reaction'] === 'dislike' ? 'btn-danger' : 'btn-outline-danger' ?>"
                                                onclick="reactToComment(<?= $comment['id'] ?>, 'dislike')"
                                                data-type="dislike">
                                            <i class="bi bi-hand-thumbs-down<?= $comment['user_reaction'] === 'dislike' ? '-fill' : '' ?>"></i>
                                            <span class="dislike-count"><?= $comment['dislike_count'] ?></span>
                                        </button>
                                    </div>
                                    <?php else: ?>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary" disabled>
                                            <i class="bi bi-hand-thumbs-up"></i>
                                            <span><?= $comment['like_count'] ?></span>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" disabled>
                                            <i class="bi bi-hand-thumbs-down"></i>
                                            <span><?= $comment['dislike_count'] ?></span>
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <button class="btn btn-sm text-muted" 
                                            onclick="replyToComment(<?= $comment['id'] ?>)">
                                        <i class="bi bi-reply"></i>
                                        <span class="ms-1">Yanıtla</span>
                                    </button>
                                    
                                    <?php if ($auth->isLoggedIn()): ?>
                                    <button class="btn btn-sm text-muted" 
                                            onclick="reportComment(<?= $comment['id'] ?>)">
                                        <i class="bi bi-flag"></i>
                                        <span class="ms-1">Şikayet Et</span>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($comments)): ?>
                    <div class="card-body text-center text-muted">
                        <p class="mb-0">Henüz yorum yapılmamış. İlk yorumu siz yapın!</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Yazar Kartı -->
            <div class="card mb-4">
                <div class="card-body">
                    <h3 class="h6 mb-3">Yazar Hakkında</h3>
                    <div class="d-flex align-items-center mb-3">
                        <img src="<?= htmlspecialchars($topic['avatar'] ?: '/assets/images/default-avatar.png') ?>" 
                             class="rounded-circle me-3" 
                             width="64" 
                             height="64"
                             alt="<?= htmlspecialchars($topic['username']) ?>">
                        <div>
                            <h4 class="h6 mb-1">
                                <a href="/profile.php?username=<?= htmlspecialchars($topic['username']) ?>" 
                                   class="text-decoration-none">
                                    <?= htmlspecialchars($topic['username']) ?>
                                </a>
                            </h4>
                            <p class="text-muted small mb-0">
                                <?= date('F Y', strtotime($topic['created_at'])) ?>'den beri üye
                            </p>
                        </div>
                    </div>
                    <div class="d-grid">
                        <a href="/profile.php?username=<?= htmlspecialchars($topic['username']) ?>" 
                           class="btn btn-outline-primary">
                            Profili Görüntüle
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Benzer Başlıklar -->
            <?php
            $similar_query = "SELECT t.*, u.username, COUNT(c.id) as comment_count
                FROM topics t
                LEFT JOIN users u ON t.user_id = u.id
                LEFT JOIN comments c ON t.id = c.topic_id
                WHERE t.id != :topic_id 
                AND t.is_deleted = 0
                GROUP BY t.id
                ORDER BY t.created_at DESC
                LIMIT 5";
            
            $stmt = $db->prepare($similar_query);
            $stmt->execute([':topic_id' => $topicId]);
            $similar_topics = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($similar_topics)):
            ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h3 class="h6 mb-3">Benzer Başlıklar</h3>
                    <div class="list-group list-group-flush">
                        <?php foreach ($similar_topics as $similar): ?>
                        <a href="/topic.php?id=<?= $similar['id'] ?>" 
                           class="list-group-item list-group-item-action">
                            <h4 class="h6 mb-1"><?= htmlspecialchars($similar['title']) ?></h4>
                            <div class="d-flex justify-content-between align-items-center small text-muted">
                                <span>@<?= htmlspecialchars($similar['username']) ?></span>
                                <span>
                                    <i class="bi bi-chat-dots me-1"></i>
                                    <?= $similar['comment_count'] ?>
                                </span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Reklam Alanı -->
            <?= $adManager->getAdCode('sidebar') ?>
        </div>
    </div>
</main>

<!-- Şikayet Modalı -->
<div class="modal fade" id="reportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Şikayet Et</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="reportForm" onsubmit="submitReport(event)">
                    <input type="hidden" id="reportContentType" name="content_type">
                    <input type="hidden" id="reportContentId" name="content_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Şikayet Sebebi</label>
                        <select class="form-select" name="reason" required>
                            <option value="">Seçiniz...</option>
                            <option value="hakaret">Hakaret/Küfür</option>
                            <option value="spam">Spam/Reklam</option>
                            <option value="yaniltici">Yanıltıcı Bilgi</option>
                            <option value="nefret">Nefret Söylemi</option>
                            <option value="taciz">Taciz/Zorbalık</option>
                            <option value="telif">Telif Hakkı İhlali</option>
                            <option value="diger">Diğer</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Açıklama (İsteğe bağlı)</label>
                        <textarea class="form-control" name="description" rows="3" 
                                  placeholder="Şikayetinizle ilgili detay ekleyin..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="submit" form="reportForm" class="btn btn-primary">Gönder</button>
            </div>
        </div>
    </div>
</div>

<script>
// Timeago.js kütüphanesini kullanarak tarihleri formatla
document.addEventListener('DOMContentLoaded', function() {
    const timeagoElements = document.querySelectorAll('.timeago');
    timeagoElements.forEach(element => {
        const datetime = new Date(element.getAttribute('datetime'));
        element.textContent = timeago.format(datetime, 'tr');
    });
});

// Başlık silme işlemi
async function deleteTopic(topicId) {
    if (!confirm('Bu başlığı silmek istediğinizden emin misiniz?')) {
        return;
    }
    
    try {
        const response = await fetch('/api/topics/delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ topic_id: topicId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.location.href = '/';
        } else {
            alert(data.message || 'Bir hata oluştu');
        }
    } catch (error) {
        console.error('Hata:', error);
        alert('Bir hata oluştu');
    }
}

// Başlık beğenme işlemi
async function likeTopic(topicId) {
    try {
        const response = await fetch('/api/topics/like.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ topic_id: topicId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Bir hata oluştu');
        }
    } catch (error) {
        console.error('Hata:', error);
        alert('Bir hata oluştu');
    }
}

// Başlık kaydetme işlemi
async function bookmarkTopic(topicId) {
    try {
        const response = await fetch('/api/topics/bookmark.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ topic_id: topicId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Bir hata oluştu');
        }
    } catch (error) {
        console.error('Hata:', error);
        alert('Bir hata oluştu');
    }
}

// Başlık paylaşma işlemi
function shareTopic(topicId) {
    const url = window.location.href;
    
    if (navigator.share) {
        navigator.share({
            title: document.title,
            url: url
        }).catch(console.error);
    } else {
        navigator.clipboard.writeText(url).then(() => {
            alert('Bağlantı panoya kopyalandı!');
        }).catch(console.error);
    }
}

// Yorum gönderme
async function submitComment(event) {
    event.preventDefault();
    
    const form = event.target;
    const submitButton = form.querySelector('button[type="submit"]');
    const textarea = form.querySelector('textarea');
    
    // Butonu devre dışı bırak
    submitButton.disabled = true;
    
    try {
        const response = await fetch('/api/submit_comment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                topic_id: form.querySelector('[name="topic_id"]').value,
                parent_id: form.querySelector('[name="parent_id"]').value,
                content: textarea.value
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Yeni yorumu listeye ekle
            if (data.comment.parent_id) {
                // Yanıt ise, ilgili yorumun altına ekle
                const parentComment = document.querySelector(`#comment-${data.comment.parent_id}`);
                const replies = parentComment.querySelector('.comment-replies') || 
                              parentComment.appendChild(document.createElement('div'));
                replies.classList.add('comment-replies', 'ms-4', 'mt-3');
                replies.insertAdjacentHTML('beforeend', data.comment.html);
            } else {
                // Ana yorum ise, listenin başına ekle
                document.querySelector('#comments').insertAdjacentHTML('afterbegin', data.comment.html);
            }
            
            // Formu temizle
            form.reset();
            cancelReply();
            
            // Başarı mesajı göster
            showAlert('success', 'Yorumunuz başarıyla eklendi.');
            
        } else {
            showAlert('danger', data.message);
        }
    } catch (error) {
        console.error('Hata:', error);
        showAlert('danger', 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.');
    } finally {
        submitButton.disabled = false;
    }
}

// Yanıtlama işlevi
function replyToComment(commentId) {
    const comment = document.querySelector(`#comment-${commentId}`);
    const username = comment.querySelector('.fw-bold').textContent.trim();
    
    // Parent ID'yi ayarla
    document.querySelector('#parentCommentId').value = commentId;
    
    // Yanıtlama bilgisini göster
    const replyingTo = document.querySelector('#replyingTo');
    replyingTo.style.display = 'block';
    replyingTo.querySelector('span').textContent = `${username} kullanıcısına yanıt yazıyorsunuz`;
    
    // Textarea'ya odaklan
    document.querySelector('#commentContent').focus();
}

// Yanıtlamayı iptal et
function cancelReply() {
    document.querySelector('#parentCommentId').value = '';
    document.querySelector('#replyingTo').style.display = 'none';
}

// Bildirim gösterme
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.querySelector('.container').insertAdjacentElement('afterbegin', alertDiv);
    
    // 5 saniye sonra otomatik kapat
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Yorum beğenme işlemi
async function reactToComment(commentId, type) {
    try {
        const response = await fetch('/api/like_comment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ comment_id: commentId, type: type })
        });
        
        const data = await response.json();
        
        if (data.success) {
            const comment = document.querySelector(`#comment-${commentId}`);
            const likeBtn = comment.querySelector(`[data-type="like"]`);
            const dislikeBtn = comment.querySelector(`[data-type="dislike"]`);
            const likeCount = comment.querySelector('.like-count');
            const dislikeCount = comment.querySelector('.dislike-count');
            
            // Sayıları güncelle
            likeCount.textContent = data.stats.likes;
            dislikeCount.textContent = data.stats.dislikes;
            
            // Buton stillerini güncelle
            likeBtn.className = `btn btn-sm ${data.userReaction === 'like' ? 'btn-primary' : 'btn-outline-primary'}`;
            dislikeBtn.className = `btn btn-sm ${data.userReaction === 'dislike' ? 'btn-danger' : 'btn-outline-danger'}`;
            
            // İkon stillerini güncelle
            likeBtn.querySelector('i').className = `bi bi-hand-thumbs-up${data.userReaction === 'like' ? '-fill' : ''}`;
            dislikeBtn.querySelector('i').className = `bi bi-hand-thumbs-down${data.userReaction === 'dislike' ? '-fill' : ''}`;
        } else {
            showAlert('danger', data.message);
        }
    } catch (error) {
        console.error('Hata:', error);
        showAlert('danger', 'Bir hata oluştu');
    }
}

// Yorum silme işlemi
async function deleteComment(commentId) {
    if (!confirm('Bu yorumu silmek istediğinizden emin misiniz?')) {
        return;
    }
    
    try {
        const response = await fetch('/api/comments/delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ comment_id: commentId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Bir hata oluştu');
        }
    } catch (error) {
        console.error('Hata:', error);
        alert('Bir hata oluştu');
    }
}

// Şikayet modalını aç
function reportTopic(topicId) {
    document.getElementById('reportContentType').value = 'topic';
    document.getElementById('reportContentId').value = topicId;
    new bootstrap.Modal(document.getElementById('reportModal')).show();
}

// Şikayet formunu gönder
async function submitReport(event) {
    event.preventDefault();
    
    const form = event.target;
    const submitButton = form.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    
    try {
        const formData = new FormData(form);
        const data = {
            content_type: formData.get('content_type'),
            content_id: formData.get('content_id'),
            reason: formData.get('reason'),
            description: formData.get('description')
        };
        
        const response = await fetch('/api/report.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Modalı kapat
            bootstrap.Modal.getInstance(document.getElementById('reportModal')).hide();
            
            // Formu temizle
            form.reset();
            
            // Başarı mesajı göster
            showAlert('success', result.message);
        } else {
            showAlert('danger', result.message);
        }
    } catch (error) {
        console.error('Hata:', error);
        showAlert('danger', 'Bir hata oluştu. Lütfen daha sonra tekrar deneyin.');
    } finally {
        submitButton.disabled = false;
    }
}

// Şikayet modalını aç
function reportComment(commentId) {
    document.getElementById('reportContentType').value = 'comment';
    document.getElementById('reportContentId').value = commentId;
    new bootstrap.Modal(document.getElementById('reportModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?> 