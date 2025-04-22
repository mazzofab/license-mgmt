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
                    <input type="date" id="expiry_date" name="expiryDate" required>
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