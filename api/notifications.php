<?php
require_once '../includes/init.php';
require_once '../includes/auth.php';
require_once '../includes/notifications.php';

// Oturum kontrolü
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// CSRF token kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!$token || !validateCsrfToken($token)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit;
    }
}

// Bildirim yöneticisini başlat
$notificationManager = new NotificationManager($db, $mailer);

// İstek metoduna göre işlem yap
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        handleGetRequest();
        break;
        
    case 'POST':
        handlePostRequest();
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

/**
 * GET isteklerini işle
 */
function handleGetRequest() {
    global $notificationManager;
    $userId = getCurrentUserId();
    $action = $_GET['action'] ?? 'all';
    
    switch ($action) {
        case 'unread':
            // Okunmamış bildirimleri getir
            $limit = (int) ($_GET['limit'] ?? 10);
            $offset = (int) ($_GET['offset'] ?? 0);
            
            $notifications = $notificationManager->getUnread($userId, $limit, $offset);
            $unreadCount = $notificationManager->getUnreadCount($userId);
            
            echo json_encode([
                'notifications' => $notifications,
                'unreadCount' => $unreadCount
            ]);
            break;
            
        case 'all':
            // Tüm bildirimleri getir
            $limit = (int) ($_GET['limit'] ?? 20);
            $offset = (int) ($_GET['offset'] ?? 0);
            
            $notifications = $notificationManager->getAll($userId, $limit, $offset);
            $unreadCount = $notificationManager->getUnreadCount($userId);
            
            echo json_encode([
                'notifications' => $notifications,
                'unreadCount' => $unreadCount
            ]);
            break;
            
        case 'count':
            // Okunmamış bildirim sayısını getir
            $count = $notificationManager->getUnreadCount($userId);
            echo json_encode(['count' => $count]);
            break;
            
        case 'new':
            // Yeni bildirim kontrolü
            $lastCheck = $_GET['last_check'] ?? null;
            $hasNew = false;
            
            if ($lastCheck) {
                $count = $notificationManager->getUnreadCount($userId);
                $hasNew = $count > 0;
            }
            
            echo json_encode(['hasNew' => $hasNew]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
}

/**
 * POST isteklerini işle
 */
function handlePostRequest() {
    global $notificationManager;
    $userId = getCurrentUserId();
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'mark_read':
            // Bildirimi okundu olarak işaretle
            $notificationId = (int) ($_POST['notification_id'] ?? 0);
            
            if ($notificationId > 0) {
                $success = $notificationManager->markAsRead($notificationId, $userId);
                echo json_encode(['success' => $success]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid notification ID']);
            }
            break;
            
        case 'mark_all_read':
            // Tüm bildirimleri okundu olarak işaretle
            $success = $notificationManager->markAllAsRead($userId);
            echo json_encode(['success' => $success]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
}

/**
 * CSRF token doğrula
 */
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Mevcut kullanıcı ID'sini al
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? 0;
} 