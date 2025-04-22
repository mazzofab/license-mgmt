/**
 * Driver License Management - Drivers Module
 */
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // DOM Elements
    const driversList = document.getElementById('drivers-list');
    const loadMoreButton = document.getElementById('load-more');
    const driverForm = document.getElementById('driver-form');
    const driverEditorModal = document.getElementById('driver-editor-modal');
    const deleteConfirmationModal = document.getElementById('delete-confirmation-modal');
    const modalTitle = document.getElementById('modal-title');
    const searchInput = document.getElementById('driver-search');
    
    // State
    let drivers = [];
    let currentPage = 1;
    let isLastPage = false;
    let currentSearchQuery = '';
    const driversPerPage = 20;
    
    // Initialize
    loadDrivers();
    setupEventListeners();
    
    /**
     * Set up event listeners
     */
    function setupEventListeners() {
        // Add driver button
        document.querySelector('.new-driver-button').addEventListener('click', function() {
            openDriverEditorModal();
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
        
        // Save driver form
        driverForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveDriver();
        });
        
        // Confirm delete button
        document.getElementById('confirm-delete').addEventListener('click', function() {
            const driverId = document.getElementById('delete-driver-id').value;
            deleteDriver(driverId);
        });
        
        // Load more button
        loadMoreButton.addEventListener('click', function() {
            loadMoreDrivers();
        });
        
        // Search input
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            if (query !== currentSearchQuery) {
                currentSearchQuery = query;
                currentPage = 1;
                loadDrivers();
            }
        });
        
        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === driverEditorModal || event.target === deleteConfirmationModal) {
                closeModals();
            }
        });
    }
    
    /**
     * Load drivers from the API
     */
    function loadDrivers() {
        driversList.innerHTML = '<tr class="empty-row"><td colspan="6">' + t('driverlicensemgmt', 'Loading drivers...') + '</td></tr>';
        
        let url = OC.generateUrl('/apps/driverlicensemgmt/api/drivers');
        const params = {
            limit: driversPerPage,
            offset: (currentPage - 1) * driversPerPage
        };
        
        if (currentSearchQuery) {
            url = OC.generateUrl('/apps/driverlicensemgmt/api/drivers/search');
            params.query = currentSearchQuery;
        }
        
        fetch(url + '?' + new URLSearchParams(params), {
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
            if (currentPage === 1) {
                drivers = [];
            }
            
            if (currentSearchQuery) {
                drivers = data;
            } else {
                drivers = drivers.concat(data.data);
                isLastPage = data.data.length < driversPerPage || (data.total && drivers.length >= data.total);
            }
            
            renderDrivers();
            toggleLoadMoreButton();
        })
        .catch(error => {
            console.error('Error fetching drivers:', error);
            driversList.innerHTML = '<tr class="empty-row"><td colspan="6">' + 
                t('driverlicensemgmt', 'Error loading drivers. Please try again.') + '</td></tr>';
        });
    }
    
    /**
     * Load more drivers (next page)
     */
    function loadMoreDrivers() {
        if (!isLastPage) {
            currentPage++;
            loadDrivers();
        }
    }
    
    /**
     * Render drivers list
     */
    function renderDrivers() {
        if (drivers.length === 0) {
            driversList.innerHTML = '<tr class="empty-row"><td colspan="6">' + 
                t('driverlicensemgmt', 'No drivers found. Add your first driver using the + button above.') + '</td></tr>';
            return;
        }
        
        driversList.innerHTML = '';
        const now = new Date();
        
        drivers.forEach(driver => {
            // Improved date handling - handle various date formats
            let expiryDateObj;
            let expiryDisplay = '';
            let expiryClass = 'expiry-ok';
            let daysUntilExpiry = 0;
            
            // Try parsing the date in different formats
            try {
                if (typeof driver.expiryDate === 'string') {
                    // Try ISO format first (YYYY-MM-DD)
                    if (driver.expiryDate.includes('T')) {
                        // Handle full ISO datetime
                        expiryDateObj = new Date(driver.expiryDate);
                    } else {
                        // Handle date-only format
                        const parts = driver.expiryDate.split('-');
                        if (parts.length === 3) {
                            // YYYY-MM-DD format
                            expiryDateObj = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
                        }
                    }
                } else if (driver.expiryDate && driver.expiryDate.date) {
                    // Handle PHP DateTime object converted to JSON
                    expiryDateObj = new Date(driver.expiryDate.date);
                }
                
                if (expiryDateObj && !isNaN(expiryDateObj.getTime())) {
                    // Date is valid
                    expiryDisplay = formatDate(expiryDateObj);
                    daysUntilExpiry = Math.ceil((expiryDateObj - now) / (1000 * 60 * 60 * 24));
                    
                    if (daysUntilExpiry <= 0) {
                        expiryClass = 'expiry-expired';
                    } else if (daysUntilExpiry <= 30) {
                        expiryClass = 'expiry-warning';
                    }
                } else {
                    // Date is invalid
                    expiryDisplay = t('driverlicensemgmt', 'Invalid Date');
                    expiryClass = 'expiry-expired';
                }
            } catch (e) {
                // Error parsing date
                console.error('Error parsing date:', e, driver.expiryDate);
                expiryDisplay = t('driverlicensemgmt', 'Invalid Date');
                expiryClass = 'expiry-expired';
            }
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${escapeHTML(driver.name)}</td>
                <td>${escapeHTML(driver.surname)}</td>
                <td>${escapeHTML(driver.licenseNumber)}</td>
                <td>
                    <span class="expiry-status ${expiryClass}">${expiryDisplay}</span>
                </td>
                <td>${escapeHTML(driver.phoneNumber)}</td>
                <td class="actions">
                    <div class="action-buttons">
                        <button class="edit-button" data-id="${driver.id}" title="${t('driverlicensemgmt', 'Edit')}">
                            <span class="icon-rename"></span>
                        </button>
                        <button class="delete-button" data-id="${driver.id}" 
                                data-name="${escapeHTML(driver.name)} ${escapeHTML(driver.surname)}" 
                                title="${t('driverlicensemgmt', 'Delete')}">
                            <span class="icon-delete"></span>
                        </button>
                        <button class="test-notification-button" data-id="${driver.id}" 
                                title="${t('driverlicensemgmt', 'Test Notification')}">
                            <span class="icon-notifications"></span>
                        </button>
                    </div>
                </td>
            `;
            
            // Add event listeners for action buttons
            row.querySelector('.edit-button').addEventListener('click', function() {
                const driverId = this.getAttribute('data-id');
                openDriverEditorModal(driverId);
            });
            
            row.querySelector('.delete-button').addEventListener('click', function() {
                const driverId = this.getAttribute('data-id');
                const driverName = this.getAttribute('data-name');
                openDeleteConfirmationModal(driverId, driverName);
            });
            
            // Add event listener for test notification button
            row.querySelector('.test-notification-button').addEventListener('click', function() {
                const driverId = this.getAttribute('data-id');
                sendTestNotification(driverId);
            });
            
            driversList.appendChild(row);
        });
    }
    
    /**
     * Send a test notification for a driver
     *
     * @param {string} driverId - Driver ID to send notification for
     */
    function sendTestNotification(driverId) {
        const url = OC.generateUrl(`/apps/driverlicensemgmt/api/test/notification/${driverId}/7`);
        
        fetch(url, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'requesttoken': OC.requestToken
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.message || 'Error sending test notification');
                });
            }
            return response.json();
        })
        .then(data => {
            // Show success message
            OC.Notification.showTemporary(t('driverlicensemgmt', 'Test notification sent. Check your notification bell.'));
        })
        .catch(error => {
            console.error('Error sending test notification:', error);
            OC.Notification.showTemporary(error.message || t('driverlicensemgmt', 'Error sending test notification'));
        });
    }
    
    /**
     * Toggle load more button visibility
     */
    function toggleLoadMoreButton() {
        if (isLastPage) {
            loadMoreButton.style.display = 'none';
        } else {
            loadMoreButton.style.display = 'inline-block';
        }
    }
    
    /**
     * Open driver editor modal
     * @param {string|null} driverId - Driver ID to edit, or null for a new driver
     */
    function openDriverEditorModal(driverId = null) {
        // Reset form
        driverForm.reset();
        document.getElementById('driver-id').value = '';
        
        if (driverId) {
            // Edit mode
            modalTitle.textContent = t('driverlicensemgmt', 'Edit Driver');
            const driver = drivers.find(d => d.id == driverId);
            
            if (driver) {
                document.getElementById('driver-id').value = driver.id;
                document.getElementById('name').value = driver.name;
                document.getElementById('surname').value = driver.surname;
                document.getElementById('license_number').value = driver.licenseNumber;
                
                // Handle different date formats for editing
                let expiryDateStr = '';
                try {
                    let expiryDateObj;
                    if (typeof driver.expiryDate === 'string') {
                        if (driver.expiryDate.includes('T')) {
                            expiryDateObj = new Date(driver.expiryDate);
                        } else {
                            const parts = driver.expiryDate.split('-');
                            if (parts.length === 3) {
                                expiryDateObj = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
                            }
                        }
                    } else if (driver.expiryDate && driver.expiryDate.date) {
                        expiryDateObj = new Date(driver.expiryDate.date);
                    }
                    
                    if (expiryDateObj && !isNaN(expiryDateObj.getTime())) {
                        expiryDateStr = formatDateForInput(expiryDateObj);
                    }
                } catch (e) {
                    console.error('Error formatting date for input:', e);
                }
                
                document.getElementById('expiry_date').value = expiryDateStr;
                document.getElementById('phone_number').value = driver.phoneNumber;
            }
        } else {
            // Add mode
            modalTitle.textContent = t('driverlicensemgmt', 'Add Driver');
            // Set default expiry date to 1 year from now
            const oneYearFromNow = new Date();
            oneYearFromNow.setFullYear(oneYearFromNow.getFullYear() + 1);
            document.getElementById('expiry_date').value = formatDateForInput(oneYearFromNow);
        }
        
        driverEditorModal.style.display = 'block';
    }
    
    /**
     * Open delete confirmation modal
     * @param {string} driverId - Driver ID to delete
     * @param {string} driverName - Driver name for confirmation message
     */
    function openDeleteConfirmationModal(driverId, driverName) {
        document.getElementById('delete-driver-id').value = driverId;
        document.getElementById('delete-driver-name').textContent = driverName;
        deleteConfirmationModal.style.display = 'block';
    }
    
    /**
     * Close all modals
     */
    function closeModals() {
        driverEditorModal.style.display = 'none';
        deleteConfirmationModal.style.display = 'none';
    }
    
    /**
     * Save driver (create or update)
     */
    function saveDriver() {
        const driverId = document.getElementById('driver-id').value;
        const isEditMode = driverId !== '';
        
        const driverData = {
            name: document.getElementById('name').value,
            surname: document.getElementById('surname').value,
            licenseNumber: document.getElementById('license_number').value,
            expiryDate: document.getElementById('expiry_date').value,
            phoneNumber: document.getElementById('phone_number').value
        };
        
        let url = OC.generateUrl('/apps/driverlicensemgmt/api/drivers');
        let method = 'POST';
        
        if (isEditMode) {
            url += '/' + driverId;
            method = 'PUT';
        }
        
        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'requesttoken': OC.requestToken
            },
            body: JSON.stringify(driverData)
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.message || 'Error saving driver');
                });
            }
            return response.json();
        })
        .then(data => {
            closeModals();
            
            // Show success message using Nextcloud's built-in notification system
            OC.Notification.showTemporary(
                isEditMode 
                    ? t('driverlicensemgmt', 'Driver updated successfully') 
                    : t('driverlicensemgmt', 'Driver added successfully')
            );
            
            // Refresh the drivers list
            currentPage = 1;
            loadDrivers();
        })
        .catch(error => {
            console.error('Error saving driver:', error);
            OC.Notification.showTemporary(error.message || t('driverlicensemgmt', 'Error saving driver'));
        });
    }
    
    /**
     * Delete a driver
     * @param {string} driverId - Driver ID to delete
     */
    function deleteDriver(driverId) {
        const url = OC.generateUrl(`/apps/driverlicensemgmt/api/drivers/${driverId}`);
        
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
                    throw new Error(err.message || 'Error deleting driver');
                });
            }
            return response.json();
        })
        .then(data => {
            closeModals();
            
            // Show success message
            OC.Notification.showTemporary(t('driverlicensemgmt', 'Driver deleted successfully'));
            
            // Refresh the drivers list
            currentPage = 1;
            loadDrivers();
        })
        .catch(error => {
            console.error('Error deleting driver:', error);
            OC.Notification.showTemporary(error.message || t('driverlicensemgmt', 'Error deleting driver'));
        });
    }
    
    /**
     * Format date for display
     * @param {Date|string} date - Date object or string
     * @returns {string} Formatted date string
     */
    function formatDate(date) {
        if (!(date instanceof Date)) {
            date = new Date(date);
        }
        
        if (isNaN(date.getTime())) {
            return t('driverlicensemgmt', 'Invalid Date');
        }
        
        return date.toLocaleDateString();
    }
    
    /**
     * Format date for input field
     * @param {Date|string} date - Date object or string
     * @returns {string} Date string in YYYY-MM-DD format
     */
    function formatDateForInput(date) {
        if (!(date instanceof Date)) {
            date = new Date(date);
        }
        
        if (isNaN(date.getTime())) {
            return '';
        }
        
        const year = date.getFullYear();
        // Add leading zero if month or day is less than 10
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const day = date.getDate().toString().padStart(2, '0');
        
        return `${year}-${month}-${day}`;
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