// Bildirim yönetimi
class NotificationManager {
    constructor() {
        this.unreadCount = 0;
        this.notifications = [];
        this.dropdown = document.querySelector('.notification-dropdown');
        this.badge = document.querySelector('.notification-badge');
        this.list = document.querySelector('.notification-list');
        this.emptyState = document.querySelector('.notification-empty');

        this.init();
    }

    init() {
        // Event listeners
        document.addEventListener('click', (e) => {
            if (e.target.matches('.mark-read')) {
                const notificationId = e.target.closest('.notification-item').dataset.id;
                this.markAsRead(notificationId);
            }
        });

        // Initial load
        this.loadNotifications();

        // Polling for new notifications
        setInterval(() => this.checkNewNotifications(), 30000);
    }

    async loadNotifications() {
        try {
            const response = await fetch('/api/notifications');
            const data = await response.json();

            this.notifications = data.notifications;
            this.unreadCount = data.unreadCount;

            this.updateUI();
        } catch (error) {
            console.error('Error loading notifications:', error);
        }
    }

    async checkNewNotifications() {
        try {
            const response = await fetch('/api/notifications/new');
            const data = await response.json();

            if (data.hasNew) {
                this.loadNotifications();
                this.showNewNotificationToast();
            }
        } catch (error) {
            console.error('Error checking new notifications:', error);
        }
    }

    async markAsRead(notificationId) {
        try {
            const response = await fetch(`/api/notifications/${notificationId}/read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (response.ok) {
                const notification = this.notifications.find(n => n.id === notificationId);
                if (notification && notification.status === 'unread') {
                    notification.status = 'read';
                    this.unreadCount--;
                    this.updateUI();
                }
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    async markAllAsRead() {
        try {
            const response = await fetch('/api/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (response.ok) {
                this.notifications.forEach(notification => {
                    notification.status = 'read';
                });
                this.unreadCount = 0;
                this.updateUI();
            }
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
        }
    }

    updateUI() {
        // Update badge
        if (this.unreadCount > 0) {
            this.badge.textContent = this.unreadCount;
            this.badge.classList.remove('d-none');
        } else {
            this.badge.classList.add('d-none');
        }

        // Update list
        if (this.notifications.length === 0) {
            this.list.innerHTML = '';
            this.emptyState.classList.remove('d-none');
        } else {
            this.emptyState.classList.add('d-none');
            this.list.innerHTML = this.notifications.map(notification => this.renderNotification(notification)).join('');
        }
    }

    renderNotification(notification) {
        return `
            <li class="notification-item ${notification.status === 'unread' ? 'unread' : ''}" data-id="${notification.id}">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p>${notification.message}</p>
                        <small>${this.formatDate(notification.created_at)}</small>
                    </div>
                    ${notification.status === 'unread' ? `
                        <button class="btn btn-link btn-sm mark-read p-0" title="Okundu olarak işaretle">
                            <i class="fas fa-check"></i>
                        </button>
                    ` : ''}
                </div>
            </li>
        `;
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = now - date;

        // Less than 1 minute
        if (diff < 60000) {
            return 'Az önce';
        }

        // Less than 1 hour
        if (diff < 3600000) {
            const minutes = Math.floor(diff / 60000);
            return `${minutes} dakika önce`;
        }

        // Less than 1 day
        if (diff < 86400000) {
            const hours = Math.floor(diff / 3600000);
            return `${hours} saat önce`;
        }

        // Less than 1 week
        if (diff < 604800000) {
            const days = Math.floor(diff / 86400000);
            return `${days} gün önce`;
        }

        // Format date
        return date.toLocaleDateString('tr-TR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    showNewNotificationToast() {
        const toast = new bootstrap.Toast(document.getElementById('newNotificationToast'));
        toast.show();
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    window.notificationManager = new NotificationManager();
}); 