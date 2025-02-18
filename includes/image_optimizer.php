<?php
class ImageOptimizer {
    private $quality = 85;
    private $maxWidth = 1920;
    private $maxHeight = 1080;
    
    public function optimize($sourcePath, $destinationPath = null) {
        if (!file_exists($sourcePath)) {
            throw new Exception('Kaynak dosya bulunamadı.');
        }
        
        $destinationPath = $destinationPath ?? $sourcePath;
        $imageInfo = getimagesize($sourcePath);
        
        if (!$imageInfo) {
            throw new Exception('Geçersiz resim dosyası.');
        }
        
        $mimeType = $imageInfo['mime'];
        
        // Resmi yükle
        switch ($mimeType) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($sourcePath);
                break;
            default:
                throw new Exception('Desteklenmeyen resim formatı.');
        }
        
        // Boyutları kontrol et ve yeniden boyutlandır
        $width = imagesx($image);
        $height = imagesy($image);
        
        if ($width > $this->maxWidth || $height > $this->maxHeight) {
            $ratio = min($this->maxWidth / $width, $this->maxHeight / $height);
            $newWidth = round($width * $ratio);
            $newHeight = round($height * $ratio);
            
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            
            // PNG transparanlığını koru
            if ($mimeType === 'image/png') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }
            
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            $image = $resized;
        }
        
        // WebP versiyonunu oluştur
        $webpPath = preg_replace('/\.(jpe?g|png|gif)$/i', '.webp', $destinationPath);
        imagewebp($image, $webpPath, $this->quality);
        
        // Orijinal formatı kaydet
        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($image, $destinationPath, $this->quality);
                break;
            case 'image/png':
                imagepng($image, $destinationPath, round($this->quality / 10));
                break;
            case 'image/gif':
                imagegif($image, $destinationPath);
                break;
        }
        
        imagedestroy($image);
        
        return [
            'original' => $destinationPath,
            'webp' => $webpPath
        ];
    }
    
    public function setQuality($quality) {
        $this->quality = max(0, min(100, $quality));
        return $this;
    }
    
    public function setMaxDimensions($width, $height) {
        $this->maxWidth = $width;
        $this->maxHeight = $height;
        return $this;
    }
} 