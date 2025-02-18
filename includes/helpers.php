<?php
require_once __DIR__ . '/image_optimizer.php';
require_once __DIR__ . '/asset_minifier.php';

function optimizeImage($path) {
    static $optimizer = null;
    
    if ($optimizer === null) {
        $optimizer = new ImageOptimizer();
    }
    
    try {
        $result = $optimizer->optimize($path);
        return [
            'src' => $result['original'],
            'srcset' => $result['webp'] . ' 1x'
        ];
    } catch (Exception $e) {
        error_log('Resim optimizasyonu hatası: ' . $e->getMessage());
        return ['src' => $path];
    }
}

function minifyAssets() {
    static $minifier = null;
    
    if ($minifier === null) {
        $minifier = new AssetMinifier();
    }
    
    // CSS dosyalarını minify et
    $cssFiles = [
        __DIR__ . '/../public/assets/css/style.css',
        __DIR__ . '/../public/assets/css/theme.css',
        __DIR__ . '/../public/assets/css/markdown-editor.css'
    ];
    
    $minifiedCss = $minifier->minifyCSS($cssFiles, 'styles.min.css');
    
    // JavaScript dosyalarını minify et
    $jsFiles = [
        __DIR__ . '/../public/assets/js/main.js',
        __DIR__ . '/../public/assets/js/markdown-editor.js'
    ];
    
    $minifiedJs = $minifier->minifyJS($jsFiles, 'scripts.min.js');
    
    return [
        'css' => '/assets/cache/' . basename($minifiedCss),
        'js' => '/assets/cache/' . basename($minifiedJs)
    ];
}

function renderImage($src, $alt = '', $class = '', $lazy = true) {
    $optimized = optimizeImage($src);
    $attributes = [
        'src' => $optimized['src'],
        'alt' => htmlspecialchars($alt),
    ];
    
    if (isset($optimized['srcset'])) {
        $attributes['srcset'] = $optimized['srcset'];
        $attributes['type'] = 'image/webp';
    }
    
    if ($lazy) {
        $attributes['loading'] = 'lazy';
    }
    
    if ($class) {
        $attributes['class'] = htmlspecialchars($class);
    }
    
    $attrs = '';
    foreach ($attributes as $key => $value) {
        $attrs .= ' ' . $key . '="' . $value . '"';
    }
    
    return '<img' . $attrs . '>';
} 