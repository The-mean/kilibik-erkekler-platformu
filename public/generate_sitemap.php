<?php
require_once __DIR__ . '/../includes/init.php';

function generateSitemap() {
    $db = Database::getInstance();
    $baseUrl = 'https://' . $_SERVER['HTTP_HOST'];
    
    // XML başlangıcı
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
    
    // Ana sayfa
    $xml .= '<url>' . PHP_EOL;
    $xml .= '<loc>' . $baseUrl . '/</loc>' . PHP_EOL;
    $xml .= '<changefreq>daily</changefreq>' . PHP_EOL;
    $xml .= '<priority>1.0</priority>' . PHP_EOL;
    $xml .= '</url>' . PHP_EOL;
    
    // Konular
    $topics = $db->query(
        "SELECT id, slug, updated_at FROM topics WHERE is_deleted = 0 ORDER BY updated_at DESC"
    )->fetchAll();
    
    foreach ($topics as $topic) {
        $xml .= '<url>' . PHP_EOL;
        $xml .= '<loc>' . $baseUrl . '/topic/' . $topic['id'] . '-' . $topic['slug'] . '</loc>' . PHP_EOL;
        $xml .= '<lastmod>' . date('c', strtotime($topic['updated_at'])) . '</lastmod>' . PHP_EOL;
        $xml .= '<changefreq>weekly</changefreq>' . PHP_EOL;
        $xml .= '<priority>0.8</priority>' . PHP_EOL;
        $xml .= '</url>' . PHP_EOL;
    }
    
    // Kullanıcı profilleri
    $users = $db->query(
        "SELECT username, updated_at FROM users WHERE is_banned = 0"
    )->fetchAll();
    
    foreach ($users as $user) {
        $xml .= '<url>' . PHP_EOL;
        $xml .= '<loc>' . $baseUrl . '/profile/' . urlencode($user['username']) . '</loc>' . PHP_EOL;
        $xml .= '<lastmod>' . date('c', strtotime($user['updated_at'])) . '</lastmod>' . PHP_EOL;
        $xml .= '<changefreq>weekly</changefreq>' . PHP_EOL;
        $xml .= '<priority>0.6</priority>' . PHP_EOL;
        $xml .= '</url>' . PHP_EOL;
    }
    
    // Statik sayfalar
    $staticPages = [
        'about' => ['priority' => '0.5', 'changefreq' => 'monthly'],
        'contact' => ['priority' => '0.5', 'changefreq' => 'monthly'],
        'terms' => ['priority' => '0.3', 'changefreq' => 'monthly'],
        'privacy' => ['priority' => '0.3', 'changefreq' => 'monthly']
    ];
    
    foreach ($staticPages as $page => $settings) {
        $xml .= '<url>' . PHP_EOL;
        $xml .= '<loc>' . $baseUrl . '/' . $page . '</loc>' . PHP_EOL;
        $xml .= '<changefreq>' . $settings['changefreq'] . '</changefreq>' . PHP_EOL;
        $xml .= '<priority>' . $settings['priority'] . '</priority>' . PHP_EOL;
        $xml .= '</url>' . PHP_EOL;
    }
    
    $xml .= '</urlset>';
    
    // Dosyayı kaydet
    $sitemapPath = __DIR__ . '/sitemap.xml';
    file_put_contents($sitemapPath, $xml);
    
    // Dosya izinlerini ayarla
    chmod($sitemapPath, 0644);
    
    return true;
}

// Sitemap'i oluştur
try {
    if (generateSitemap()) {
        echo "Sitemap başarıyla oluşturuldu.\n";
    }
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
} 