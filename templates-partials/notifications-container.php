<?php
/**
 * Notifications container template.
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<!-- Global notification container -->
<div class="notification-container" id="notification-container"></div>

<!-- JavaScript for notification system -->
<script>
class NotificationSystem {
    constructor() {
        this.container = document.getElementById('notification-container');
        this.notifications = [];
        this.notificationId = 0;
    }

    /**
     * Show a notification
     * @param {Object} options - Notification options
     * @param {string} options.type - Notification type (success, error, warning, info)
     * @param {string} options.title - Notification title
     * @param {string} options.message - Notification message
     * @param {boolean} options.autoClose - Auto close notification
     * @param {number} options.duration - Duration before auto close
     */
    show(options = {}) {
        const {
            type = 'info',
            title = '',
            message = '',
            autoClose = true,
            duration = 5000,
            inline = false
        } = options;

        const notification = this.createNotification(type, title, message, autoClose, duration, inline);

        if (inline) {
            this.showInline(notification);
        } else {
            this.showFloating(notification);
        }

        return notification;
    }

    /**
     * Create notification element
     */
    createNotification(type, title, message, autoClose, duration, inline) {
        const notificationId = ++this.notificationId;
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}${inline ? ' notification-inline' : ''}`;
        notification.dataset.notificationId = notificationId;

        const icons = {
            success: '✓',
            error: '✕',
            warning: '!',
            info: 'i'
        };

        notification.innerHTML = `
            <div class="notification-icon">${icons[type] || icons.info}</div>
            <div class="notification-content">
                ${title ? `<div class="notification-title">${this.escapeHtml(title)}</div>` : ''}
                <div class="notification-message">${this.escapeHtml(message)}</div>
            </div>
            <button class="notification-close" onclick="notificationSystem.hide(${notificationId})">&times;</button>
        `;

        if (autoClose && !inline) {
            setTimeout(() => this.hide(notificationId), duration);
        }

        return {
            id: notificationId,
            element: notification,
            autoClose,
            duration,
            inline
        };
    }

    /**
     * Show floating notification
     */
    showFloating(notification) {
        this.container.appendChild(notification.element);

        // Trigger animation
        setTimeout(() => {
            notification.element.classList.add('notification-show');
        }, 10);

        this.notifications.push(notification);
    }

    /**
     * Show inline notification
     */
    showInline(notification) {
        // Find or create inline container
        let inlineContainer = document.querySelector('.notifications-inline-container');
        if (!inlineContainer) {
            inlineContainer = document.createElement('div');
            inlineContainer.className = 'notifications-inline-container';

            // Insert at the beginning of the main content area
            const mainContent = document.querySelector('.org-management section > .container') ||
                               document.querySelector('.wicket-orgman') ||
                               document.querySelector('main .container');
            if (mainContent) {
                mainContent.insertBefore(inlineContainer, mainContent.firstChild);
            }
        }

        inlineContainer.appendChild(notification.element);
        this.notifications.push(notification);
    }

    /**
     * Hide notification
     */
    hide(notificationId) {
        const notification = this.notifications.find(n => n.id === notificationId);
        if (!notification) return;

        notification.element.classList.add('notification-hide');

        setTimeout(() => {
            if (notification.element.parentNode) {
                notification.element.parentNode.removeChild(notification.element);
            }
            this.notifications = this.notifications.filter(n => n.id !== notificationId);

            // Clean up empty inline container
            const inlineContainer = document.querySelector('.notifications-inline-container');
            if (inlineContainer && inlineContainer.children.length === 0) {
                inlineContainer.remove();
            }
        }, 300);
    }

    /**
     * Show success notification
     */
    success(message, title = '', options = {}) {
        return this.show({ type: 'success', title, message, ...options });
    }

    /**
     * Show error notification
     */
    error(message, title = '', options = {}) {
        return this.show({ type: 'error', title, message, ...options });
    }

    /**
     * Show warning notification
     */
    warning(message, title = '', options = {}) {
        return this.show({ type: 'warning', title, message, ...options });
    }

    /**
     * Show info notification
     */
    info(message, title = '', options = {}) {
        return this.show({ type: 'info', title, message, ...options });
    }

    /**
     * Clear all notifications
     */
    clear() {
        this.notifications.forEach(notification => {
            if (notification.element.parentNode) {
                notification.element.parentNode.removeChild(notification.element);
            }
        });
        this.notifications = [];

        // Clear inline container
        const inlineContainer = document.querySelector('.notifications-inline-container');
        if (inlineContainer) {
            inlineContainer.remove();
        }
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize global notification system
const notificationSystem = new NotificationSystem();

// Handle Datastar signals for notifications
document.addEventListener('DOMContentLoaded', function() {
    // Listen for custom notification events
    document.addEventListener('show-notification', function(e) {
        const { type, title, message, autoClose, duration, inline } = e.detail;
        notificationSystem.show({ type, title, message, autoClose, duration, inline });
    });

    // Handle legacy notice messages (convert to new system)
    const convertLegacyNotices = () => {
        const legacyNotices = document.querySelectorAll('.notice:not(.notification)');
        legacyNotices.forEach(notice => {
            const type = notice.classList.contains('notice--success') ? 'success' :
                        notice.classList.contains('notice--error') ? 'error' : 'info';

            const message = notice.textContent.trim();
            const title = type.charAt(0).toUpperCase() + type.slice(1);

            // Show new notification
            notificationSystem.show({ type, title, message, inline: true });

            // Remove legacy notice
            notice.style.display = 'none';
            setTimeout(() => notice.remove(), 500);
        });
    };

    // Convert existing notices and watch for new ones
    convertLegacyNotices();

    // Set up MutationObserver to catch dynamically added legacy notices
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes) {
                convertLegacyNotices();
            }
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});

// Make notification system available globally
window.notificationSystem = notificationSystem;
</script>
