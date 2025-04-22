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
        
        // Search input - debounced search with slight delay
        let searchTimeout = null;
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            // Clear previous timeout
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
            
            // Set a new timeout to prevent too many requests while typing
            searchTimeout = setTimeout(function() {
                if (query !== currentSearchQuery) {
                    currentSearchQuery = query;
                    currentPage = 1;
                    loadDrivers();
                }
            }, 300); // 300ms delay
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
        
        let url;
        const params = {};
        
        // Build the proper URL and parameters
        if (currentSearchQuery && currentSearchQuery.trim() !== '') {
            // Search mode
            url = OC.generateUrl('/apps/driverlicensemgmt/api/drivers/search');
            params.query = currentSearchQuery;
        } else {
            // Regular listing mode
            url = OC.generateUrl('/apps/driverlicensemgmt/api/drivers');
        }
        
        // Add pagination params
        params.limit = driversPerPage;
        params.offset = (currentPage - 1) * driversPerPage;
        
        // Build the query string
        const queryString = Object.keys(params)
            .map(key => encodeURIComponent(key) + '=' + encodeURIComponent(params[key]))
            .join('&');
        
        const fullUrl = url + (queryString ? '?' + queryString : '');
        console.log('Loading drivers from:', fullUrl);
        
        fetch(fullUrl, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'requesttoken': OC.requestToken
            }
        })
        .then(response => {
            // Check if response is OK
            if (!response.ok) {
                // Try to parse as JSON first
                return response.text().then(text => {
                    try {
                        // Try to parse as JSON
                        const jsonError = JSON.parse(text);
                        throw new Error(jsonError.message || 'Network response was not ok');
                    } catch (e) {
                        // If not valid JSON, it might be HTML error page
                        if (text.includes('<!DOCTYPE html>') || text.startsWith('<')) {
                            throw new Error('Server returned HTML instead of JSON. There might be a server error.');
                        } else {
                            throw new Error('Network response was not ok: ' + text);
                        }
                    }
                });
            }
            
            // Process successful response
            return response.text().then(text => {
                try {
                    // Try to parse as JSON
                    return JSON.parse(text);
                } catch (e) {
                    // If not valid JSON
                    console.error('Error parsing JSON:', e);
                    console.error('Raw response:', text);
                    throw new Error('Invalid JSON response from server');
                }
            });
        })
        .then(data => {
            console.log('Received data:', data);
            
            if (currentPage === 1) {
                drivers = [];
            }
            
            // Handle different response formats
            if (Array.isArray(data)) {
                // Search results or array response
                drivers = currentPage === 1 ? data : drivers.concat(data);
                isLastPage = data.length < driversPerPage;
                console.log('Processed as array data, drivers count:', drivers.length);
            } else if (data.data && Array.isArray(data.data)) {
                // Standard paginated response
                drivers = currentPage === 1 ? data.data : drivers.concat(data.data);
                isLastPage = data.data.length < driversPerPage || (data.total && drivers.length >= data.total);
                console.log('Processed as paginated data, drivers count:', drivers.length);
            } else {
                // Unknown format
                console.error('Unexpected data format:', data);
                throw new Error('Unexpected data format from server');
            }
            
            renderDrivers();
            toggleLoadMoreButton();
        })
        .catch(error => {
            console.error('Error fetching drivers:', error);
            
            let errorMessage = t('driverlicensemgmt', 'Error loading drivers. Please try again.');
            if (error.message) {
                // If we have a specific error message, display it
                if (error.message.includes('not found') || error.message.includes('does not exist')) {
                    errorMessage = t('driverlicensemgmt', 'Driver not found. It may have been deleted.');
                } else {
                    errorMessage = t('driverlicensemgmt', 'Error loading drivers: ') + error.message;
                }
            }
            
            driversList.innerHTML = '<tr class="empty-row"><td colspan="6">' + errorMessage + '</td></tr>';
            
            // Show notification
            OC.Notification.showTemporary(errorMessage);
            
            // Hide load more button on error
            loadMoreButton.style.display = 'none';
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
        if (!drivers || drivers.length === 0) {
            let message = currentSearchQuery 
                ? t('driverlicensemgmt', 'No drivers found matching your search criteria.')
                : t('driverlicensemgmt', 'No drivers found. Add your first driver using the + button above.');
                
            driversList.innerHTML = '<tr class="empty-row"><td colspan="6">' + message + '</td></tr>';
            return;
        }
        
        driversList.innerHTML = '';
        const now = new Date();
        
        drivers.forEach(driver => {
            // Skip invalid driver objects
            if (!driver || typeof driver !== 'object' || !driver.id) {
                console.warn('Invalid driver object:', driver);
                return;
            }
            
            // Ensure all required properties exist
            driver.name = driver.name || '';
            driver.surname = driver.surname || '';
            driver.licenseNumber = driver.licenseNumber || '';
            driver.phoneNumber = driver.phoneNumber || '';
            
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
                            expiryDateObj = new Date(parseInt(parts[0], 10), 
                                                   parseInt(parts[1], 10) - 1, 
                                                   parseInt(parts[2], 10));
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
                console.error('Error parsing date for driver ' + driver.id + ':', e, driver.expiryDate);
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
                            <span class="icon-bell"></span>
                        </button>
                    </div>
                </td>
            `;
            
            // Add event listeners for action buttons
            row.querySelector('.edit-button').addEventListener('click', function(e) {
                e.preventDefault();
                const driverId = this.getAttribute('data-id');
                openDriverEditorModal(driverId);
            });
            
            row.querySelector('.delete-button').addEventListener('click', function(e) {
                e.preventDefault();
                const driverId = this.getAttribute('data-id');
                const driverName = this.getAttribute('data-name');
                openDeleteConfirmationModal(driverId, driverName);
            });
            
            // Add event listener for test notification button
            row.querySelector('.test-notification-button').addEventListener('click', function(e) {
                e.preventDefault();
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
        if (!driverId) {
            OC.Notification.showTemporary(t('driverlicensemgmt', 'Invalid driver ID for notification'));
            return;
        }
        
        const url = OC.generateUrl(`/apps/driverlicensemgmt/api/test/notification/${driverId}/7`);
        
        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'requesttoken': OC.requestToken
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    try {
                        // Try to parse as JSON
                        const jsonError = JSON.parse(text);
                        throw new Error(jsonError.message || 'Error sending test notification');
                    } catch (e) {
                        // If not valid JSON
                        throw new Error('Error sending test notification');
                    }
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
            
            let errorMessage = t('driverlicensemgmt', 'Error sending test notification');
            if (error.message) {
                if (error.message.includes('not found')) {
                    errorMessage = t('driverlicensemgmt', 'Driver not found. It may have been deleted.');
                } else {
                    errorMessage = error.message;
                }
            }
            
            OC.Notification.showTemporary(errorMessage);
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
            // Edit mode - find the driver
            const driver = drivers.find(d => d && d.id == driverId);
            
            if (driver) {
                console.log('Opening edit modal for driver:', driver);
                modalTitle.textContent = t('driverlicensemgmt', 'Edit Driver');
                
                document.getElementById('driver-id').value = driver.id;
                document.getElementById('name').value = driver.name || '';
                document.getElementById('surname').value = driver.surname || '';
                document.getElementById('license_number').value = driver.licenseNumber || '';
                document.getElementById('phone_number').value = driver.phoneNumber || '';
                
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
                                expiryDateObj = new Date(
                                    parseInt(parts[0], 10), 
                                    parseInt(parts[1], 10) - 1, 
                                    parseInt(parts[2], 10)
                                );
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
            } else {
                // Driver not found - show error and don't open modal
                console.error('Driver not found for ID:', driverId);
                OC.Notification.showTemporary(t('driverlicensemgmt', 'Error: Driver not found. It may have been deleted.'));
                // Refresh the list to show current data
                loadDrivers();
                return;
            }
        } else {
            // Add mode
            console.log('Opening new driver modal');
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
        if (!driverId || !drivers.some(d => d && d.id == driverId)) {
            console.error('Cannot delete: Driver not found for ID:', driverId);
            OC.Notification.showTemporary(t('driverlicensemgmt', 'Error: Driver not found. It may have been deleted.'));
            // Refresh the list to show current data
            loadDrivers();
            return;
        }
        
        document.getElementById('delete-driver-id').value = driverId;
        document.getElementById('delete-driver-name').textContent = driverName || t('driverlicensemgmt', 'this driver');
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
            
            // Check if driver still exists before trying to update
            if (!drivers.some(d => d && d.id == driverId)) {
                closeModals();
                OC.Notification.showTemporary(t('driverlicensemgmt', 'Error: Driver not found. It may have been deleted.'));
                loadDrivers();
                return;
            }
        }
        
        console.log('Saving driver:', driverData, 'to URL:', url, 'with method:', method);
        
        fetch(url, {
            method: method,
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'requesttoken': OC.requestToken
            },
            body: JSON.stringify(driverData)
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    try {
                        // Try to parse as JSON
                        const jsonError = JSON.parse(text);
                        throw new Error(jsonError.message || 'Error saving driver');
                    } catch (e) {
                        // If not valid JSON
                        throw new Error('Error saving driver: Server returned an invalid response');
                    }
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
            
            let errorMessage = t('driverlicensemgmt', 'Error saving driver');
            if (error.message) {
                if (error.message.includes('not found')) {
                    errorMessage = t('driverlicensemgmt', 'Driver not found. It may have been deleted by another user.');
                } else {
                    errorMessage = error.message;
                }
            }
            
            OC.Notification.showTemporary(errorMessage);
        });
    }
    
    /**
     * Delete a driver
     * @param {string} driverId - Driver ID to delete
     */
    function deleteDriver(driverId) {
        if (!driverId) {
            closeModals();
            OC.Notification.showTemporary(t('driverlicensemgmt', 'Invalid driver ID'));
            return;
        }
        
        const url = OC.generateUrl(`/apps/driverlicensemgmt/api/drivers/${driverId}`);
        
        console.log('Deleting driver:', driverId, 'using URL:', url);
        
        fetch(url, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'requesttoken': OC.requestToken
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    try {
                        // Try to parse as JSON
                        const jsonError = JSON.parse(text);
                        throw new Error(jsonError.message || 'Error deleting driver');
                    } catch (e) {
                        // If not valid JSON
                        throw new Error('Error deleting driver: Server returned an invalid response');
                    }
                });
            }
            return response.json();
        })
        .then(data => {
            closeModals();
            
            // Show success message
            OC.Notification.showTemporary(t('driverlicensemgmt', 'Driver deleted successfully'));
            
            // Remove the driver from the local array
            drivers = drivers.filter(d => d && d.id != driverId);
            
            // Refresh the drivers list
            currentPage = 1;
            loadDrivers();
        })
        .catch(error => {
            console.error('Error deleting driver:', error);
            closeModals();
            
            let errorMessage = t('driverlicensemgmt', 'Error deleting driver');
            if (error.message) {
                if (error.message.includes('not found')) {
                    errorMessage = t('driverlicensemgmt', 'Driver not found. It may have been already deleted.');
                } else {
                    errorMessage = error.message;
                }
            }
            
            OC.Notification.showTemporary(errorMessage);
            
            // Refresh the list to show current data
            loadDrivers();
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
     * Format date for input field
     * @param {Date|string} date - Date object or string
     * @returns {string} Date string in YYYY-MM-DD format (required for date inputs)
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