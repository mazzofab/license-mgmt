<?php
script('driverlicensemgmt', 'notifications');
style('driverlicensemgmt', 'style');
?>

<div id="app-content">
    <div id="app-content-wrapper">
        <div class="app-content-detail">
            <div class="section">
                <div class="section-header">
                    <div class="section-title">
                        <h2><?php p($l->t('Notification Recipients')); ?></h2>
                    </div>
                    <div class="section-actions">
                        <div class="actions">
                            <a href="<?php p(\OC::$server->getURLGenerator()->linkToRoute('driverlicensemgmt.page.index')); ?>" class="button">
                                <?php p($l->t('Dashboard')); ?>
                            </a>
                            <a href="<?php p(\OC::$server->getURLGenerator()->linkToRoute('driverlicensemgmt.page.drivers')); ?>" class="button">
                                <?php p($l->t('Drivers')); ?>
                            </a>
                            <button class="primary new-notification-button">
                                <span class="icon-add"></span>
                                <span><?php p($l->t('Add Recipient')); ?></span>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="notifications-container">
                    <div class="notifications-info">
                        <p><?php p($l->t('These email addresses and phone numbers will receive notifications when driver licenses are about to expire.')); ?></p>
                        <p><?php p($l->t('Reminders will be sent 30 days, 7 days, and 1 day before license expiry.')); ?></p>
                    </div>
                    
                    <div class="notifications-table-container">
                        <table class="notifications-table">
                            <thead>
                                <tr>
                                    <th><?php p($l->t('Email')); ?></th>
                                    <th><?php p($l->t('Phone Number')); ?></th>
                                    <th><?php p($l->t('Status')); ?></th>
                                    <th class="actions"><?php p($l->t('Actions')); ?></th>
                                </tr>
                            </thead>
                            <tbody id="notifications-list">
                                <!-- Notification recipients will be loaded here via JavaScript -->
                                <tr class="empty-row">
                                    <td colspan="4" class="empty-message">
                                        <?php p($l->t('Loading notification recipients...')); ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Notification Editor Modal -->
<div id="notification-editor-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modal-title"><?php p($l->t('Add Notification Recipient')); ?></h3>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <form id="notification-form">
                <input type="hidden" id="notification-id" name="id" value="">
                
                <div class="form-group">
                    <label for="email"><?php p($l->t('Email Address')); ?> *</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="phone_number"><?php p($l->t('Phone Number')); ?></label>
                    <input type="tel" id="phone_number" name="phoneNumber">
                    <span class="help-text"><?php p($l->t('Optional')); ?></span>
                </div>
                
                <div class="form-group">
                    <label for="active" class="checkbox-label">
                        <input type="checkbox" id="active" name="active" checked>
                        <?php p($l->t('Active')); ?>
                    </label>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="button primary" id="save-notification"><?php p($l->t('Save')); ?></button>
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
            <p><?php p($l->t('Are you sure you want to delete this notification recipient?')); ?></p>
            <p id="delete-recipient-email"></p>
            <input type="hidden" id="delete-notification-id" value="">
            
            <div class="form-actions">
                <button class="button primary" id="confirm-delete"><?php p($l->t('Delete')); ?></button>
                <button class="button" id="cancel-delete"><?php p($l->t('Cancel')); ?></button>
            </div>
        </div>
    </div>
</div>