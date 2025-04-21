<?php
namespace OCA\DriverLicenseMgmt\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

// Use a proper timestamp for migration version
class Version20250421000000 extends SimpleMigrationStep {

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('dlm_drivers')) {
            $table = $schema->createTable('dlm_drivers');
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('name', 'string', [
                'notnull' => true,
                'length' => 128,
            ]);
            $table->addColumn('surname', 'string', [
                'notnull' => true,
                'length' => 128,
            ]);
            $table->addColumn('license_number', 'string', [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('expiry_date', 'date', [
                'notnull' => true,
            ]);
            $table->addColumn('phone_number', 'string', [
                'notnull' => true,
                'length' => 32,
            ]);
            $table->addColumn('user_id', 'string', [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('created_at', 'datetime', [
                'notnull' => true,
            ]);
            $table->addColumn('updated_at', 'datetime', [
                'notnull' => true,
            ]);
            
            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'dlm_drivers_user_id_idx');
            $table->addIndex(['expiry_date'], 'dlm_drivers_expiry_idx');
            $table->addIndex(['license_number'], 'dlm_drivers_license_idx');
        }

        if (!$schema->hasTable('dlm_notifications')) {
            $table = $schema->createTable('dlm_notifications');
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('email', 'string', [
                'notnull' => true,
                'length' => 128,
            ]);
            $table->addColumn('phone_number', 'string', [
                'notnull' => false,
                'length' => 32,
            ]);
            $table->addColumn('active', 'boolean', [
                'notnull' => true,
                'default' => true,
            ]);
            $table->addColumn('user_id', 'string', [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('created_at', 'datetime', [
                'notnull' => true,
            ]);
            $table->addColumn('updated_at', 'datetime', [
                'notnull' => true,
            ]);
            
            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'dlm_notifications_user_id_idx');
            $table->addIndex(['email'], 'dlm_notifications_email_idx');
        }

        if (!$schema->hasTable('dlm_reminders_sent')) {
            $table = $schema->createTable('dlm_reminders_sent');
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('driver_id', 'integer', [
                'notnull' => true,
            ]);
            $table->addColumn('notification_id', 'integer', [
                'notnull' => true,
            ]);
            $table->addColumn('days_before', 'integer', [
                'notnull' => true,
            ]);
            $table->addColumn('sent_at', 'datetime', [
                'notnull' => true,
            ]);
            
            $table->setPrimaryKey(['id']);
            $table->addIndex(['driver_id'], 'dlm_reminders_driver_id_idx');
            $table->addIndex(['notification_id'], 'dlm_reminders_notif_id_idx');
            $table->addIndex(['days_before'], 'dlm_reminders_days_idx');
        }

        return $schema;
    }
}