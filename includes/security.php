<?php

class Security {
    /**
     * XSS koruması için girdiyi temizler
     */
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }

    /**
     * POST verilerini temizler
     */
    public static function sanitizePost() {
        $_POST = self::sanitize($_POST);
    }

    /**
     * GET verilerini temizler
     */
    public static function sanitizeGet() {
        $_GET = self::sanitize($_GET);
    }

    /**
     * REQUEST verilerini temizler
     */
    public static function sanitizeRequest() {
        $_REQUEST = self::sanitize($_REQUEST);
    }

    /**
     * CSRF token oluşturur veya mevcut tokeni döndürür
     */
    public static function getCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * CSRF token doğrular
     */
    public static function validateCSRFToken($token) {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * CSRF token HTML input elementi oluşturur
     */
    public static function getCSRFTokenInput() {
        $token = self::getCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    /**
     * XSS koruması için HTML özel karakterleri temizler
     */
    public static function sanitizeHTML($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeHTML'], $input);
        }
        
        // HTML özel karakterleri dönüştür
        $output = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Tehlikeli JavaScript olabilecek içerikleri temizle
        $output = preg_replace('/(on\w+)=/', 'data-blocked-$1=', $output);
        $output = preg_replace('/javascript:/i', 'blocked-javascript:', $output);
        
        return $output;
    }

    /**
     * Güvenli URL oluşturur
     */
    public static function sanitizeURL($url) {
        // URL'yi temizle
        $url = filter_var($url, FILTER_SANITIZE_URL);
        
        // Sadece güvenli protokollere izin ver
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'http://' . $url;
        }
        
        return $url;
    }

    /**
     * Güvenli dosya adı oluşturur
     */
    public static function sanitizeFileName($fileName) {
        // Uzantıyı al
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        
        // Dosya adını temizle
        $fileName = pathinfo($fileName, PATHINFO_FILENAME);
        
        // Sadece alfanumerik karakterler, tire ve alt çizgiye izin ver
        $fileName = preg_replace('/[^a-z0-9-_]/i', '-', $fileName);
        
        // Çoklu tireleri tekil tireye dönüştür
        $fileName = preg_replace('/-+/', '-', $fileName);
        
        // Başındaki ve sonundaki tireleri kaldır
        $fileName = trim($fileName, '-');
        
        // Benzersiz bir isim oluştur
        return sprintf('%s-%s.%s', $fileName, uniqid(), $extension);
    }

    /**
     * Güvenli şifre politikası kontrolü
     */
    public static function validatePassword($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Şifre en az 8 karakter olmalıdır.';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Şifre en az bir büyük harf içermelidir.';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Şifre en az bir küçük harf içermelidir.';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Şifre en az bir rakam içermelidir.';
        }
        
        if (!preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $password)) {
            $errors[] = 'Şifre en az bir özel karakter içermelidir.';
        }
        
        return empty($errors) ? true : $errors;
    }

    /**
     * Oturum güvenliği için kullanıcı parmak izi oluşturur
     */
    public static function generateUserFingerprint() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ip = self::getIpAddress();
        return hash('sha256', $userAgent . $ip . $_SERVER['REMOTE_PORT']);
    }

    /**
     * Oturum güvenliğini kontrol eder
     */
    public static function validateSession() {
        if (empty($_SESSION['user_fingerprint'])) {
            return false;
        }
        
        return hash_equals($_SESSION['user_fingerprint'], self::generateUserFingerprint());
    }

    /**
     * Güvenli şifre oluşturur
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
    }

    /**
     * Şifre doğrular
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * SQL injection koruması için değerleri temizler
     */
    public static function escapeSql($value) {
        if (is_array($value)) {
            return array_map([self::class, 'escapeSql'], $value);
        }
        if (is_string($value)) {
            return addslashes($value);
        }
        return $value;
    }

    /**
     * Güvenli header yönlendirmesi
     */
    public static function redirect($url) {
        if (!headers_sent()) {
            header('Location: ' . self::sanitize($url));
            exit;
        }
    }

    /**
     * IP adresini alır
     */
    public static function getIpAddress() {
        $ipAddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED'];
        } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipAddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipAddress = $_SERVER['HTTP_FORWARDED'];
        } else if (isset($_SERVER['REMOTE_ADDR'])) {
            $ipAddress = $_SERVER['REMOTE_ADDR'];
        }
        return $ipAddress;
    }

    /**
     * Dosya yükleme güvenliği
     */
    public static function validateUpload($file, $allowedTypes = [], $maxSize = 5242880) {
        if (!isset($file['error']) || is_array($file['error'])) {
            return false;
        }

        if ($file['size'] > $maxSize) {
            return false;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!empty($allowedTypes) && !in_array($mimeType, $allowedTypes)) {
            return false;
        }

        return true;
    }
} 