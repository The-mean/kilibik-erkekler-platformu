<?php
require_once __DIR__ . '/../config/database.php';

class RateLimiter {
    private $db;
    private $limits = [
        'topic' => ['count' => 20, 'window' => 300],     // 5 dakikada 20 başlık
        'comment' => ['count' => 20, 'window' => 300],   // 5 dakikada 20 yorum
        'register' => ['count' => 50, 'window' => 3600], // Saatte 50 kayıt
        'login' => ['count' => 20, 'window' => 300],     // 5 dakikada 20 giriş
        'report' => ['count' => 50, 'window' => 3600]    // Saatte 50 şikayet
    ];

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function checkLimit($ip, $action, $userId = null) {
        if (!isset($this->limits[$action])) {
            return true;
        }

        $limit = $this->limits[$action];
        $window = $limit['window'];
        $maxCount = $limit['count'];

        // Son istekleri kontrol et
        $result = $this->db->query(
            "SELECT COUNT(*) as count FROM rate_limits 
            WHERE ip_address = :ip 
            AND action_type = :action 
            AND UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(last_request) < :window",
            [
                ':ip' => $ip,
                ':action' => $action,
                ':window' => $window
            ]
        );

        $row = $result->fetch(PDO::FETCH_ASSOC);
        $count = $row['count'];

        if ($count >= $maxCount) {
            return false;
        }

        // Yeni istek kaydı
        $this->db->query(
            "INSERT INTO rate_limits (ip_address, action_type, request_count, last_request) 
            VALUES (:ip, :action, 1, CURRENT_TIMESTAMP)",
            [
                ':ip' => $ip,
                ':action' => $action
            ]
        );

        return true;
    }

    public function getRemainingLimit($ip, $action, $userId = null) {
        if (!isset($this->limits[$action])) {
            return 0;
        }

        $limit = $this->limits[$action];
        $window = $limit['window'];
        $maxCount = $limit['count'];

        $result = $this->db->query(
            "SELECT COUNT(*) as count FROM rate_limits 
            WHERE ip_address = :ip 
            AND action_type = :action 
            AND UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(last_request) < :window",
            [
                ':ip' => $ip,
                ':action' => $action,
                ':window' => $window
            ]
        );

        $row = $result->fetch(PDO::FETCH_ASSOC);
        return max(0, $maxCount - $row['count']);
    }
} 