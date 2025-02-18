<footer class="footer mt-auto">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4 mb-md-0">
                <h5 class="mb-3">Kılıbık Erkekler Platformu</h5>
                <p class="text-muted">
                    Türkiye'nin en büyük kılıbık erkekler topluluğu. 
                    Deneyimlerinizi paylaşın, başkalarının hikayelerini okuyun.
                </p>
                <div class="social-links">
                    <a href="https://facebook.com/kilibikerkekler" target="_blank" rel="noopener">
                        <i class="bi bi-facebook"></i>
                    </a>
                    <a href="https://twitter.com/kilibikerkekler" target="_blank" rel="noopener">
                        <i class="bi bi-twitter"></i>
                    </a>
                    <a href="https://instagram.com/kilibikerkekler" target="_blank" rel="noopener">
                        <i class="bi bi-instagram"></i>
                    </a>
                    <a href="https://youtube.com/kilibikerkekler" target="_blank" rel="noopener">
                        <i class="bi bi-youtube"></i>
                    </a>
                </div>
            </div>
            
            <div class="col-md-2 mb-4 mb-md-0">
                <h6 class="mb-3">Platform</h6>
                <ul class="footer-links">
                    <li><a href="/about.php">Hakkımızda</a></li>
                    <li><a href="/contact.php">İletişim</a></li>
                    <li><a href="/rules.php">Kurallar</a></li>
                    <li><a href="/faq.php">S.S.S</a></li>
                </ul>
            </div>
            
            <div class="col-md-2 mb-4 mb-md-0">
                <h6 class="mb-3">Yasal</h6>
                <ul class="footer-links">
                    <li><a href="/privacy.php">Gizlilik Politikası</a></li>
                    <li><a href="/terms.php">Kullanım Koşulları</a></li>
                    <li><a href="/cookies.php">Çerez Politikası</a></li>
                    <li><a href="/gdpr.php">KVKK</a></li>
                </ul>
            </div>
            
            <div class="col-md-4">
                <h6 class="mb-3">Mobil Uygulama</h6>
                <div class="d-flex gap-2 mb-3">
                    <a href="#" class="app-store-badge">
                        <img src="/assets/images/app-store.svg" alt="App Store" height="40">
                    </a>
                    <a href="#" class="play-store-badge">
                        <img src="/assets/images/play-store.svg" alt="Play Store" height="40">
                    </a>
                </div>
                <p class="text-muted small">
                    Mobil uygulamamızı indirerek bildirimlerden anında haberdar olun, 
                    offline okuma yapın ve daha fazla özelliğe erişin.
                </p>
            </div>
        </div>
        
        <hr class="my-4">
        
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                <p class="mb-0 text-muted">
                    &copy; <?= date('Y') ?> Kılıbık Erkekler Platformu. Tüm hakları saklıdır.
                </p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <div class="theme-switch">
                    <button class="btn btn-sm btn-outline-secondary" id="themeToggle">
                        <i class="bi bi-moon-stars"></i>
                        <span class="ms-2 d-none d-sm-inline">Karanlık Mod</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</footer>

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