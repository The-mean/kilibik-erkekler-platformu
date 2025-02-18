<?php
// Oturum ayarları
$session_options = [
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
    'gc_maxlifetime' => 3600 // 1 saat
];

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/rate_limiter.php';

class Auth {
    private $db;
    private $rateLimiter;
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOGIN_TIMEOUT = 900; // 15 dakika
    private const SESSION_LIFETIME = 3600; // 1 saat

    public function __construct() {
        $this->db = Database::getInstance();
        $this->rateLimiter = new RateLimiter();
        $this->setupSession();
    }

    private function setupSession() {
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
        } else if (time() - $_SESSION['last_activity'] > self::SESSION_LIFETIME) {
            // Oturum zaman aşımına uğradı
            $this->logout();
            Security::redirect('/login.php?reason=timeout');
        }
        $_SESSION['last_activity'] = time();
    }

    public function register($username, $email, $password) {
        // Rate limiting kontrolü
        $ip = Security::getIpAddress();
        if (!$this->rateLimiter->checkLimit($ip, 'register')) {
            throw new Exception('Çok fazla kayıt denemesi. Lütfen daha sonra tekrar deneyin.');
        }

        // Giriş bilgilerini doğrula
        if (!$this->validateCredentials($username, $email, $password)) {
            return false;
        }

        // Kullanıcı adı ve email kontrolü
        if ($this->isUsernameTaken($username) || $this->isEmailTaken($email)) {
            return false;
        }

        // Şifreyi hashle
        $hashedPassword = Security::hashPassword($password);
        
        try {
            // Kullanıcıyı kaydet
            $userId = $this->db->insert('users', [
                'username' => $username,
                'email' => $email,
                'password' => $hashedPassword,
                'created_at' => date('Y-m-d H:i:s'),
                'is_admin' => 0,
                'is_banned' => 0
            ]);

            // Aktivite logla
            $this->logActivity($userId, 'register', 'Yeni kayıt');
            
            return true;
        } catch (Exception $e) {
            error_log('Kayıt hatası: ' . $e->getMessage());
            return false;
        }
    }

    public function login($username, $password) {
        $ip = Security::getIpAddress();

        // Rate limiting kontrolü
        if (!$this->rateLimiter->checkLimit($ip, 'login')) {
            throw new Exception('Çok fazla başarısız giriş denemesi. Lütfen daha sonra tekrar deneyin.');
        }

        try {
            // Kullanıcıyı bul
            $user = $this->db->fetchOne(
                "SELECT * FROM users WHERE username = :username AND is_banned = 0",
                [':username' => $username]
            );

            if (!$user) {
                $this->logFailedLogin($ip, $username);
                return false;
            }

            // Şifreyi doğrula
            if (!Security::verifyPassword($password, $user['password'])) {
                $this->logFailedLogin($ip, $username);
                return false;
            }

            // Başarılı giriş
            $this->createSession($user);
            $this->logActivity($user['id'], 'login', 'Başarılı giriş');
            $this->resetLoginAttempts($ip);

            return true;
        } catch (Exception $e) {
            error_log('Giriş hatası: ' . $e->getMessage());
            return false;
        }
    }

    private function createSession($user) {
        // Eski oturumu temizle
        session_regenerate_id(true);

        // Oturum bilgilerini ayarla
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = $user['is_admin'];
        $_SESSION['is_anonymous'] = false;
        $_SESSION['created_at'] = time();
        $_SESSION['ip_address'] = Security::getIpAddress();
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

        // Son giriş zamanını güncelle
        $this->db->update(
            'users',
            ['last_login' => date('Y-m-d H:i:s')],
            'id = :id',
            [':id' => $user['id']]
        );
    }

    public function logout() {
        if ($this->isLoggedIn()) {
            $this->logActivity($_SESSION['user_id'], 'logout', 'Çıkış yapıldı');
        }

        // Oturumu temizle
        $_SESSION = [];
        
        // Oturum çerezini sil
        if (isset($_COOKIE[session_name()])) {
            setcookie(
                session_name(),
                '',
                time() - 3600,
                '/',
                '',
                true,
                true
            );
        }
        
        session_destroy();
        return true;
    }

    public function isLoggedIn() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        // Oturum güvenlik kontrolleri
        if ($_SESSION['ip_address'] !== Security::getIpAddress() ||
            $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            $this->logout();
            return false;
        }

        return true;
    }

    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return $this->db->fetchOne(
            "SELECT * FROM users WHERE id = :id",
            [':id' => $_SESSION['user_id']]
        );
    }

    private function validateCredentials($username, $email, $password) {
        // Kullanıcı adı kontrolü
        if (!preg_match('/^[a-zA-Z0-9_-]{3,20}$/', $username)) {
            throw new Exception('Kullanıcı adı 3-20 karakter arasında olmalı ve sadece harf, rakam, tire ve alt çizgi içermelidir.');
        }

        // Email kontrolü
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Geçerli bir e-posta adresi giriniz.');
        }

        // Şifre kontrolü - sadece minimum uzunluk
        if (strlen($password) < 6) {
            throw new Exception('Şifre en az 6 karakter olmalıdır.');
        }

        return true;
    }

    private function isUsernameTaken($username) {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM users WHERE username = :username",
            [':username' => $username]
        );
        return $result && $result['count'] > 0;
    }

    private function isEmailTaken($email) {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM users WHERE email = :email",
            [':email' => $email]
        );
        return $result && $result['count'] > 0;
    }

    private function logFailedLogin($ip, $username) {
        $this->db->insert('login_attempts', [
            'ip_address' => $ip,
            'username' => $username,
            'attempted_at' => date('Y-m-d H:i:s')
        ]);

        // Başarısız giriş sayısını kontrol et
        $attempts = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM login_attempts 
            WHERE ip_address = :ip AND attempted_at > :time",
            [
                ':ip' => $ip,
                ':time' => date('Y-m-d H:i:s', time() - self::LOGIN_TIMEOUT)
            ]
        );

        if ($attempts['count'] >= self::MAX_LOGIN_ATTEMPTS) {
            // IP'yi geçici olarak engelle
            $this->rateLimiter->ban($ip, 'login', self::LOGIN_TIMEOUT);
        }
    }

    private function resetLoginAttempts($ip) {
        $this->db->delete(
            'login_attempts',
            'ip_address = :ip',
            [':ip' => $ip]
        );
    }

    private function logActivity($userId, $action, $description) {
        $this->db->insert('activity_logs', [
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'ip_address' => Security::getIpAddress(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    // Anonim mod özellikleri
    public function enableAnonymousMode() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $_SESSION['is_anonymous'] = true;
        $this->logActivity($_SESSION['user_id'], 'anonymous_mode', 'Anonim mod aktif');
        return true;
    }

    public function disableAnonymousMode() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $_SESSION['is_anonymous'] = false;
        $this->logActivity($_SESSION['user_id'], 'anonymous_mode', 'Anonim mod devre dışı');
        return true;
    }

    public function isAnonymousMode() {
        return isset($_SESSION['is_anonymous']) && $_SESSION['is_anonymous'] === true;
    }

    public function getDisplayName() {
        if (!$this->isLoggedIn()) {
            return 'Misafir-' . substr(md5(Security::getIpAddress()), 0, 6);
        }
        
        if ($this->isAnonymousMode()) {
            return 'Anonim-' . substr(md5($_SESSION['user_id']), 0, 6);
        }
        
        return $_SESSION['username'];
    }

    public function getEffectiveUserId() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        if ($this->isAnonymousMode()) {
            return null;
        }
        
        return $_SESSION['user_id'];
    }
} 