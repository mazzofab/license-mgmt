<?php
script('driverlicensemgmt', 'drivers');
style('driverlicensemgmt', 'style');
?>

<div id="app-content">
    <div id="app-content-wrapper">
        <div class="app-content-detail">
            <div class="section">
                <div class="section-header">
                    <div class="section-title">
                        <h2><?php p($l->t('Driver License Management')); ?></h2>
                    </div>
                    <div class="section-actions">
                        <div class="actions">
                            <a href="<?php p(\OC::$server->getURLGenerator()->linkToRoute('driverlicensemgmt.page.index')); ?>" class="button">
                                <?php p($l->t('Dashboard')); ?>
                            </a>
                            <a href="<?php p(\OC::$server->getURLGenerator()->linkToRoute('driverlicensemgmt.page.notifications')); ?>" class="button">
                                <?php p($l->t('Notifications')); ?>
                            </a>
                            <button class="primary new-driver-button">
                                <span class="icon-add"></span>
                                <span><?php p($l->t('Add Driver')); ?></span>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="drivers-container">
                    <div class="filters">
                        <input type="text" id="driver-search" class="search-input" placeholder="<?php p($l->t('Search drivers...')); ?>">
                    </div>
                    
                    <div class="drivers-table-container">
                        <table class="drivers-table">
                            <thead>
                                <tr>
                                    <th><?php p($l->t('Name')); ?></th>
                                    <th><?php p($l->t('Surname')); ?></th>
                                    <th><?php p($l->t('License Number')); ?></th>
                                    <th><?php p($l->t('Expiry Date')); ?></th>
                                    <th><?php p($l->t('Phone Number')); ?></th>
                                    <th class="actions"><?php p($l->t('Actions')); ?></th>
                                </tr>
                            </thead>
                            <tbody id="drivers-list">
                                <!-- Drivers will be loaded here via JavaScript -->
                                <tr class="empty-row">
                                    <td colspan="6" class="empty-message">
                                        <?php p($l->t('Loading drivers...')); ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <div class="pagination-container">
                            <button id="load-more" class="button load-more">
                                <?php p($l->t('Load More')); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Driver Editor Modal -->
<div id="driver-editor-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modal-title"><?php p($l->t('Add Driver')); ?></h3>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <form id="driver-form">
                <input type="hidden" id="driver-id" name="id" value="">
                
                <div class="form-group">
                    <label for="name"><?php p($l->t('Name')); ?> *</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="surname"><?php p($l->t('Surname')); ?> *</label>
                    <input type="text" id="surname" name="surname" required>
                </div>
                
                <div class="form-group">
                    <label for="license_number"><?php p($l->t('License Number')); ?> *</label>
                    <input type="text" id="license_number" name="licenseNumber" required>
                </div>
                
                <div class="form-group">
                    <label for="expiry_date"><?php p($l->t('Expiry Date')); ?> *</label>
                    <input type="text" id="expiry_date" name="expiryDate" class="datepicker" required>
                </div>
                
                <div class="form-group">
                    <label for="phone_number"><?php p($l->t('Phone Number')); ?> *</label>
                    <input type="tel" id="phone_number" name="phoneNumber" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="button primary" id="save-driver"><?php p($l->t('Save')); ?></button>
                    <button type="button" class="button" id="cancel-save"><?php p($l->t('Cancel')); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-confirmation-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><?php p($l->t('Confirm Deletion')); ?></h3>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <p><?php p($l->t('Are you sure you want to delete this driver?')); ?></p>
            <p id="delete-driver-name"></p>
            <input type="hidden" id="delete-driver-id" value="">
            
            <div class="form-actions">
                <button class="button primary" id="confirm-delete"><?php p($l->t('Delete')); ?></button>
                <button class="button" id="cancel-delete"><?php p($l->t('Cancel')); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
// Add this script block to initialize the date picker with locale support
document.addEventListener('DOMContentLoaded', function() {
    // Check if jQuery UI datepicker is available (included in Nextcloud)
    if ($.datepicker) {
        // Get user locale from Nextcloud
        const userLocale = OC.getLocale() || navigator.language || 'en';
        
        // Initialize the datepicker with locale settings
        $('#expiry_date').datepicker({
            dateFormat: OC.datepickerDateFormat || 'yy-mm-dd',
            firstDay: OC.datepickerFirstDay || 0,
            changeMonth: true,
            changeYear: true,
            yearRange: 'c-10:c+10',
            showOtherMonths: true,
            selectOtherMonths: true
        });
        
        // Add custom handling for form submission
        // This ensures the date is converted to the proper format for backend processing
        $('#driver-form').on('submit', function(e) {
            try {
                // Get the date from datepicker
                const datepickerDate = $('#expiry_date').datepicker('getDate');
                
                // If we have a valid date, convert it to YYYY-MM-DD format for backend
                if (datepickerDate) {
                    const year = datepickerDate.getFullYear();
                    const month = String(datepickerDate.getMonth() + 1).padStart(2, '0');
                    const day = String(datepickerDate.getDate()).padStart(2, '0');
                    
                    // Store the ISO formatted date in a hidden field for submission
                    if (!$('#expiry_date_formatted').length) {
                        $('<input>').attr({
                            type: 'hidden',
                            id: 'expiry_date_formatted',
                            name: 'expiryDateFormatted',
                            value: `${year}-${month}-${day}`
                        }).appendTo('#driver-form');
                    } else {
                        $('#expiry_date_formatted').val(`${year}-${month}-${day}`);
                    }
                }
            } catch (error) {
                console.error('Error formatting date:', error);
            }
        });
        
        // Handle date formats when editing an existing driver
        // Add this to your existing code that populates the form
        // This code assumes your drivers.js has an editDriver function that populates the form
        const originalEditDriver = window.editDriver;
        if (typeof originalEditDriver === 'function') {
            window.editDriver = function(driverId) {
                // Call the original function
                originalEditDriver(driverId);
                
                // Additional code to handle date formatting
                // This should be adapted to match how your editDriver function works
                setTimeout(function() {
                    const expiryDate = $('#expiry_date').val();
                    if (expiryDate) {
                        try {
                            // Parse the ISO date into a Date object
                            const date = new Date(expiryDate);
                            // Set it in the datepicker
                            $('#expiry_date').datepicker('setDate', date);
                        } catch (error) {
                            console.error('Error parsing date:', error);
                        }
                    }
                }, 100); // Short delay to ensure the original function has completed
            };
        }
    }
});
</script>