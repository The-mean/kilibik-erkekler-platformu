<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/ads.php';

// AdManager örneğini oluştur
$adManager = new AdManager($auth);

// Varsayılan meta bilgileri
$defaultMeta = [
    'title' => 'Kılıbık Erkekler Platformu',
    'description' => 'Kılıbık erkeklerin buluşma noktası. Deneyimlerinizi paylaşın, başkalarının hikayelerini okuyun.',
    'image' => 'https://kilibikerkekler.com/assets/images/og-image.jpg',
    'type' => 'website',
    'url' => 'https://kilibikerkekler.com'
];

// Sayfa başlığı ve meta bilgilerini ayarla
function setPageMeta($meta = []) {
    global $defaultMeta;
    $meta = array_merge($defaultMeta, $meta);
    return $meta;
}

// Başlık detaylarını al
function getTopicMeta($slug) {
    $db = Database::getInstance();
    $result = $db->query(
        "SELECT t.*, u.username, 
        (SELECT COUNT(*) FROM comments WHERE topic_id = t.id AND is_deleted = 0) as comment_count
        FROM topics t 
        LEFT JOIN users u ON t.user_id = u.id 
        WHERE t.slug = :slug AND t.is_deleted = 0",
        [':slug' => $slug]
    );
    
    $topic = $result->fetchArray(SQLITE3_ASSOC);
    if (!$topic) return null;
    
    // İlk yorumu al (description için)
    $result = $db->query(
        "SELECT content FROM comments 
        WHERE topic_id = :topic_id AND is_deleted = 0 
        ORDER BY created_at ASC LIMIT 1",
        [':topic_id' => $topic['id']]
    );
    $firstComment = $result->fetchArray(SQLITE3_ASSOC);
    
    return [
        'title' => $topic['title'] . ' - Kılıbık Erkekler Platformu',
        'description' => $firstComment ? mb_substr(strip_tags($firstComment['content']), 0, 160) : $topic['title'],
        'image' => 'https://kilibikerkekler.com/assets/images/topic-' . $topic['id'] . '.jpg',
        'type' => 'article',
        'url' => 'https://kilibikerkekler.com/baslik/' . $topic['id'] . '-' . $topic['slug'],
        'author' => $topic['username'] ?? 'Anonim',
        'date' => $topic['created_at'],
        'comment_count' => $topic['comment_count']
    ];
}

// Mevcut URL'yi al
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$meta = $defaultMeta;

// URL'ye göre meta bilgilerini ayarla
if (preg_match('/^\/baslik\/(\d+)-([^\/]+)/', $currentPath, $matches)) {
    $topicId = $matches[1];
    $slug = $matches[2];
    $topicMeta = getTopicMeta($slug);
    if ($topicMeta) {
        $meta = $topicMeta;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
    <!-- Temel meta etiketleri -->
    <title><?= htmlspecialchars($meta['title']) ?></title>
    <meta name="description" content="<?= htmlspecialchars($meta['description']) ?>">
    <meta name="robots" content="index, follow">
    <meta name="googlebot" content="index, follow">
    
    <!-- Open Graph meta etiketleri -->
    <meta property="og:title" content="<?= htmlspecialchars($meta['title']) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($meta['description']) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($meta['image']) ?>">
    <meta property="og:type" content="<?= htmlspecialchars($meta['type']) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($meta['url']) ?>">
    <meta property="og:site_name" content="Kılıbık Erkekler Platformu">
    <meta property="og:locale" content="tr_TR">
    
    <?php if ($meta['type'] === 'article'): ?>
        <meta property="article:author" content="<?= htmlspecialchars($meta['author']) ?>">
        <meta property="article:published_time" content="<?= htmlspecialchars($meta['date']) ?>">
        <meta property="article:section" content="Kılıbık Erkekler">
        <meta property="article:tag" content="kılıbık,erkek,platform,forum">
    <?php endif; ?>
    
    <!-- Twitter Card meta etiketleri -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($meta['title']) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($meta['description']) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($meta['image']) ?>">
    <meta name="twitter:site" content="@kilibikerkekler">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?= htmlspecialchars($meta['url']) ?>">
    
    <!-- Alternatif dil bağlantıları -->
    <link rel="alternate" href="<?= htmlspecialchars($meta['url']) ?>" hreflang="tr-TR">
    <link rel="alternate" href="<?= htmlspecialchars($meta['url']) ?>" hreflang="x-default">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/images/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/images/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/theme.css">
    
    <!-- Yapısal veri (JSON-LD) -->
    <?php if ($meta['type'] === 'article'): ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Article",
        "headline": "<?= htmlspecialchars($meta['title']) ?>",
        "author": {
            "@type": "Person",
            "name": "<?= htmlspecialchars($meta['author']) ?>"
        },
        "datePublished": "<?= htmlspecialchars($meta['date']) ?>",
        "image": "<?= htmlspecialchars($meta['image']) ?>",
        "articleSection": "Kılıbık Erkekler",
        "commentCount": <?= (int)$meta['comment_count'] ?>,
        "publisher": {
            "@type": "Organization",
            "name": "Kılıbık Erkekler Platformu",
            "logo": {
                "@type": "ImageObject",
                "url": "https://kilibikerkekler.com/assets/images/logo.png"
            }
        }
    }
    </script>
    <?php else: ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "Kılıbık Erkekler Platformu",
        "url": "https://kilibikerkekler.com",
        "potentialAction": {
            "@type": "SearchAction",
            "target": "https://kilibikerkekler.com/ara?q={search_term_string}",
            "query-input": "required name=search_term_string"
        }
    }
    </script>
    <?php endif; ?>
    
    <!-- Google AdSense -->
    <?= $adManager->getAdScript() ?>
    <?= $adManager->getAdStyle() ?>
    
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXXX"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', 'G-XXXXXXXXXX');
    </script>
    
    <script>
    // Tema yönetimi
    document.addEventListener('DOMContentLoaded', function() {
        // Sistem temasını kontrol et
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        // localStorage'dan kayıtlı temayı al veya sistem temasını kullan
        const savedTheme = localStorage.getItem('theme');
        const theme = savedTheme || (prefersDark ? 'dark' : 'light');
        
        // Temayı uygula
        document.documentElement.setAttribute('data-theme', theme);
    });
    </script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/">
                <img src="/assets/images/logo.png" alt="Kılıbık Erkekler Platformu" width="30" height="30" class="d-inline-block align-text-top">
                Kılıbık Erkekler Platformu
            </a>
            <!-- ... Mevcut navbar içeriği ... -->
        </div>
    </nav>
    
    <?= $adManager->getAdCode('header') ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <!-- Ana içerik alanı -->
                <?= $adManager->getAdCode('content') ?>
            </div>
            <div class="col-md-4">
                <!-- Sidebar -->
                <?= $adManager->getAdCode('sidebar') ?>
            </div>
        </div>
    </div>
    
    <?= $adManager->getAdCode('mobile') ?>
</body>
</html> 