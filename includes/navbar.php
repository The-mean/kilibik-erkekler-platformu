<?php
if (!isset($auth)) {
    require_once __DIR__ . '/auth.php';
    $auth = new Auth();
}
$currentUser = $auth->getCurrentUser();
?>
<nav class="navbar navbar-expand-lg sticky-top bg-white border-bottom">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="/">
            <i class="bi bi-heart-fill text-danger me-2"></i>
            <span>Kılıbık Erkekler</span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <i class="bi bi-list"></i>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <!-- Arama Formu -->
            <form class="d-flex mx-auto my-2 my-lg-0 nav-search" action="/search.php" method="GET">
                <div class="input-group">
                    <input type="search" name="q" class="form-control" placeholder="Başlık veya içerik ara..." 
                           aria-label="Arama" required minlength="3">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>

            <!-- Sağ Menü -->
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <?php if ($auth->isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/submit.php">
                            <i class="bi bi-plus-lg"></i>
                            <span class="d-lg-inline d-none">Yeni Başlık</span>
                        </a>
                    </li>
                    
                    <!-- Bildirimler Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link" href="#" id="notificationsDropdown" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-bell position-relative">
                                <?php if (isset($_SESSION['unread_notifications']) && $_SESSION['unread_notifications'] > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?= $_SESSION['unread_notifications'] ?>
                                    </span>
                                <?php endif; ?>
                            </i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
                            <div class="dropdown-header d-flex justify-content-between align-items-center">
                                <span>Bildirimler</span>
                                <a href="/notifications.php" class="text-decoration-none">Tümünü Gör</a>
                            </div>
                            <div class="dropdown-divider"></div>
                            <!-- Bildirimler AJAX ile yüklenecek -->
                            <div class="notifications-container">
                                <div class="text-center p-3">
                                    <div class="loading"></div>
                                </div>
                            </div>
                        </div>
                    </li>

                    <!-- Kullanıcı Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <?php if ($currentUser['avatar']): ?>
                                <img src="<?= htmlspecialchars($currentUser['avatar']) ?>" 
                                     alt="Profil" class="rounded-circle" width="24" height="24">
                            <?php else: ?>
                                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white" 
                                     style="width: 24px; height: 24px; display: inline-block;">
                                    <i class="bi bi-person-fill" style="font-size: 0.8rem;"></i>
                                </div>
                            <?php endif; ?>
                            <span class="d-lg-inline d-none ms-2"><?= htmlspecialchars($auth->getDisplayName()) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <a class="dropdown-item" href="/profile.php">
                                    <i class="bi bi-person me-2"></i>Profilim
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="/settings.php">
                                    <i class="bi bi-gear me-2"></i>Ayarlar
                                </a>
                            </li>
                            <?php if ($currentUser['is_admin'] ?? false): ?>
                                <li>
                                    <a class="dropdown-item" href="/admin/">
                                        <i class="bi bi-shield-check me-2"></i>Yönetim Paneli
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="/logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Çıkış Yap
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/login.php">
                            <i class="bi bi-box-arrow-in-right"></i>
                            <span class="d-lg-inline d-none">Giriş Yap</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary text-white ms-lg-2 px-3" href="/register.php">
                            <i class="bi bi-person-plus"></i>
                            <span class="d-lg-inline d-none">Kayıt Ol</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Mobil Arama Butonu -->
<button class="btn btn-primary rounded-circle mobile-search-btn d-lg-none" type="button" 
        data-bs-toggle="modal" data-bs-target="#searchModal">
    <i class="bi bi-search"></i>
</button>

<!-- Mobil Arama Modal -->
<div class="modal fade" id="searchModal" tabindex="-1">
    <div class="modal-dialog modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ara</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="/search.php" method="GET">
                    <div class="input-group">
                        <input type="search" name="q" class="form-control" 
                               placeholder="Başlık veya içerik ara..." required minlength="3">
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Bildirimler için AJAX
document.addEventListener('DOMContentLoaded', function() {
    const notificationsDropdown = document.getElementById('notificationsDropdown');
    const notificationsContainer = document.querySelector('.notifications-container');
    let notificationsLoaded = false;

    notificationsDropdown?.addEventListener('show.bs.dropdown', function() {
        if (!notificationsLoaded) {
            fetch('/api/notifications.php')
                .then(response => response.json())
                .then(data => {
                    if (data.notifications.length > 0) {
                        notificationsContainer.innerHTML = data.notifications
                            .map(notification => `
                                <a href="${notification.url}" class="dropdown-item notification-item ${notification.read ? '' : 'unread'}">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <i class="bi ${notification.icon}"></i>
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <p class="mb-0">${notification.message}</p>
                                            <small class="text-muted">${notification.time_ago}</small>
                                        </div>
                                    </div>
                                </a>
                            `).join('');
                    } else {
                        notificationsContainer.innerHTML = '<div class="dropdown-item text-center">Bildirim yok</div>';
                    }
                    notificationsLoaded = true;
                })
                .catch(error => {
                    console.error('Bildirimler yüklenirken hata:', error);
                    notificationsContainer.innerHTML = '<div class="dropdown-item text-center text-danger">Bildirimler yüklenemedi</div>';
                });
        }
    });
});

function setTheme(theme) {
    // Temayı localStorage'a kaydet
    localStorage.setItem('theme', theme);
    
    // Temayı uygula
    document.documentElement.setAttribute('data-theme', theme);
}
</script> 