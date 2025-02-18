<!-- Footer -->
<footer class="footer mt-5">
    <div class="footer-top py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="footer-brand mb-4">
                        <img src="/assets/images/logo.svg" alt="Logo" width="32" height="32" class="me-2">
                        <span class="brand-text h5 mb-0">Kılıbık Erkekler</span>
                    </div>
                    <p class="text-muted mb-4">
                        Türkiye'nin en büyük kılıbık erkekler topluluğu. 
                        Deneyimlerinizi paylaşın, başkalarının hikayelerini okuyun.
                    </p>
                    <div class="social-links">
                        <a href="https://facebook.com/kilibikerkekler" class="social-link" title="Facebook">
                            <i class="bi bi-facebook"></i>
                        </a>
                        <a href="https://twitter.com/kilibikerkekler" class="social-link" title="Twitter">
                            <i class="bi bi-twitter-x"></i>
                        </a>
                        <a href="https://instagram.com/kilibikerkekler" class="social-link" title="Instagram">
                            <i class="bi bi-instagram"></i>
                        </a>
                        <a href="https://youtube.com/kilibikerkekler" class="social-link" title="YouTube">
                            <i class="bi bi-youtube"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6">
                    <h6 class="footer-title">Platform</h6>
                    <ul class="footer-links">
                        <li><a href="/about">Hakkımızda</a></li>
                        <li><a href="/contact">İletişim</a></li>
                        <li><a href="/rules">Kurallar</a></li>
                        <li><a href="/faq">S.S.S</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-2 col-md-6">
                    <h6 class="footer-title">Yasal</h6>
                    <ul class="footer-links">
                        <li><a href="/privacy">Gizlilik Politikası</a></li>
                        <li><a href="/terms">Kullanım Koşulları</a></li>
                        <li><a href="/cookies">Çerez Politikası</a></li>
                        <li><a href="/gdpr">KVKK</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <h6 class="footer-title">Mobil Uygulama</h6>
                    <p class="text-muted mb-3">Mobil uygulamamızı indirerek bildirimlerden anında haberdar olun.</p>
                    <div class="app-badges">
                        <a href="#" class="app-badge">
                            <img src="/assets/images/app-store.svg" alt="App Store" height="40">
                        </a>
                        <a href="#" class="app-badge ms-2">
                            <img src="/assets/images/play-store.svg" alt="Play Store" height="40">
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="footer-bottom py-3">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0 small">
                        &copy; <?= date('Y') ?> Kılıbık Erkekler Platformu. Tüm hakları saklıdır.
                    </p>
                </div>
                <div class="col-md-6 text-center text-md-end mt-3 mt-md-0">
                    <button class="btn btn-sm btn-link text-muted" id="themeToggle">
                        <i class="bi bi-moon-stars me-2"></i>
                        <span>Tema Değiştir</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</footer>

<style>
/* Footer Stilleri */
.footer {
    background-color: var(--footer-bg);
    border-top: 1px solid var(--border-color);
}

.footer-top {
    background-color: var(--footer-top-bg);
}

.footer-bottom {
    background-color: var(--footer-bottom-bg);
    border-top: 1px solid var(--border-color);
}

.footer-brand {
    display: flex;
    align-items: center;
}

.footer-title {
    color: var(--text-color);
    font-weight: 600;
    margin-bottom: 1.5rem;
    font-size: 1rem;
}

.footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-links li {
    margin-bottom: 0.75rem;
}

.footer-links a {
    color: var(--text-muted);
    text-decoration: none;
    transition: color 0.2s ease;
    font-size: 0.9rem;
}

.footer-links a:hover {
    color: var(--primary-color);
}

.social-links {
    display: flex;
    gap: 1rem;
}

.social-link {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background-color: var(--hover-bg);
    color: var(--text-color);
    text-decoration: none;
    transition: all 0.2s ease;
}

.social-link:hover {
    transform: translateY(-2px);
    color: var(--primary-color);
    background-color: var(--primary-color-light);
}

.app-badges {
    display: flex;
    gap: 1rem;
}

.app-badge {
    transition: transform 0.2s ease;
}

.app-badge:hover {
    transform: translateY(-2px);
}

/* Tema Değişkenleri */
:root[data-theme="light"] {
    --footer-bg: #ffffff;
    --footer-top-bg: #f8f9fa;
    --footer-bottom-bg: #ffffff;
    --text-muted: #6c757d;
    --hover-bg: #f0f2f5;
    --primary-color-light: rgba(52, 152, 219, 0.1);
}

:root[data-theme="dark"] {
    --footer-bg: #1a1a1a;
    --footer-top-bg: #2c2c2c;
    --footer-bottom-bg: #1a1a1a;
    --text-muted: #a0a0a0;
    --hover-bg: #363636;
    --primary-color-light: rgba(97, 218, 251, 0.1);
}

/* Mobil Uyumluluk */
@media (max-width: 767.98px) {
    .footer-title {
        margin-bottom: 1rem;
    }
    
    .footer-links li {
        margin-bottom: 0.5rem;
    }
    
    .social-links {
        justify-content: center;
        margin-top: 1rem;
    }
    
    .app-badges {
        justify-content: center;
    }
}
</style>

<!-- Çerez Bildirimi -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="cookieNotice" class="toast" role="alert">
        <div class="toast-header">
            <i class="bi bi-info-circle me-2"></i>
            <strong class="me-auto">Çerez Bildirimi</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
            <p class="mb-3">
                Bu web sitesi deneyiminizi geliştirmek için çerezleri kullanmaktadır.
                Sitemizi kullanmaya devam ederek çerez kullanımını kabul etmiş olursunuz.
            </p>
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="toast">
                    Reddet
                </button>
                <button type="button" class="btn btn-primary btn-sm" id="acceptCookies">
                    Kabul Et
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Tema değiştirme
document.addEventListener('DOMContentLoaded', function() {
    const themeToggle = document.getElementById('themeToggle');
    const icon = themeToggle.querySelector('i');
    const text = themeToggle.querySelector('span');
    
    // Kaydedilmiş temayı kontrol et
    const currentTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', currentTheme);
    updateThemeButton(currentTheme);
    
    themeToggle.addEventListener('click', function() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateThemeButton(newTheme);
    });
    
    function updateThemeButton(theme) {
        if (theme === 'dark') {
            icon.classList.remove('bi-moon-stars');
            icon.classList.add('bi-sun');
            text.textContent = 'Aydınlık Mod';
        } else {
            icon.classList.remove('bi-sun');
            icon.classList.add('bi-moon-stars');
            text.textContent = 'Karanlık Mod';
        }
    }
});

// Çerez bildirimi
document.addEventListener('DOMContentLoaded', function() {
    const cookieNotice = document.getElementById('cookieNotice');
    const acceptBtn = document.getElementById('acceptCookies');
    
    if (!localStorage.getItem('cookiesAccepted')) {
        const toast = new bootstrap.Toast(cookieNotice);
        toast.show();
    }
    
    acceptBtn.addEventListener('click', function() {
        localStorage.setItem('cookiesAccepted', 'true');
        const toast = bootstrap.Toast.getInstance(cookieNotice);
        toast.hide();
    });
});
</script> 