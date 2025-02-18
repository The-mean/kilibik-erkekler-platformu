<?php
class AssetMinifier {
    private $cssCache = [];
    private $jsCache = [];
    private $cacheDir;
    
    public function __construct($cacheDir = null) {
        $this->cacheDir = $cacheDir ?? __DIR__ . '/../public/assets/cache';
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    public function minifyCSS($files, $outputFile = null) {
        $cacheKey = md5(implode('', $files));
        
        if (isset($this->cssCache[$cacheKey])) {
            return $this->cssCache[$cacheKey];
        }
        
        $minified = '';
        foreach ($files as $file) {
            if (!file_exists($file)) {
                continue;
            }
            
            $css = file_get_contents($file);
            
            // Yorumları kaldır
            $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
            
            // Boşlukları temizle
            $css = str_replace(["\r\n", "\r", "\n", "\t"], '', $css);
            $css = preg_replace('/\s+/', ' ', $css);
            
            // Gereksiz boşlukları kaldır
            $css = str_replace(': ', ':', $css);
            $css = str_replace(' {', '{', $css);
            $css = str_replace('{ ', '{', $css);
            $css = str_replace(', ', ',', $css);
            $css = str_replace('} ', '}', $css);
            $css = str_replace(';}', '}', $css);
            
            $minified .= $css;
        }
        
        if ($outputFile) {
            $outputPath = $this->cacheDir . '/' . $outputFile;
            file_put_contents($outputPath, $minified);
            $this->cssCache[$cacheKey] = $outputPath;
            return $outputPath;
        }
        
        $this->cssCache[$cacheKey] = $minified;
        return $minified;
    }
    
    public function minifyJS($files, $outputFile = null) {
        $cacheKey = md5(implode('', $files));
        
        if (isset($this->jsCache[$cacheKey])) {
            return $this->jsCache[$cacheKey];
        }
        
        $minified = '';
        foreach ($files as $file) {
            if (!file_exists($file)) {
                continue;
            }
            
            $js = file_get_contents($file);
            
            // Yorumları kaldır
            $js = preg_replace('/\/\*[\s\S]*?\*\/|([^\\:]|^)\/\/.*$/m', '', $js);
            
            // Boşlukları temizle
            $js = preg_replace('/\s+/', ' ', $js);
            
            // Gereksiz boşlukları kaldır
            $js = str_replace('{ ', '{', $js);
            $js = str_replace(' }', '}', $js);
            $js = str_replace('; ', ';', $js);
            $js = str_replace(', ', ',', $js);
            
            $minified .= $js;
        }
        
        if ($outputFile) {
            $outputPath = $this->cacheDir . '/' . $outputFile;
            file_put_contents($outputPath, $minified);
            $this->jsCache[$cacheKey] = $outputPath;
            return $outputPath;
        }
        
        $this->jsCache[$cacheKey] = $minified;
        return $minified;
    }
    
    public function getCachePath($file) {
        return $this->cacheDir . '/' . basename($file);
    }
    
    public function clearCache() {
        $files = glob($this->cacheDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        $this->cssCache = [];
        $this->jsCache = [];
    }
} 