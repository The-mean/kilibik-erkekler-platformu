<?php
class NotificationManager {
    private $db;
    private $mailer;
    
    public function __construct($db, $mailer = null) {
        $this->db = $db;
        $this->mailer = $mailer;
    }
    
    /**
     * Yeni bildirim oluştur
     * 
     * @param int $userId Bildirim alacak kullanıcı ID
     * @param string $message Bildirim mesajı
     * @param string $type Bildirim tipi (comment, like, follow vb.)
     * @param array $data İlgili veriler (topic_id, comment_id vb.)
     * @param bool $sendEmail Email bildirimi gönderilsin mi
     * @return bool
     */
    public function create($userId, $message, $type, $data = [], $sendEmail = false) {
        try {
            // Bildirim oluştur
            $sql = "INSERT INTO notifications (user_id, type, message, related_data, status, created_at) 
                    VALUES (:user_id, :type, :message, :data, 'unread', NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':type' => $type,
                ':message' => $message,
                ':data' => json_encode($data)
            ]);
            
            $notificationId = $this->db->lastInsertId();
            
            // Email bildirimi
            if ($sendEmail && $this->mailer && $this->shouldSendEmail($userId)) {
                $this->sendEmailNotification($userId, $message, $type, $data);
            }
            
            return $notificationId;
        } catch (PDOException $e) {
            error_log("Bildirim oluşturma hatası: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Kullanıcının okunmamış bildirimlerini getir
     * 
     * @param int $userId Kullanıcı ID
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array
     */
    public function getUnread($userId, $limit = 10, $offset = 0) {
        try {
            $sql = "SELECT * FROM notifications 
                    WHERE user_id = :user_id AND status = 'unread' 
                    ORDER BY created_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Okunmamış bildirimleri getirme hatası: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Kullanıcının tüm bildirimlerini getir
     * 
     * @param int $userId Kullanıcı ID
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array
     */
    public function getAll($userId, $limit = 20, $offset = 0) {
        try {
            $sql = "SELECT * FROM notifications 
                    WHERE user_id = :user_id 
                    ORDER BY created_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Tüm bildirimleri getirme hatası: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Bildirimi okundu olarak işaretle
     * 
     * @param int $notificationId Bildirim ID
     * @param int $userId Kullanıcı ID (güvenlik kontrolü için)
     * @return bool
     */
    public function markAsRead($notificationId, $userId) {
        try {
            $sql = "UPDATE notifications 
                    SET status = 'read', read_at = NOW() 
                    WHERE id = :id AND user_id = :user_id";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':id' => $notificationId,
                ':user_id' => $userId
            ]);
        } catch (PDOException $e) {
            error_log("Bildirim okundu işaretleme hatası: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Tüm bildirimleri okundu olarak işaretle
     * 
     * @param int $userId Kullanıcı ID
     * @return bool
     */
    public function markAllAsRead($userId) {
        try {
            $sql = "UPDATE notifications 
                    SET status = 'read', read_at = NOW() 
                    WHERE user_id = :user_id AND status = 'unread'";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':user_id' => $userId]);
        } catch (PDOException $e) {
            error_log("Tüm bildirimleri okundu işaretleme hatası: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Okunmamış bildirim sayısını getir
     * 
     * @param int $userId Kullanıcı ID
     * @return int
     */
    public function getUnreadCount($userId) {
        try {
            $sql = "SELECT COUNT(*) FROM notifications 
                    WHERE user_id = :user_id AND status = 'unread'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Okunmamış bildirim sayısı getirme hatası: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Eski bildirimleri temizle
     * 
     * @param int $days Gün sayısı
     * @return bool
     */
    public function cleanOldNotifications($days = 30) {
        try {
            $sql = "DELETE FROM notifications 
                    WHERE status = 'read' 
                    AND created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':days' => $days]);
        } catch (PDOException $e) {
            error_log("Eski bildirimleri temizleme hatası: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Kullanıcının email bildirimi alıp almayacağını kontrol et
     * 
     * @param int $userId Kullanıcı ID
     * @return bool
     */
    private function shouldSendEmail($userId) {
        try {
            $sql = "SELECT email_notifications FROM user_preferences 
                    WHERE user_id = :user_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            
            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Email tercihi kontrol hatası: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Email bildirimi gönder
     * 
     * @param int $userId Kullanıcı ID
     * @param string $message Bildirim mesajı
     * @param string $type Bildirim tipi
     * @param array $data İlgili veriler
     * @return bool
     */
    private function sendEmailNotification($userId, $message, $type, $data) {
        try {
            // Kullanıcı bilgilerini al
            $sql = "SELECT email, name FROM users WHERE id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return false;
            }
            
            // Email şablonunu hazırla
            $subject = "Yeni Bildirim - " . ucfirst($type);
            $template = $this->getEmailTemplate($type, [
                'userName' => $user['name'],
                'message' => $message,
                'data' => $data
            ]);
            
            // Emaili gönder
            return $this->mailer->send($user['email'], $subject, $template);
        } catch (Exception $e) {
            error_log("Email gönderme hatası: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Email şablonunu al
     * 
     * @param string $type Bildirim tipi
     * @param array $data Şablon verileri
     * @return string
     */
    private function getEmailTemplate($type, $data) {
        // Email şablonları ayrı bir dosyada tutulabilir
        $templates = [
            'comment' => "Merhaba {userName},\n\n{message}\n\nGörüntülemek için tıklayın: {data['link']}",
            'like' => "Merhaba {userName},\n\n{message}",
            'follow' => "Merhaba {userName},\n\n{message}\n\nProfili görüntüle: {data['link']}"
        ];
        
        $template = $templates[$type] ?? "Merhaba {userName},\n\n{message}";
        
        // Şablon değişkenlerini değiştir
        foreach ($data as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
        }
        
        return $template;
    }
} 