<?php
require_once __DIR__ . '/security.php';

class Markdown {
    private static $instance = null;
    private $purifier;
    
    private function __construct() {
        // HTML Purifier konfigürasyonu
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,b,i,strong,em,a[href],ul,ol,li,code,pre,blockquote,h1,h2,h3,h4,h5,h6,img[src|alt],table,thead,tbody,tr,th,td,hr,br');
        $config->set('HTML.Nofollow', true);
        $config->set('HTML.TargetBlank', true);
        $config->set('AutoFormat.AutoParagraph', true);
        $config->set('AutoFormat.RemoveEmpty', true);
        
        $this->purifier = new HTMLPurifier($config);
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Markdown içeriğini güvenli HTML'e dönüştürür
     */
    public function parse($markdown) {
        // Markdown'ı HTML'e dönüştür
        $parsedown = new Parsedown();
        $parsedown->setSafeMode(true);
        $html = $parsedown->text($markdown);
        
        // HTML'i temizle
        $html = $this->purifier->purify($html);
        
        // Kod bloklarını highlight et
        $html = preg_replace_callback('/<pre><code.*?>(.*?)<\/code><\/pre>/s', function($matches) {
            $code = htmlspecialchars_decode($matches[1]);
            return '<pre><code class="hljs">' . $code . '</code></pre>';
        }, $html);
        
        return $html;
    }
    
    /**
     * Markdown içeriğini önizleme için dönüştürür
     * (Daha az kısıtlayıcı, sadece temel XSS koruması)
     */
    public function parsePreview($markdown) {
        $parsedown = new Parsedown();
        $parsedown->setSafeMode(true);
        return $parsedown->text($markdown);
    }
    
    /**
     * Markdown içeriğini özetler
     */
    public function excerpt($markdown, $length = 200) {
        // Markdown'ı düz metne dönüştür
        $text = strip_tags($this->parse($markdown));
        
        // Gereksiz boşlukları temizle
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Metni kısalt
        if (mb_strlen($text) > $length) {
            $text = mb_substr($text, 0, $length) . '...';
        }
        
        return $text;
    }
} 