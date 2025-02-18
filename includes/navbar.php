<?php
if (!isset($auth)) {
    require_once __DIR__ . '/auth.php';
    $auth = new Auth();
}
$currentUser = $auth->getCurrentUser();
?>
<nav class="navbar navbar-expand-lg sticky-top bg-white border-bottom">
    <div class="container">
        <!-- Logo ve Marka -->
        <a class="navbar-brand d-flex align-items-center" href="/">
            <i class="bi bi-heart-fill text-danger me-2"></i>
            <span>Kılıbık Erkekler</span>
        </a>
        
        <!-- Mobil Menü Butonu -->
        <button class="navbar-toggler border-0 p-0" type="button" 
                data-bs-toggle="offcanvas" data-bs-target="#navbarOffcanvas">
            <i class="bi bi-list fs-4"></i>
        </button>

        <!-- Mobil Offcanvas Menü -->
        <div class="offcanvas offcanvas-end" tabindex="-1" id="navbarOffcanvas">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title d-flex align-items-center">
                    <i class="bi bi-heart-fill text-danger me-2"></i>
                    <span>Kılıbık Erkekler</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
            </div>
            <div class="offcanvas-body">
                <!-- Arama Formu -->
                <form class="d-flex mb-3 mt-2 nav-search" action="/search.php" method="GET">
                    <div class="input-group">
                        <input type="search" name="q" class="form-control" 
                               placeholder="Başlık veya içerik ara..." 
                               aria-label="Arama" required minlength="3">
                        <button class="btn btn-outline-primary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>

                <!-- Menü Öğeleri -->
                <ul class="navbar-nav">
                    <?php if ($auth->isLoggedIn()): ?>
                        <!-- Yeni Başlık -->
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center" href="/submit.php">
                                <i class="bi bi-plus-lg me-2"></i>
                                <span>Yeni Başlık</span>
                            </a>
                        </li>
                        
                        <!-- Bildirimler -->
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center" href="/notifications.php">
                                <i class="bi bi-bell me-2 position-relative">
                                    <?php if (isset($_SESSION['unread_notifications']) && $_SESSION['unread_notifications'] > 0): ?>
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            <?= $_SESSION['unread_notifications'] ?>
                                        </span>
                                    <?php endif; ?>
                                </i>
                                <span>Bildirimler</span>
                            </a>
                        </li>
                        
                        <!-- Profil Menüsü -->
                        <li class="nav-item dropdown">
                            <a class="nav-link d-flex align-items-center" href="#" 
                               data-bs-toggle="dropdown">
                                <?php if ($currentUser['avatar']): ?>
                                    <img src="<?= htmlspecialchars($currentUser['avatar']) ?>" 
                                         alt="Profil" class="rounded-circle me-2" width="24" height="24">
                                <?php else: ?>
                                    <i class="bi bi-person-circle me-2"></i>
                                <?php endif; ?>
                                <span><?= htmlspecialchars($auth->getDisplayName()) ?></span>
                            </a>
                            <ul class="dropdown-menu">
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
                        
                        <!-- Tema Değiştirici -->
                        <li class="nav-item">
                            <button class="nav-link d-flex align-items-center border-0 bg-transparent w-100" 
                                    onclick="toggleTheme()">
                                <i class="bi bi-moon-stars me-2"></i>
                                <span>Tema</span>
                            </button>
                        </li>
                    <?php else: ?>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary" onclick="showLoginModal()">
                                <i class="bi bi-box-arrow-in-right"></i>
                                <span class="d-none d-md-inline ms-1">Giriş Yap</span>
                            </button>
                            <button type="button" class="btn btn-primary" onclick="showRegisterModal()">
                                <i class="bi bi-person-plus"></i>
                                <span class="d-none d-md-inline ms-1">Kayıt Ol</span>
                            </button>
                        </div>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</nav>

<style>
/* Navbar Stilleri */
.navbar {
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.navbar-toggler:focus {
    box-shadow: none;
}

/* Offcanvas Stilleri */
.offcanvas {
    max-width: 300px;
}

.offcanvas-backdrop.show {
    opacity: 0.7;
}

/* Mobil Menü Animasyonları */
.offcanvas.offcanvas-end {
    transform: translateX(100%);
    transition: transform 0.3s ease-in-out;
}

.offcanvas.offcanvas-end.show {
    transform: translateX(0);
}

/* Mobil Menü Öğeleri */
@media (max-width: 991.98px) {
    .nav-search {
        width: 100%;
    }
    
    .navbar-nav .nav-link {
        padding: 0.8rem 0;
        border-bottom: 1px solid var(--bs-border-color);
    }
    
    .navbar-nav .nav-link:last-child {
        border-bottom: none;
    }
    
    .dropdown-menu {
        border: none;
        padding: 0;
        margin: 0;
        box-shadow: none;
    }
    
    .dropdown-item {
        padding: 0.8rem 1rem;
    }
}

/* Tema Geçiş Animasyonu */
.nav-link {
    transition: color 0.2s ease;
}

.nav-link:hover {
    color: var(--bs-primary);
}
</style>

<script>
// Tema değiştirme fonksiyonu
function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    
    // Tema butonunu güncelle
    const themeIcon = document.querySelector('.nav-link i.bi-moon-stars, .nav-link i.bi-sun');
    themeIcon.className = `bi ${newTheme === 'dark' ? 'bi-sun' : 'bi-moon-stars'} me-2`;
}

// Sayfa yüklendiğinde tema kontrolü
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    
    const themeIcon = document.querySelector('.nav-link i.bi-moon-stars, .nav-link i.bi-sun');
    themeIcon.className = `bi ${savedTheme === 'dark' ? 'bi-sun' : 'bi-moon-stars'} me-2`;
});
</script> 