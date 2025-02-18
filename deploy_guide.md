# Kılıbık Erkekler Platformu - Deployment Guide

Bu rehber, Kılıbık Erkekler Platformu'nun canlı sunucuya güvenli bir şekilde deploy edilmesi için gerekli adımları içerir.

## 1. Sunucu Gereksinimleri

- PHP 7.4 veya üzeri
- MySQL 5.7 veya üzeri
- Apache 2.4 veya üzeri / Nginx 1.18 veya üzeri
- SSL Sertifikası (Let's Encrypt önerilir)
- Git

## 2. Dosya Yapısı ve İzinler

```bash
# Ana dizin yapısı
/var/www/kilibikerkekler.com/
├── public/          # Document root (755)
├── includes/        # PHP dosyaları (755)
├── config/          # Yapılandırma dosyaları (755)
├── logs/           # Log dosyaları (755)
└── uploads/        # Yüklenen dosyalar (755)

# Özel izinler
chmod -R 755 /var/www/kilibikerkekler.com
chmod -R 644 /var/www/kilibikerkekler.com/public/.htaccess
chmod -R 755 /var/www/kilibikerkekler.com/logs
chmod -R 755 /var/www/kilibikerkekler.com/uploads
```

## 3. Apache Yapılandırması

```apache
<VirtualHost *:80>
    ServerName kilibikerkekler.com
    ServerAlias www.kilibikerkekler.com
    DocumentRoot /var/www/kilibikerkekler.com/public
    
    <Directory /var/www/kilibikerkekler.com/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # PHP handler
        <FilesMatch \.php$>
            SetHandler application/x-httpd-php
        </FilesMatch>
    </Directory>
    
    # Loglar
    ErrorLog ${APACHE_LOG_DIR}/kilibikerkekler.com-error.log
    CustomLog ${APACHE_LOG_DIR}/kilibikerkekler.com-access.log combined
    
    # HTTPS yönlendirmesi
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</VirtualHost>

<VirtualHost *:443>
    ServerName kilibikerkekler.com
    ServerAlias www.kilibikerkekler.com
    DocumentRoot /var/www/kilibikerkekler.com/public
    
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/kilibikerkekler.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/kilibikerkekler.com/privkey.pem
    
    # HSTS
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    
    # Diğer güvenlik başlıkları
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set X-Content-Type-Options "nosniff"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    
    # Gzip sıkıştırma
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/x-javascript application/json
</VirtualHost>
```

## 4. Nginx Yapılandırması

```nginx
server {
    listen 80;
    server_name kilibikerkekler.com www.kilibikerkekler.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name kilibikerkekler.com www.kilibikerkekler.com;
    root /var/www/kilibikerkekler.com/public;
    index index.php index.html;

    # SSL
    ssl_certificate /etc/letsencrypt/live/kilibikerkekler.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/kilibikerkekler.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;

    # Güvenlik başlıkları
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Gzip sıkıştırma
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml application/json application/javascript application/xml+rss application/atom+xml image/svg+xml;

    # PHP-FPM yapılandırması
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Statik dosyalar için önbellek
    location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
    }

    # Güvenlik için gizli dosyalara erişimi engelle
    location ~ /\. {
        deny all;
    }
}
```

## 5. PHP Yapılandırması (php.ini)

```ini
; Güvenlik ayarları
expose_php = Off
display_errors = Off
log_errors = On
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
allow_url_fopen = Off
allow_url_include = Off
max_execution_time = 30
max_input_time = 60
memory_limit = 128M
post_max_size = 10M
upload_max_filesize = 5M
max_file_uploads = 20

; Session güvenliği
session.cookie_httponly = On
session.cookie_secure = On
session.use_strict_mode = On
session.cookie_samesite = "Lax"
session.gc_maxlifetime = 1440
session.gc_probability = 1
session.gc_divisor = 100

; Önbellek ayarları
opcache.enable = On
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 4000
```

## 6. Veritabanı Yapılandırması

1. `.env` dosyası oluştur:

```bash
# .env
DB_HOST=localhost
DB_NAME=kilibik_erkekler
DB_USER=your_db_user
DB_PASS=your_secure_password
DB_CHARSET=utf8mb4

# Diğer ayarlar
APP_ENV=production
APP_DEBUG=false
APP_URL=https://kilibikerkekler.com
```

2. `config/database.php` dosyasını güncelle:

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

define('DB_HOST', $_ENV['DB_HOST']);
define('DB_NAME', $_ENV['DB_NAME']);
define('DB_USER', $_ENV['DB_USER']);
define('DB_PASS', $_ENV['DB_PASS']);
define('DB_CHARSET', $_ENV['DB_CHARSET']);
```

## 7. Log Temizleme (Cron Job)

1. Log temizleme script'i oluştur (`scripts/clean_logs.php`):

```php
<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/logger.php';

$logger = Logger::getInstance();
$logger->backupLogs();
```

2. Cron job ekle:

```bash
# Günlük log temizleme (her gece 03:00'da)
0 3 * * * php /var/www/kilibikerkekler.com/scripts/clean_logs.php >> /var/www/kilibikerkekler.com/logs/cron.log 2>&1
```

## 8. GitHub Repository Ayarları

1. `.gitignore` dosyası oluştur:

```gitignore
# Yapılandırma
.env
config/*.local.php

# Loglar ve yüklemeler
logs/*
!logs/.gitkeep
uploads/*
!uploads/.gitkeep

# Bağımlılıklar
vendor/
node_modules/

# IDE ve sistem dosyaları
.idea/
.vscode/
.DS_Store
Thumbs.db
```

2. Repository'ye ekle:

```bash
git init
git add .
git commit -m "Initial commit"
git branch -M main
git remote add origin https://github.com/username/kilibik-erkekler.git
git push -u origin main
```

## 9. Deploy Sonrası Kontrol Listesi

- [ ] SSL sertifikası aktif ve çalışıyor
- [ ] Tüm yönlendirmeler HTTPS'e yapılıyor
- [ ] Veritabanı bağlantısı çalışıyor
- [ ] Log dosyaları oluşturuluyor ve yazılabiliyor
- [ ] Dosya yükleme çalışıyor
- [ ] Cron job'lar çalışıyor
- [ ] Önbellek sistemleri aktif
- [ ] Güvenlik başlıkları doğru ayarlanmış
- [ ] robots.txt ve sitemap.xml erişilebilir
- [ ] Tüm statik dosyalar (CSS, JS, resimler) yükleniyor

## 10. Performans İyileştirmeleri

1. CDN Kullanımı:
```html
<!-- Bootstrap CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Font Awesome CDN -->
<link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.0.0/css/all.min.css" rel="stylesheet">
```

2. Resim Optimizasyonu:
```bash
# WebP dönüşümü için script
find /var/www/kilibikerkekler.com/public/uploads -type f \( -iname "*.jpg" -o -iname "*.png" \) -exec sh -c 'cwebp -q 80 "$1" -o "${1%.*}.webp"' sh {} \;
```

## 11. Bakım Modu

Bakım modu için `public/maintenance.php` dosyası oluştur ve gerektiğinde kullan:

```php
<?php
http_response_code(503);
header('Retry-After: 3600');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Bakım Çalışması - Kılıbık Erkekler</title>
    <meta name="robots" content="noindex,nofollow">
    <style>
        /* Bakım sayfası stilleri */
    </style>
</head>
<body>
    <div class="maintenance">
        <h1>Bakım Çalışması</h1>
        <p>Sitemiz kısa süreliğine bakımda. Lütfen daha sonra tekrar deneyin.</p>
    </div>
</body>
</html>
```

## 12. Yedekleme Stratejisi

1. Veritabanı yedekleme script'i (`scripts/backup_db.php`):

```php
<?php
require_once __DIR__ . '/../includes/init.php';

$backupDir = __DIR__ . '/../backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
$command = sprintf(
    'mysqldump -h %s -u %s -p%s %s > %s/%s',
    DB_HOST,
    DB_USER,
    DB_PASS,
    DB_NAME,
    $backupDir,
    $filename
);

exec($command);
```

2. Yedekleme cron job'ı:

```bash
# Günlük veritabanı yedekleme (her gece 02:00'da)
0 2 * * * php /var/www/kilibikerkekler.com/scripts/backup_db.php >> /var/www/kilibikerkekler.com/logs/backup.log 2>&1
``` 