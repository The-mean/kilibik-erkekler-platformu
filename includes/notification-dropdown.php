<?php
// Bildirim yöneticisini başlat
$notificationManager = new NotificationManager($db);
$userId = getCurrentUserId();
$unreadCount = $notificationManager->getUnreadCount($userId);
$notifications = $notificationManager->getUnread($userId, 5);
?>

<!-- Bildirim Dropdown -->
<div class="dropdown notification-wrapper">
    <button class="btn btn-link position-relative" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fas fa-bell"></i>
        <?php if ($unreadCount > 0): ?>
        <span class="notification-badge"><?php echo $unreadCount; ?></span>
        <?php endif; ?>
    </button>
    
    <div class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown">
        <div class="notification-header">
            <h6>Bildirimler</h6>
            <?php if ($unreadCount > 0): ?>
            <button class="btn btn-link btn-sm p-0" onclick="notificationManager.markAllAsRead()">
                Tümünü Okundu İşaretle
            </button>
            <?php endif; ?>
        </div>
        
        <ul class="notification-list">
            <?php if (empty($notifications)): ?>
            <div class="notification-empty">
                <i class="fas fa-bell-slash"></i>
                <p>Bildiriminiz bulunmuyor</p>
            </div>
            <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
            <li class="notification-item <?php echo $notification['status'] === 'unread' ? 'unread' : ''; ?>" 
                data-id="<?php echo $notification['id']; ?>">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p><?php echo htmlspecialchars($notification['message']); ?></p>
                        <small><?php echo formatDate($notification['created_at']); ?></small>
                    </div>
                    <?php if ($notification['status'] === 'unread'): ?>
                    <button class="btn btn-link btn-sm mark-read p-0" title="Okundu olarak işaretle">
                        <i class="fas fa-check"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
            <?php endif; ?>
        </ul>
        
        <?php if (!empty($notifications)): ?>
        <div class="dropdown-divider"></div>
        <a href="/notifications" class="dropdown-item text-center">
            Tüm Bildirimleri Görüntüle
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Yeni Bildirim Toast -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="newNotificationToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <i class="fas fa-bell me-2"></i>
            <strong class="me-auto">Yeni Bildirim</strong>
            <small>Az önce</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Kapat"></button>
        </div>
        <div class="toast-body">
            Yeni bildiriminiz var! Görüntülemek için tıklayın.
        </div>
    </div>
</div>

<?php
/**
 * Tarihi formatla
 */
function formatDate($dateString) {
    $date = new DateTime($dateString);
    $now = new DateTime();
    $diff = $now->diff($date);
    
    if ($diff->y > 0) {
        return $diff->y . ' yıl önce';
    }
    if ($diff->m > 0) {
        return $diff->m . ' ay önce';
    }
    if ($diff->d > 0) {
        return $diff->d . ' gün önce';
    }
    if ($diff->h > 0) {
        return $diff->h . ' saat önce';
    }
    if ($diff->i > 0) {
        return $diff->i . ' dakika önce';
    }
    
    return 'Az önce';
} 