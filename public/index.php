<?php
require_once '../includes/init.php';
require_once '../includes/header.php';
require_once '../includes/navbar.php';
require_once '../includes/category_manager.php';

$categoryManager = new CategoryManager();

// Kategori filtresi
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$tagSlug = isset($_GET['tag']) ? trim($_GET['tag']) : '';

// Sayfalama için parametreler
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Kategorileri ve popüler etiketleri al
$categories = $categoryManager->getAllCategories();
$popularTags = $categoryManager->getPopularTags(10);

// Konuları getir
if ($categoryId) {
    $topics = $categoryManager->getTopicsByCategory($categoryId, $page, $per_page);
    $total_topics = $categoryManager->getCategoryTopicCount($categoryId);
} elseif ($tagSlug) {
    $topics = $categoryManager->getTopicsByTag($tagSlug, $page, $per_page);
    $total_topics = $categoryManager->getTagTopicCount($tagSlug);
} else {
    $topics_query = "SELECT 
        t.*, 
        u.username, 
        u.avatar,
        c.name as category_name,
        c.slug as category_slug,
        COUNT(DISTINCT cm.id) as comment_count,
        COUNT(DISTINCT l.id) as like_count
    FROM topics t
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN categories c ON t.category_id = c.id
    LEFT JOIN comments cm ON t.id = cm.topic_id
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
}

$total_pages = ceil($total_topics / $per_page);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $categoryId ? htmlspecialchars($categories[$categoryId]['name']) . ' - ' : '' ?>Kılıbık Erkekler Platformu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $assets['css'] ?>">
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <main class="container py-4">
        <div class="row">
            <!-- Ana İçerik -->
            <div class="col-lg-8">
                <?php if ($categoryId && isset($categories[$categoryId])): ?>
                <div class="mb-4">
                    <h1 class="h3"><?= htmlspecialchars($categories[$categoryId]['name']) ?></h1>
                    <?php if ($categories[$categoryId]['description']): ?>
                        <p class="text-muted"><?= htmlspecialchars($categories[$categoryId]['description']) ?></p>
                    <?php endif; ?>
                </div>
                <?php elseif ($tagSlug): ?>
                <div class="mb-4">
                    <h1 class="h3">#<?= htmlspecialchars($tagSlug) ?> Etiketi</h1>
                </div>
                <?php endif; ?>

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
                                            <div>
                                                <h2 class="h5 card-title mb-1">
                                                    <a href="/topic.php?id=<?= $topic['id'] ?>" class="text-decoration-none">
                                                        <?= htmlspecialchars($topic['title']) ?>
                                                    </a>
                                                </h2>
                                                <?php if ($topic['category_name']): ?>
                                                <a href="/?category=<?= $topic['category_id'] ?>" class="badge bg-primary text-decoration-none">
                                                    <?= htmlspecialchars($topic['category_name']) ?>
                                                </a>
                                                <?php endif; ?>
                                            </div>
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
                                                </ul>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex align-items-center gap-3 mt-2">
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
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?><?= $categoryId ? '&category=' . $categoryId : '' ?><?= $tagSlug ? '&tag=' . urlencode($tagSlug) : '' ?>">
                                <?= $i ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>

            <!-- Yan Panel -->
            <div class="col-lg-4">
                <!-- Kategoriler -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h3 class="h5 mb-3">Kategoriler</h3>
                        <div class="list-group list-group-flush">
                            <a href="/" class="list-group-item list-group-item-action <?= !$categoryId ? 'active' : '' ?>">
                                Tüm Konular
                            </a>
                            <?php foreach ($categories as $category): ?>
                            <a href="/?category=<?= $category['id'] ?>" 
                               class="list-group-item list-group-item-action <?= $categoryId == $category['id'] ? 'active' : '' ?>">
                                <?= htmlspecialchars($category['name']) ?>
                                <span class="badge bg-secondary float-end">
                                    <?= $categoryManager->getCategoryTopicCount($category['id']) ?>
                                </span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Popüler Etiketler -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h3 class="h5 mb-3">Popüler Etiketler</h3>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($popularTags as $tag): ?>
                            <a href="/?tag=<?= urlencode($tag['slug']) ?>" 
                               class="badge bg-secondary text-decoration-none <?= $tagSlug == $tag['slug'] ? 'bg-primary' : '' ?>">
                                #<?= htmlspecialchars($tag['name']) ?>
                                <span class="badge bg-light text-dark ms-1"><?= $tag['usage_count'] ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= $assets['js'] ?>"></script>
</body>
</html>

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