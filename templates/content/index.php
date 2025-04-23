<?php
script('driverlicensemgmt', 'script');
style('driverlicensemgmt', 'style');
?>

<div id="app-content">
    <div id="app-content-wrapper">
        <div class="app-content-detail">
            <div class="dashboard">
                <div class="dashboard-header">
                    <h2><?php p($l->t('Driving License Management')); ?></h2>
                </div>

                <div class="dashboard-content">
                    <div class="two-columns">
                        <div class="panel">
                            <div class="panel-header">
                                <h3><?php p($l->t('Manage Drivers')); ?></h3>
                            </div>
                            <div class="panel-body">
                                <p><?php p($l->t('Add and manage driver details along with their license information.')); ?></p>
                                <div class="button-container">
                                    <a href="<?php p(\OC::$server->getURLGenerator()->linkToRoute('driverlicensemgmt.page.drivers')); ?>" class="button primary">
                                        <?php p($l->t('Manage Drivers')); ?>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="panel">
                            <div class="panel-header">
                                <h3><?php p($l->t('Notification Recipients')); ?></h3>
                            </div>
                            <div class="panel-body">
                                <p><?php p($l->t('Add and manage email addresses and phone numbers to receive license expiry notifications.')); ?></p>
                                <div class="button-container">
                                    <a href="<?php p(\OC::$server->getURLGenerator()->linkToRoute('driverlicensemgmt.page.notifications')); ?>" class="button primary">
                                        <?php p($l->t('Manage Notifications')); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="csv-import">
                        <form id="csvUploadForm" enctype="multipart/form-data">
                            <label for="csvFile"><?php p($l->t('Import Drivers from CSV:')); ?></label>
                            <input type="file" id="csvFile" name="csvFile" accept=".csv" required />
                            <button type="submit" class="button">Upload</button>
                        </form>
                    </div>

                    <div class="information-section">
                        <h3><?php p($l->t('About this App')); ?></h3>
                        <p><?php p($l->t('The Driver License Management app helps you track driver licenses and send automatic reminders when licenses are about to expire.')); ?></p>
                        <p><?php p($l->t('Reminders are sent at 30, 7, and 1 day before expiry to all active notification recipients.')); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?php print_unescaped(OC_Helper::linkTo('driverlicensemgmt', 'js/importCSV.js')); ?>"></script>