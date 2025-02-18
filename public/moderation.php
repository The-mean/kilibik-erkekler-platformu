<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';

$auth = new Auth();

// Sadece adminlerin erişimine izin ver
if (!$auth->isLoggedIn() || !($auth->getCurrentUser()['is_admin'] ?? false)) {
    header('Location: /');
    exit;
}

$db = Database::getInstance();

// Filtreleme parametreleri
$status = $_GET['status'] ?? 'pending';
$type = $_GET['type'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

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

// Şikayetleri getir
$whereConditions = ['1=1'];
$params = [];

if ($status !== 'all') {
    $whereConditions[] = 'r.status = :status';
    $params[':status'] = $status;
}

if ($type !== 'all') {
    $whereConditions[] = 'r.content_type = :type';
    $params[':type'] = $type;
}

$where = implode(' AND ', $whereConditions);

$reports = $db->fetchAll(
    "SELECT r.*, u.username as reporter_name,
    (SELECT COUNT(*) FROM reports WHERE content_type = r.content_type AND content_id = r.content_id) as report_count,
    CASE 
        WHEN r.content_type = 'comment' THEN c.content
        WHEN r.content_type = 'topic' THEN t.title
    END as content_text,
    CASE 
        WHEN r.content_type = 'comment' THEN c.user_id
        WHEN r.content_type = 'topic' THEN t.user_id
    END as content_user_id,
    cu.username as content_username
    FROM reports r
    LEFT JOIN users u ON r.reporter_id = u.id
    LEFT JOIN comments c ON r.content_type = 'comment' AND r.content_id = c.id
    LEFT JOIN topics t ON r.content_type = 'topic' AND r.content_id = t.id
    LEFT JOIN users cu ON cu.id = CASE 
        WHEN r.content_type = 'comment' THEN c.user_id
        WHEN r.content_type = 'topic' THEN t.user_id
    END
    WHERE $where
    ORDER BY r.created_at DESC
    LIMIT :limit OFFSET :offset",
    array_merge($params, [':limit' => $perPage, ':offset' => $offset])
);

// Toplam şikayet sayısını getir
$totalCount = $db->fetchOne(
    "SELECT COUNT(*) as count FROM reports r WHERE $where",
    $params
)['count'];

$totalPages = ceil($totalCount / $perPage);

// Badge renklerini belirle
function getBadgeClass($reason) {
    switch ($reason) {
        case 'hakaret':
            return 'bg-danger';
        case 'spam':
            return 'bg-warning text-dark';
        case 'yaniltici':
            return 'bg-info text-dark';
        case 'nefret':
            return 'bg-dark';
        case 'taciz':
            return 'bg-danger';
        case 'telif':
            return 'bg-secondary';
        default:
            return 'bg-primary';
    }
}

function getCountBadgeClass($count) {
    if ($count >= 5) return 'bg-danger';
    if ($count >= 3) return 'bg-warning text-dark';
    return 'bg-info text-dark';
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderasyon Paneli - Kılıbık Erkekler Platformu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Moderasyon Paneli</h1>
            <div class="btn-group">
                <a href="?status=pending" class="btn btn-outline-primary <?= $status === 'pending' ? 'active' : '' ?>">
                    Bekleyen
                </a>
                <a href="?status=resolved" class="btn btn-outline-primary <?= $status === 'resolved' ? 'active' : '' ?>">
                    Çözülen
                </a>
                <a href="?status=rejected" class="btn btn-outline-primary <?= $status === 'rejected' ? 'active' : '' ?>">
                    Reddedilen
                </a>
                <a href="?status=all" class="btn btn-outline-primary <?= $status === 'all' ? 'active' : '' ?>">
                    Tümü
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tür</th>
                                <th>İçerik</th>
                                <th>Şikayet Eden</th>
                                <th>Sebep</th>
                                <th>Tarih</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $report): ?>
                            <tr>
                                <td>#<?= $report['id'] ?></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?= $report['content_type'] === 'comment' ? 'Yorum' : 'Başlık' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-start">
                                        <div>
                                            <div class="text-truncate" style="max-width: 300px;">
                                                <?= htmlspecialchars($report['content_text']) ?>
                                            </div>
                                            <small class="text-muted">
                                                Yazan: <?= htmlspecialchars($report['content_username']) ?>
                                            </small>
                                            <span class="badge <?= getCountBadgeClass($report['report_count']) ?> ms-2">
                                                <?= $report['report_count'] ?> şikayet
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($report['reporter_name']) ?></td>
                                <td>
                                    <span class="badge <?= getBadgeClass($report['reason']) ?>">
                                        <?= $validReasons[$report['reason']] ?>
                                    </span>
                                    <?php if ($report['description']): ?>
                                        <i class="bi bi-info-circle ms-1" title="<?= htmlspecialchars($report['description']) ?>"></i>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d.m.Y H:i', strtotime($report['created_at'])) ?></td>
                                <td>
                                    <?php if ($report['status'] === 'pending'): ?>
                                        <span class="badge bg-warning text-dark">Bekliyor</span>
                                    <?php elseif ($report['status'] === 'resolved'): ?>
                                        <span class="badge bg-success">Çözüldü</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Reddedildi</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($report['status'] === 'pending'): ?>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-success" 
                                                onclick="handleReport(<?= $report['id'] ?>, 'resolve')">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                            <button type="button" class="btn btn-danger" 
                                                onclick="handleReport(<?= $report['id'] ?>, 'reject')">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                            <button type="button" class="btn btn-warning" 
                                                onclick="hideContent('<?= $report['content_type'] ?>', <?= $report['content_id'] ?>)">
                                                <i class="bi bi-eye-slash"></i>
                                            </button>
                                            <?php if ($report['content_user_id']): ?>
                                            <button type="button" class="btn btn-dark" 
                                                onclick="banUser(<?= $report['content_user_id'] ?>)">
                                                <i class="bi bi-person-x"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (empty($reports)): ?>
                    <div class="text-center py-4">
                        <p class="text-muted mb-0">Şikayet bulunamadı.</p>
                    </div>
                <?php endif; ?>

                <?php if ($totalPages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&status=<?= $status ?>&type=<?= $type ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    // Şikayet durumunu güncelle
    async function handleReport(reportId, action) {
        try {
            const response = await fetch('/api/handle_report.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    report_id: reportId,
                    action: action
                })
            });

            const data = await response.json();
            if (data.success) {
                location.reload();
            } else {
                alert(data.message);
            }
        } catch (error) {
            console.error('Hata:', error);
            alert('Bir hata oluştu. Lütfen daha sonra tekrar deneyin.');
        }
    }

    // İçeriği gizle
    async function hideContent(contentType, contentId) {
        if (!confirm('Bu içeriği gizlemek istediğinize emin misiniz?')) {
            return;
        }

        try {
            const response = await fetch('/api/hide_content.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    content_type: contentType,
                    content_id: contentId
                })
            });

            const data = await response.json();
            if (data.success) {
                location.reload();
            } else {
                alert(data.message);
            }
        } catch (error) {
            console.error('Hata:', error);
            alert('Bir hata oluştu. Lütfen daha sonra tekrar deneyin.');
        }
    }

    // Kullanıcıyı banla
    async function banUser(userId) {
        if (!confirm('Bu kullanıcıyı banlamak istediğinize emin misiniz?')) {
            return;
        }

        try {
            const response = await fetch('/api/ban_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    user_id: userId
                })
            });

            const data = await response.json();
            if (data.success) {
                location.reload();
            } else {
                alert(data.message);
            }
        } catch (error) {
            console.error('Hata:', error);
            alert('Bir hata oluştu. Lütfen daha sonra tekrar deneyin.');
        }
    }
    </script>
</body>
</html> 