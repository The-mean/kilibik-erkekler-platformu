<?php
require_once __DIR__ . '/init.php';

class Notification {
    private $db;
    private $mailer;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Yeni bildirim ekle
     */
    public function add($userId, $type, $message, $relatedId = null, $relatedType = null, $link = null) {
        try {
            $this->db->insert('notifications', [
                'user_id' => $userId,
                'type' => $type,
                'message' => $message,
                'related_id' => $relatedId,
                'related_type' => $relatedType,
                'link' => $link,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // E-posta bildirimi ayarını kontrol et
            $user = $this->db->fetchOne(
                "SELECT email, email_notifications FROM users WHERE id = :id",
                [':id' => $userId]
            );
            
            if ($user && $user['email_notifications']) {
                $this->sendEmailNotification($user['email'], $message, $link);
            }
            
            return true;
        } catch (Exception $e) {
            error_log('Bildirim eklenirken hata: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Kullanıcının okunmamış bildirimlerini getir
     */
    public function getUnread($userId, $limit = 10) {
        return $this->db->fetchAll(
            "SELECT * FROM notifications 
            WHERE user_id = :user_id AND status = 'unread' 
            ORDER BY created_at DESC LIMIT :limit",
            [':user_id' => $userId, ':limit' => $limit]
        );
    }
    
    /**
     * Kullanıcının tüm bildirimlerini getir
     */
    public function getAll($userId, $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        
        return $this->db->fetchAll(
            "SELECT * FROM notifications 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC 
            LIMIT :limit OFFSET :offset",
            [
                ':user_id' => $userId,
                ':limit' => $perPage,
                ':offset' => $offset
            ]
        );
    }
    
    /**
     * Bildirimi okundu olarak işaretle
     */
    public function markAsRead($notificationId, $userId) {
        return $this->db->update(
            'notifications',
            [
                'status' => 'read',
                'read_at' => date('Y-m-d H:i:s')
            ],
            'id = :id AND user_id = :user_id',
            [
                ':id' => $notificationId,
                ':user_id' => $userId
            ]
        );
    }
    
    /**
     * Tüm bildirimleri okundu olarak işaretle
     */
    public function markAllAsRead($userId) {
        return $this->db->update(
            'notifications',
            [
                'status' => 'read',
                'read_at' => date('Y-m-d H:i:s')
            ],
            'user_id = :user_id AND status = :status',
            [
                ':user_id' => $userId,
                ':status' => 'unread'
            ]
        );
    }
    
    /**
     * Okunmamış bildirim sayısını getir
     */
    public function getUnreadCount($userId) {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM notifications 
            WHERE user_id = :user_id AND status = 'unread'",
            [':user_id' => $userId]
        );
        
        return (int)$result['count'];
    }
    
    /**
     * E-posta bildirimi gönder
     */
    private function sendEmailNotification($email, $message, $link = null) {
        $subject = "Yeni Bildirim - Kılıbık Erkekler";
        
        $body = "Merhaba,\n\n";
        $body .= "Yeni bir bildiriminiz var:\n";
        $body .= $message . "\n\n";
        
        if ($link) {
            $body .= "Detaylar için tıklayın: " . $_ENV['APP_URL'] . $link . "\n\n";
        }
        
        $body .= "Saygılarımızla,\n";
        $body .= "Kılıbık Erkekler Platformu";
        
        // Mail gönderme işlemi
        $headers = "From: " . $_ENV['MAIL_FROM_ADDRESS'] . "\r\n";
        $headers .= "Reply-To: " . $_ENV['MAIL_FROM_ADDRESS'] . "\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        mail($email, $subject, $body, $headers);
        
        // E-posta gönderildi olarak işaretle
        $this->db->update(
            'notifications',
            ['email_sent' => 1],
            'user_id = :user_id AND status = :status',
            [
                ':user_id' => $userId,
                ':status' => 'unread'
            ]
        );
    }
} 