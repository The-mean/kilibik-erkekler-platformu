<?php
if (!isset($auth)) {
    require_once __DIR__ . '/auth.php';
    $auth = new Auth();
}
$currentUser = $auth->getCurrentUser();
?>
<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <!-- Logo ve Site Adı -->
        <a class="navbar-brand d-flex align-items-center py-2" href="/">
            <img src="/assets/images/logo.svg" alt="Logo" width="28" height="28" class="me-2">
            <span class="brand-text">Kılıbık Erkekler</span>
        </a>

        <!-- Yeni Başlık Butonu (Masaüstü) -->
        <a href="/submit.php" class="btn btn-primary rounded-pill px-4 py-2 d-none d-lg-flex align-items-center new-topic-btn ms-4">
            <i class="bi bi-plus-lg me-2"></i>
            <span>Yeni Başlık</span>
        </a>

        <!-- Mobil Menü Butonu -->
        <button class="navbar-toggler border-0" type="button" 
                data-bs-toggle="offcanvas" data-bs-target="#navbarOffcanvas">
            <i class="bi bi-list"></i>
        </button>

        <!-- Offcanvas Menü -->
        <div class="offcanvas offcanvas-end" tabindex="-1" id="navbarOffcanvas">
            <div class="offcanvas-header py-2">
                <h5 class="offcanvas-title d-flex align-items-center">
                    <img src="/assets/images/logo.svg" alt="Logo" width="24" height="24" class="me-2">
                    <span>Kılıbık Erkekler</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
            </div>
            <div class="offcanvas-body pt-2">
                <!-- Arama Formu -->
                <form class="d-flex mb-2 nav-search" action="/search.php" method="GET">
                    <div class="input-group">
                        <input type="search" name="q" class="form-control form-control-sm" 
                               placeholder="Ara..." aria-label="Arama">
                        <button class="btn btn-outline-primary btn-sm" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>

                <!-- Yeni Başlık Butonu (Mobil) -->
                <a href="/submit.php" class="btn btn-primary rounded-pill w-100 mb-3 d-lg-none">
                    <i class="bi bi-plus-lg me-2"></i>
                    <span>Yeni Başlık</span>
                </a>

                <!-- Menü Öğeleri -->
                <ul class="navbar-nav align-items-center">
                    <?php if ($auth->isLoggedIn()): ?>
                        <!-- Bildirimler -->
                        <li class="nav-item">
                            <a class="nav-link position-relative p-2" href="/notifications.php">
                                <i class="bi bi-bell"></i>
                                <?php if (isset($_SESSION['unread_notifications']) && $_SESSION['unread_notifications'] > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?= $_SESSION['unread_notifications'] ?>
                                    </span>
                                <?php endif; ?>
                                <span class="d-lg-none ms-2">Bildirimler</span>
                            </a>
                        </li>
                        
                        <!-- Profil Dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link d-flex align-items-center p-2" href="#" 
                               data-bs-toggle="dropdown">
                                <?php if ($currentUser['avatar']): ?>
                                    <img src="<?= htmlspecialchars($currentUser['avatar']) ?>" 
                                         alt="Profil" class="rounded-circle" width="28" height="28">
                                <?php else: ?>
                                    <i class="bi bi-person-circle"></i>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item py-2" href="/profile.php">
                                        <i class="bi bi-person me-2"></i>Profilim
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2" href="/settings.php">
                                        <i class="bi bi-gear me-2"></i>Ayarlar
                                    </a>
                                </li>
                                <?php if ($currentUser['is_admin'] ?? false): ?>
                                    <li>
                                        <a class="dropdown-item py-2" href="/admin/">
                                            <i class="bi bi-shield-check me-2"></i>Yönetim
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider my-1"></li>
                                <li>
                                    <a class="dropdown-item py-2 text-danger" href="/logout.php">
                                        <i class="bi bi-box-arrow-right me-2"></i>Çıkış
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <button class="btn btn-sm btn-link nav-link p-2" onclick="showLoginModal()">
                                <i class="bi bi-box-arrow-in-right fs-5"></i>
                                <span class="d-lg-none ms-2">Giriş</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1" onclick="showRegisterModal()">
                                <i class="bi bi-person-plus fs-5"></i>
                                <span class="d-lg-none ms-2">Kayıt Ol</span>
                            </button>
                        </li>
                    <?php endif; ?>
                    
                    <!-- Tema Değiştirici -->
                    <li class="nav-item ms-lg-2">
                        <button class="btn btn-sm btn-link nav-link p-2" onclick="toggleTheme()">
                            <i class="bi bi-moon-stars theme-icon"></i>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<style>
/* Navbar Temel Stilleri */
.navbar {
    background-color: var(--navbar-bg);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    border-bottom: 1px solid var(--border-color);
    min-height: 48px;
    padding-top: 0;
    padding-bottom: 0;
}

.navbar.scrolled {
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

/* Logo ve Marka */
.navbar-brand {
    font-weight: 600;
    font-size: 1.1rem;
}

.brand-text {
    background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
}

/* Yeni Başlık Butonu */
.new-topic-btn {
    background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
    border: none;
    font-weight: 500;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.new-topic-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Navbar Butonları */
.navbar .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.navbar .nav-link {
    color: var(--text-color);
    transition: color 0.2s ease;
}

.navbar .nav-link:hover {
    color: var(--primary-color);
}

/* Giriş/Kayıt Butonları */
.navbar .btn-link.nav-link {
    color: var(--text-color);
    transition: all 0.2s ease;
}

.navbar .btn-link.nav-link:hover {
    color: var(--primary-color);
    transform: translateY(-1px);
}

.navbar .btn-outline-primary {
    border-width: 1.5px;
    transition: all 0.2s ease;
}

.navbar .btn-outline-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(var(--primary-rgb), 0.2);
}

/* Mobil Menü */
.navbar-toggler {
    padding: 0.25rem;
}

.navbar-toggler i {
    font-size: 1.5rem;
    color: var(--text-color);
}

.offcanvas {
    background-color: var(--navbar-bg);
    border-left: 1px solid var(--border-color);
}

.offcanvas-header {
    border-bottom: 1px solid var(--border-color);
}

/* Arama Formu */
.nav-search .form-control {
    border-radius: 20px 0 0 20px;
    border: 1px solid var(--border-color);
}

.nav-search .btn {
    border-radius: 0 20px 20px 0;
}

/* Dropdown Menü */
.dropdown-menu {
    background-color: var(--navbar-bg);
    border: 1px solid var(--border-color);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    margin-top: 0.5rem;
    padding: 0.5rem 0;
}

.dropdown-item {
    color: var(--text-color);
}

.dropdown-item:hover {
    background-color: var(--hover-bg);
}

/* Tema Geçiş Animasyonu */
.theme-icon {
    transition: transform 0.3s ease;
}

[data-theme="dark"] .theme-icon {
    transform: rotate(180deg);
}

/* Mobil Uyumluluk */
@media (max-width: 991.98px) {
    .offcanvas {
        width: 280px;
    }
    
    .nav-item {
        width: 100%;
    }
    
    .dropdown-menu {
        border: none;
        padding: 0;
        margin: 0;
        box-shadow: none;
    }
}
</style>

<script>
// Sayfa kaydırma kontrolü
window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});

// Tema değiştirme
function toggleTheme() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    html.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    
    const themeIcon = document.querySelector('.theme-icon');
    themeIcon.classList.remove('bi-moon-stars', 'bi-sun');
    themeIcon.classList.add(newTheme === 'dark' ? 'bi-sun' : 'bi-moon-stars');
}

// Sayfa yüklendiğinde tema kontrolü
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    
    const themeIcon = document.querySelector('.theme-icon');
    themeIcon.classList.remove('bi-moon-stars', 'bi-sun');
    themeIcon.classList.add(savedTheme === 'dark' ? 'bi-sun' : 'bi-moon-stars');
});
</script> 