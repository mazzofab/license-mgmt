/**
 * Driver License Management - Notifications Module
 */
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // DOM Elements
    const notificationsList = document.getElementById('notifications-list');
    const notificationForm = document.getElementById('notification-form');
    const notificationEditorModal = document.getElementById('notification-editor-modal');
    const deleteConfirmationModal = document.getElementById('delete-confirmation-modal');
    const modalTitle = document.getElementById('modal-title');
    
    // State
    let notifications = [];
    
    // Initialize
    loadNotifications();
    setupEventListeners();
    
    /**
     * Set up event listeners
     */
    function setupEventListeners() {
        // Add notification button
        document.querySelector('.new-notification-button').addEventListener('click', function() {
            openNotificationEditorModal();
        });
        
        // Close modal buttons
        document.querySelectorAll('.close-modal').forEach(function(closeButton) {
            closeButton.addEventListener('click', function() {
                closeModals();
            });
        });
        
        // Cancel buttons
        document.getElementById('cancel-save').addEventListener('click', function() {
            closeModals();
        });
        document.getElementById('cancel-delete').addEventListener('click', function() {
            closeModals();
        });
        
        // Save notification form
        notificationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveNotification();
        });
        
        // Confirm delete button
        document.getElementById('confirm-delete').addEventListener('click', function() {
            const notificationId = document.getElementById('delete-notification-id').value;
            deleteNotification(notificationId);
        });
        
        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === notificationEditorModal || event.target === deleteConfirmationModal) {
                closeModals();
            }
        });
    }
    
    /**
     * Load notifications from the API
     */
    function loadNotifications() {
        notificationsList.innerHTML = '<tr class="empty-row"><td colspan="4">' + 
            t('driverlicensemgmt', 'Loading notification recipients...') + '</td></tr>';
        
        const url = OC.generateUrl('/apps/driverlicensemgmt/api/notifications');
        
        fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'requesttoken': OC.requestToken
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            notifications = data;
            renderNotifications();
        })
        .catch(error => {
            console.error('Error fetching notifications:', error);
            notificationsList.innerHTML = '<tr class="empty-row"><td colspan="4">' + 
                t('driverlicensemgmt', 'Error loading notification recipients. Please try again.') + '</td></tr>';
        });
    }
    
    /**
     * Render notifications list
     */
    function renderNotifications() {
        if (notifications.length === 0) {
            notificationsList.innerHTML = '<tr class="empty-row"><td colspan="4">' + 
                t('driverlicensemgmt', 'No notification recipients found. Add your first recipient using the + button above.') + '</td></tr>';
            return;
        }
        
        notificationsList.innerHTML = '';
        
        notifications.forEach(notification => {
            // Get creation and update timestamps in local format
            let createdDate = '';
            let updatedDate = '';
            
            try {
                if (notification.createdAt) {
                    createdDate = formatDate(new Date(notification.createdAt));
                }
                if (notification.updatedAt) {
                    updatedDate = formatDate(new Date(notification.updatedAt));
                }
            } catch (e) {
                console.error('Error formatting dates:', e);
            }
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${escapeHTML(notification.email)}</td>
                <td>${notification.phoneNumber ? escapeHTML(notification.phoneNumber) : '-'}</td>
                <td>
                    <span class="status-badge ${notification.active ? 'status-active' : 'status-inactive'}">
                        ${notification.active ? t('driverlicensemgmt', 'Active') : t('driverlicensemgmt', 'Inactive')}
                    </span>
                </td>
                <td class="actions">
                    <div class="action-buttons">
                        <button class="edit-button" data-id="${notification.id}" title="${t('driverlicensemgmt', 'Edit')}">
                            <span class="icon-rename"></span>
                        </button>
                        <button class="delete-button" data-id="${notification.id}" 
                                data-email="${escapeHTML(notification.email)}" 
                                title="${t('driverlicensemgmt', 'Delete')}">
                            <span class="icon-delete"></span>
                        </button>
                    </div>
                </td>
            `;
            
            // Add event listeners for action buttons
            row.querySelector('.edit-button').addEventListener('click', function() {
                const notificationId = this.getAttribute('data-id');
                openNotificationEditorModal(notificationId);
            });
            
            row.querySelector('.delete-button').addEventListener('click', function() {
                const notificationId = this.getAttribute('data-id');
                const email = this.getAttribute('data-email');
                openDeleteConfirmationModal(notificationId, email);
            });
            
            notificationsList.appendChild(row);
        });
    }
    
    /**
     * Open notification editor modal
     * @param {string|null} notificationId - Notification ID to edit, or null for a new notification
     */
    function openNotificationEditorModal(notificationId = null) {
        // Reset form
        notificationForm.reset();
        document.getElementById('notification-id').value = '';
        document.getElementById('active').checked = true;
        
        if (notificationId) {
            // Edit mode
            modalTitle.textContent = t('driverlicensemgmt', 'Edit Notification Recipient');
            const notification = notifications.find(n => n.id == notificationId);
            
            if (notification) {
                document.getElementById('notification-id').value = notification.id;
                document.getElementById('email').value = notification.email;
                document.getElementById('phone_number').value = notification.phoneNumber || '';
                document.getElementById('active').checked = notification.active;
            }
        } else {
            // Add mode
            modalTitle.textContent = t('driverlicensemgmt', 'Add Notification Recipient');
        }
        
        notificationEditorModal.style.display = 'block';
    }
    
    /**
     * Open delete confirmation modal
     * @param {string} notificationId - Notification ID to delete
     * @param {string} email - Email for confirmation message
     */
    function openDeleteConfirmationModal(notificationId, email) {
        document.getElementById('delete-notification-id').value = notificationId;
        document.getElementById('delete-recipient-email').textContent = email;
        deleteConfirmationModal.style.display = 'block';
    }
    
    /**
     * Close all modals
     */
    function closeModals() {
        notificationEditorModal.style.display = 'none';
        deleteConfirmationModal.style.display = 'none';
    }
    
    /**
     * Save notification (create or update)
     */
    function saveNotification() {
        const notificationId = document.getElementById('notification-id').value;
        const isEditMode = notificationId !== '';
        
        const notificationData = {
            email: document.getElementById('email').value,
            phoneNumber: document.getElementById('phone_number').value,
            active: document.getElementById('active').checked
        };
        
        let url = OC.generateUrl('/apps/driverlicensemgmt/api/notifications');
        let method = 'POST';
        
        if (isEditMode) {
            url += '/' + notificationId;
            method = 'PUT';
        }
        
        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'requesttoken': OC.requestToken
            },
            body: JSON.stringify(notificationData)
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.message || 'Error saving notification recipient');
                });
            }
            return response.json();
        })
        .then(data => {
            closeModals();
            
            // Show success message using Nextcloud's built-in notification system
            OC.Notification.showTemporary(
                isEditMode 
                    ? t('driverlicensemgmt', 'Notification recipient updated successfully') 
                    : t('driverlicensemgmt', 'Notification recipient added successfully')
            );
            
            // Refresh the notifications list
            loadNotifications();
        })
        .catch(error => {
            console.error('Error saving notification recipient:', error);
            OC.Notification.showTemporary(error.message || t('driverlicensemgmt', 'Error saving notification recipient'));
        });
    }
    
    /**
     * Delete a notification recipient
     * @param {string} notificationId - Notification ID to delete
     */
    function deleteNotification(notificationId) {
        const url = OC.generateUrl(`/apps/driverlicensemgmt/api/notifications/${notificationId}`);
        
        fetch(url, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'requesttoken': OC.requestToken
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.message || 'Error deleting notification recipient');
                });
            }
            return response.json();
        })
        .then(data => {
            closeModals();
            
            // Show success message
            OC.Notification.showTemporary(t('driverlicensemgmt', 'Notification recipient deleted successfully'));
            
            // Refresh the notifications list
            loadNotifications();
        })
        .catch(error => {
            console.error('Error deleting notification recipient:', error);
            OC.Notification.showTemporary(error.message || t('driverlicensemgmt', 'Error deleting notification recipient'));
        });
    }
    
    /**
     * Format date for display using local system format
     * @param {Date|string} date - Date object or string
     * @returns {string} Formatted date string in local format
     */
    function formatDate(date) {
        if (!(date instanceof Date)) {
            date = new Date(date);
        }
        
        if (isNaN(date.getTime())) {
            return t('driverlicensemgmt', 'Invalid Date');
        }
        
        // Use the browser's built-in Intl.DateTimeFormat for localized date format
        return new Intl.DateTimeFormat(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        }).format(date);
    }
    
    /**
     * Escape HTML to prevent XSS
     * @param {string} text - Text to escape
     * @returns {string} Escaped text
     */
    function escapeHTML(text) {
        if (text === null || text === undefined) {
            return '';
        }
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});