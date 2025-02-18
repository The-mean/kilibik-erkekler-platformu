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
        <a class="navbar-brand d-flex align-items-center" href="/">
            <img src="/assets/images/logo.svg" alt="Logo" width="32" height="32" class="me-2">
            <span class="brand-text">Kılıbık Erkekler</span>
        </a>

        <!-- Mobil Menü Butonu -->
        <button class="navbar-toggler border-0" type="button" 
                data-bs-toggle="offcanvas" data-bs-target="#navbarOffcanvas">
            <i class="bi bi-list"></i>
        </button>

        <!-- Offcanvas Menü -->
        <div class="offcanvas offcanvas-end" tabindex="-1" id="navbarOffcanvas">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title d-flex align-items-center">
                    <img src="/assets/images/logo.svg" alt="Logo" width="24" height="24" class="me-2">
                    <span>Kılıbık Erkekler</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
            </div>
            <div class="offcanvas-body">
                <!-- Arama Formu -->
                <form class="d-flex mb-3 mt-2 nav-search" action="/search.php" method="GET">
                    <div class="input-group">
                        <input type="search" name="q" class="form-control" 
                               placeholder="Ara..." aria-label="Arama">
                        <button class="btn btn-outline-primary" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>

                <!-- Menü Öğeleri -->
                <ul class="navbar-nav ms-auto align-items-center">
                    <?php if ($auth->isLoggedIn()): ?>
                        <!-- Yeni Başlık -->
                        <li class="nav-item">
                            <a class="nav-link" href="/submit.php">
                                <i class="bi bi-plus-lg"></i>
                                <span class="d-lg-none ms-2">Yeni Başlık</span>
                            </a>
                        </li>
                        
                        <!-- Bildirimler -->
                        <li class="nav-item">
                            <a class="nav-link position-relative" href="/notifications.php">
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
                            <a class="nav-link d-flex align-items-center" href="#" 
                               data-bs-toggle="dropdown">
                                <?php if ($currentUser['avatar']): ?>
                                    <img src="<?= htmlspecialchars($currentUser['avatar']) ?>" 
                                         alt="Profil" class="rounded-circle" width="32" height="32">
                                <?php else: ?>
                                    <i class="bi bi-person-circle"></i>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
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
                                            <i class="bi bi-shield-check me-2"></i>Yönetim
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="/logout.php">
                                        <i class="bi bi-box-arrow-right me-2"></i>Çıkış
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <button class="btn btn-sm btn-link nav-link" onclick="showLoginModal()">
                                <i class="bi bi-box-arrow-in-right"></i>
                                <span class="d-lg-none ms-2">Giriş</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="btn btn-sm btn-primary rounded-pill px-3" onclick="showRegisterModal()">
                                <i class="bi bi-person-plus"></i>
                                <span class="d-lg-none ms-2">Kayıt Ol</span>
                            </button>
                        </li>
                    <?php endif; ?>
                    
                    <!-- Tema Değiştirici -->
                    <li class="nav-item ms-lg-2">
                        <button class="btn btn-sm btn-link nav-link" onclick="toggleTheme()">
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
}

.navbar.scrolled {
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

/* Logo ve Marka */
.navbar-brand {
    font-weight: 600;
    font-size: 1.2rem;
}

.brand-text {
    background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
}

/* Navbar Butonları */
.navbar .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.navbar .nav-link {
    color: var(--text-color);
    padding: 0.5rem 0.75rem;
    transition: color 0.2s ease;
}

.navbar .nav-link:hover {
    color: var(--primary-color);
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
}

.dropdown-item {
    color: var(--text-color);
    padding: 0.5rem 1rem;
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
        padding: 0.5rem 0;
    }
    
    .nav-link {
        padding: 0.5rem 0;
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