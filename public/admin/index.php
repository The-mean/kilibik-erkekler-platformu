<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/auth.php';

$auth = new Auth();

// Admin kontrolü
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: /login.php');
    exit;
}

$db = Database::getInstance();
$logger = Logger::getInstance();

// İstatistikleri al
$stats = [
    'users' => $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE is_banned = 0")['count'],
    'topics' => $db->fetchOne("SELECT COUNT(*) as count FROM topics WHERE is_deleted = 0")['count'],
    'comments' => $db->fetchOne("SELECT COUNT(*) as count FROM comments WHERE is_deleted = 0")['count'],
    'reports' => $db->fetchOne("SELECT COUNT(*) as count FROM reports WHERE status = 'pending'")['count']
];

// Son aktiviteleri al
$activities = $db->fetchAll(
    "SELECT al.*, u.username 
    FROM activity_logs al 
    LEFT JOIN users u ON al.user_id = u.id 
    ORDER BY al.created_at DESC 
    LIMIT 10"
);

// Son hataları al
$errors = $logger->getLastLogs(10);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Paneli - Kılıbık Erkekler Platformu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="/admin">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/users.php">
                                <i class="bi bi-people"></i> Kullanıcılar
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/topics.php">
                                <i class="bi bi-file-text"></i> Konular
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/comments.php">
                                <i class="bi bi-chat"></i> Yorumlar
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/reports.php">
                                <i class="bi bi-flag"></i> Şikayetler
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/logs.php">
                                <i class="bi bi-journal-text"></i> Loglar
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/admin/settings.php">
                                <i class="bi bi-gear"></i> Ayarlar
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Ana içerik -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">Rapor Al</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">Dışa Aktar</button>
                        </div>
                    </div>
                </div>

                <!-- İstatistikler -->
                <div class="row">
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <h5 class="card-title">Kullanıcılar</h5>
                                <p class="card-text display-6"><?= $stats['users'] ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <h5 class="card-title">Konular</h5>
                                <p class="card-text display-6"><?= $stats['topics'] ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <h5 class="card-title">Yorumlar</h5>
                                <p class="card-text display-6"><?= $stats['comments'] ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <h5 class="card-title">Bekleyen Şikayetler</h5>
                                <p class="card-text display-6"><?= $stats['reports'] ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Son aktiviteler -->
                <h2 class="h4 mb-3">Son Aktiviteler</h2>
                <div class="table-responsive mb-4">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Tarih</th>
                                <th>Kullanıcı</th>
                                <th>İşlem</th>
                                <th>Açıklama</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activities as $activity): ?>
                            <tr>
                                <td><?= date('d.m.Y H:i', strtotime($activity['created_at'])) ?></td>
                                <td><?= htmlspecialchars($activity['username'] ?? 'Sistem') ?></td>
                                <td><?= htmlspecialchars($activity['action']) ?></td>
                                <td><?= htmlspecialchars($activity['description']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Son hatalar -->
                <h2 class="h4 mb-3">Son Hatalar</h2>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Tarih</th>
                                <th>Seviye</th>
                                <th>Mesaj</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($errors as $error): ?>
                            <tr>
                                <td><?= substr($error, 1, 19) ?></td>
                                <td><?= substr($error, 22, 5) ?></td>
                                <td><?= htmlspecialchars(substr($error, 28)) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="/assets/js/admin.js"></script>
</body>
</html> 