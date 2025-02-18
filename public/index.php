<?php
require_once '../includes/init.php';
require_once '../includes/header.php';
require_once '../includes/navbar.php';

// Sayfalama için parametreler
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Konuları getir
$topics_query = "SELECT 
    t.*, 
    u.username, 
    u.avatar,
    COUNT(DISTINCT c.id) as comment_count,
    COUNT(DISTINCT l.id) as like_count
FROM topics t
LEFT JOIN users u ON t.user_id = u.id
LEFT JOIN comments c ON t.id = c.topic_id
LEFT JOIN likes l ON t.id = l.topic_id
WHERE t.is_deleted = 0
GROUP BY t.id
ORDER BY t.created_at DESC
LIMIT :limit OFFSET :offset";

$topics = $db->fetchAll($topics_query, [
    ':limit' => $per_page,
    ':offset' => $offset
]);

// Toplam konu sayısını getir
$total_query = "SELECT COUNT(*) as count FROM topics WHERE is_deleted = 0";
$result = $db->fetchOne($total_query);
$total_topics = $result['count'];
$total_pages = ceil($total_topics / $per_page);
?>

<main class="container py-4">
    <!-- Filtreler ve Sıralama -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex gap-2">
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-filter"></i> Filtrele
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="?filter=popular">En Popüler</a></li>
                    <li><a class="dropdown-item" href="?filter=most_commented">En Çok Yorum Alan</a></li>
                    <li><a class="dropdown-item" href="?filter=most_liked">En Çok Beğenilen</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="?filter=this_week">Bu Hafta</a></li>
                    <li><a class="dropdown-item" href="?filter=this_month">Bu Ay</a></li>
                    <li><a class="dropdown-item" href="?filter=all_time">Tüm Zamanlar</a></li>
                </ul>
            </div>
            
            <?php if (isset($_SESSION['user_id'])): ?>
            <a href="/new-topic.php" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Yeni Konu Aç
            </a>
            <?php endif; ?>
        </div>
        
        <div class="d-none d-md-block">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/">Ana Sayfa</a></li>
                    <li class="breadcrumb-item active">Konular</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <!-- Konular Listesi -->
    <div class="row g-4">
        <?php foreach ($topics as $topic): ?>
        <div class="col-12">
            <div class="card topic-card h-100">
                <div class="card-body">
                    <div class="d-flex">
                        <!-- Kullanıcı Avatarı -->
                        <div class="flex-shrink-0">
                            <a href="/profile.php?username=<?= htmlspecialchars($topic['username']) ?>" class="d-block">
                                <?php if ($topic['avatar']): ?>
                                    <img src="<?= htmlspecialchars($topic['avatar']) ?>" 
                                         class="rounded-circle" 
                                         width="48" 
                                         height="48"
                                         alt="<?= htmlspecialchars($topic['username']) ?>">
                                <?php else: ?>
                                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white" 
                                         style="width: 48px; height: 48px;">
                                        <i class="bi bi-person-fill" style="font-size: 1.5rem;"></i>
                                    </div>
                                <?php endif; ?>
                            </a>
                        </div>
                        
                        <!-- Konu İçeriği -->
                        <div class="flex-grow-1 ms-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <h2 class="h5 card-title mb-1">
                                    <a href="/topic.php?id=<?= $topic['id'] ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($topic['title']) ?>
                                    </a>
                                </h2>
                                <div class="dropdown">
                                    <button class="btn btn-link text-muted p-0" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $topic['user_id']): ?>
                                        <li>
                                            <a class="dropdown-item" href="/edit-topic.php?id=<?= $topic['id'] ?>">
                                                <i class="bi bi-pencil me-2"></i> Düzenle
                                            </a>
                                        </li>
                                        <li>
                                            <button class="dropdown-item text-danger" 
                                                    onclick="deleteTopic(<?= $topic['id'] ?>)">
                                                <i class="bi bi-trash me-2"></i> Sil
                                            </button>
                                        </li>
                                        <?php endif; ?>
                                        <li>
                                            <button class="dropdown-item" 
                                                    onclick="reportTopic(<?= $topic['id'] ?>)">
                                                <i class="bi bi-flag me-2"></i> Şikayet Et
                                            </button>
                                        </li>
                                        <li>
                                            <button class="dropdown-item" 
                                                    onclick="shareTopic(<?= $topic['id'] ?>)">
                                                <i class="bi bi-share me-2"></i> Paylaş
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center text-muted small mb-2">
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
                            </div>
                            
                            <p class="card-text mb-3">
                                <?= nl2br(htmlspecialchars(mb_substr($topic['content'], 0, 300))) ?>
                                <?php if (mb_strlen($topic['content']) > 300): ?>
                                <a href="/topic.php?id=<?= $topic['id'] ?>" class="text-decoration-none">devamını oku...</a>
                                <?php endif; ?>
                            </p>
                            
                            <!-- Etkileşim Butonları -->
                            <div class="d-flex align-items-center gap-3">
                                <button class="btn btn-sm btn-link text-muted p-0" 
                                        onclick="likeTopic(<?= $topic['id'] ?>)">
                                    <i class="bi bi-heart<?= $topic['is_liked'] ? '-fill text-danger' : '' ?>"></i>
                                    <span class="ms-1"><?= $topic['like_count'] ?></span>
                                </button>
                                
                                <a href="/topic.php?id=<?= $topic['id'] ?>#comments" 
                                   class="btn btn-sm btn-link text-muted p-0">
                                    <i class="bi bi-chat"></i>
                                    <span class="ms-1"><?= $topic['comment_count'] ?></span>
                                </a>
                                
                                <button class="btn btn-sm btn-link text-muted p-0" 
                                        onclick="bookmarkTopic(<?= $topic['id'] ?>)">
                                    <i class="bi bi-bookmark<?= $topic['is_bookmarked'] ? '-fill text-primary' : '' ?>"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Sayfalama -->
    <?php if ($total_pages > 1): ?>
    <nav class="mt-4" aria-label="Sayfalama">
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Önceki">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>
            <?php endif; ?>
            
            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            if ($start_page > 1) {
                echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                if ($start_page > 2) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }
            
            for ($i = $start_page; $i <= $end_page; $i++) {
                echo '<li class="page-item' . ($i == $page ? ' active' : '') . '">';
                echo '<a class="page-link" href="?page=' . $i . '">' . $i . '</a>';
                echo '</li>';
            }
            
            if ($end_page < $total_pages) {
                if ($end_page < $total_pages - 1) {
                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
                echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . '">' . $total_pages . '</a></li>';
            }
            ?>
            
            <?php if ($page < $total_pages): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Sonraki">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</main>

<script>
// Timeago.js kütüphanesini kullanarak tarihleri formatla
document.addEventListener('DOMContentLoaded', function() {
    const timeagoElements = document.querySelectorAll('.timeago');
    timeagoElements.forEach(element => {
        const datetime = new Date(element.getAttribute('datetime'));
        element.textContent = timeago.format(datetime, 'tr');
    });
});

// Konu silme işlemi
async function deleteTopic(topicId) {
    if (!confirm('Bu konuyu silmek istediğinizden emin misiniz?')) {
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
            location.reload();
        } else {
            alert(data.message || 'Bir hata oluştu');
        }
    } catch (error) {
        console.error('Hata:', error);
        alert('Bir hata oluştu');
    }
}

// Konu beğenme işlemi
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

// Konu kaydetme işlemi
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

// Konu paylaşma işlemi
function shareTopic(topicId) {
    const url = `${window.location.origin}/topic.php?id=${topicId}`;
    
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

// Konu şikayet etme işlemi
function reportTopic(topicId) {
    const modal = new bootstrap.Modal(document.getElementById('reportModal'));
    document.getElementById('reportTopicId').value = topicId;
    modal.show();
}
</script>

<!-- Şikayet Modalı -->
<div class="modal fade" id="reportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konuyu Şikayet Et</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="reportForm" onsubmit="submitReport(event)">
                    <input type="hidden" id="reportTopicId" name="topic_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Şikayet Nedeni</label>
                        <select class="form-select" name="reason" required>
                            <option value="">Seçiniz...</option>
                            <option value="spam">Spam/Reklam</option>
                            <option value="hakaret">Hakaret/Küfür</option>
                            <option value="yaniltici">Yanıltıcı Bilgi</option>
                            <option value="nefret">Nefret Söylemi</option>
                            <option value="taciz">Taciz/Zorbalık</option>
                            <option value="telif">Telif Hakkı İhlali</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea class="form-control" name="description" rows="3" 
                                  placeholder="Lütfen şikayetinizi detaylandırın..."></textarea>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-danger">Şikayet Et</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Şikayet formunu gönder
async function submitReport(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    try {
        const response = await fetch('/api/topics/report.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('reportModal')).hide();
            alert('Şikayetiniz alınmıştır. En kısa sürede incelenecektir.');
            form.reset();
        } else {
            alert(data.message || 'Bir hata oluştu');
        }
    } catch (error) {
        console.error('Hata:', error);
        alert('Bir hata oluştu');
    }
}
</script>

<?php require_once '../includes/footer.php'; ?> 