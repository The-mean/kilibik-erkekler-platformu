<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/notification.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bu işlem için giriş yapmanız gerekiyor.']);
    exit;
}

$notification = new Notification();
$userId = $auth->getEffectiveUserId();

// İstek tipini kontrol et
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_unread':
        // Okunmamış bildirimleri getir
        $notifications = $notification->getUnread($userId);
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'count' => count($notifications)
        ]);
        break;
        
    case 'get_all':
        // Sayfalama parametreleri
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 20);
        
        // Tüm bildirimleri getir
        $notifications = $notification->getAll($userId, $page, $perPage);
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'page' => $page,
            'per_page' => $perPage
        ]);
        break;
        
    case 'mark_read':
        // POST verilerini al
        $data = json_decode(file_get_contents('php://input'), true);
        $notificationId = (int)($data['notification_id'] ?? 0);
        
        if ($notificationId) {
            $success = $notification->markAsRead($notificationId, $userId);
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Bildirim okundu olarak işaretlendi.' : 'Bildirim işaretlenirken hata oluştu.'
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Geçersiz bildirim ID.']);
        }
        break;
        
    case 'mark_all_read':
        // Tüm bildirimleri okundu olarak işaretle
        $success = $notification->markAllAsRead($userId);
        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Tüm bildirimler okundu olarak işaretlendi.' : 'Bildirimler işaretlenirken hata oluştu.'
        ]);
        break;
        
    case 'count':
        // Okunmamış bildirim sayısını getir
        $count = $notification->getUnreadCount($userId);
        echo json_encode([
            'success' => true,
            'count' => $count
        ]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Geçersiz işlem.']);
        break;
} 