<?php
namespace OCA\DriverLicenseMgmt\BackgroundJob;

use OCA\DriverLicenseMgmt\Service\ReminderService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

class SendReminders extends TimedJob {
    private ReminderService $reminderService;
    private LoggerInterface $logger;

    public function __construct(ITimeFactory $time, ReminderService $reminderService, LoggerInterface $logger) {
        parent::__construct($time);
        
        // Run once a day
        $this->setInterval(60 * 60 * 24);
        
        $this->reminderService = $reminderService;
        $this->logger = $logger;
    }

    /**
     * Execute this job
     *
     * @param array $argument
     */
    protected function run($argument) {
        $this->logger->info('Running driver license expiry reminder job', ['app' => 'driverlicensemgmt']);
        
        $daysToCheck = [30, 7, 1];
        $results = [];
        
        foreach ($daysToCheck as $days) {
            try {
                $result = $this->reminderService->sendReminders($days);
                $results[$days] = $result;
                
                $this->logger->info('Sent reminders for licenses expiring in ' . $days . ' days', [
                    'app' => 'driverlicensemgmt',
                    'successes' => $result['success'],
                    'failures' => $result['failed'],
                    'skipped' => $result['skipped']
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Error sending reminders for ' . $days . ' days: ' . $e->getMessage(), [
                    'app' => 'driverlicensemgmt',
                    'exception' => get_class($e)
                ]);
            }
        }
        
        $this->logger->info('Completed driver license expiry reminder job', [
            'app' => 'driverlicensemgmt',
            'results' => $results
        ]);
    }
}