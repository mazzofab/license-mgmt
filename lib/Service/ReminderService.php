<?php
namespace OCA\DriverLicenseMgmt\Service;

use Exception;
use OCA\DriverLicenseMgmt\Db\DriverMapper;
use OCA\DriverLicenseMgmt\Db\NotificationMapper;
use OCA\DriverLicenseMgmt\Db\ReminderSentMapper;
use OCP\IL10N;
use OCP\Mail\IMailer;
use Psr\Log\LoggerInterface;

class ReminderService {
    private DriverMapper $driverMapper;
    private NotificationMapper $notificationMapper;
    private ReminderSentMapper $reminderSentMapper;
    private IMailer $mailer;
    private LoggerInterface $logger;
    private IL10N $l;

    public function __construct(
        DriverMapper $driverMapper,
        NotificationMapper $notificationMapper,
        ReminderSentMapper $reminderSentMapper,
        IMailer $mailer,
        LoggerInterface $logger,
        IL10N $l
    ) {
        $this->driverMapper = $driverMapper;
        $this->notificationMapper = $notificationMapper;
        $this->reminderSentMapper = $reminderSentMapper;
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->l = $l;
    }

    /**
     * Send reminders for drivers with expiring licenses
     *
     * @param int $daysToCheck Days before expiry to check for (30, 7, or 1)
     * @return array Contains counts of successes and failures
     */
    public function sendReminders(int $daysToCheck): array {
        if (!in_array($daysToCheck, [30, 7, 1])) {
            throw new Exception('Invalid days parameter. Must be 30, 7, or 1.');
        }

        $result = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0
        ];

        $now = new \DateTime();
        $targetDate = (new \DateTime())->modify('+' . $daysToCheck . ' days');
        
        // Get all drivers with licenses expiring on the target date
        $expiringDrivers = $this->driverMapper->findExpiring($targetDate, $targetDate);
        $notifications = $this->notificationMapper->findActive();

        if (empty($expiringDrivers) || empty($notifications)) {
            return $result;
        }

        foreach ($expiringDrivers as $driver) {
            foreach ($notifications as $notification) {
                // Check if this reminder has already been sent to avoid duplicates
                if ($this->reminderSentMapper->hasReminderBeenSent(
                    $driver->getId(), $notification->getId(), $daysToCheck
                )) {
                    $result['skipped']++;
                    continue;
                }

                try {
                    $this->sendReminderEmail($driver, $notification, $daysToCheck);
                    
                    // Record that we've sent this reminder
                    $this->reminderSentMapper->recordReminderSent(
                        $driver->getId(), $notification->getId(), $daysToCheck
                    );
                    
                    $result['success']++;
                } catch (Exception $e) {
                    $this->logger->error('Failed to send license expiry reminder: ' . $e->getMessage(), [
                        'app' => 'driverlicensemgmt',
                        'driver_id' => $driver->getId(),
                        'notification_id' => $notification->getId()
                    ]);
                    $result['failed']++;
                }
            }
        }

        return $result;
    }

    /**
     * Send a reminder email for a specific driver and notification recipient
     *
     * @param \OCA\DriverLicenseMgmt\Db\Driver $driver
     * @param \OCA\DriverLicenseMgmt\Db\Notification $notification
     * @param int $daysRemaining
     * @return bool
     * @throws Exception
     */
    private function sendReminderEmail($driver, $notification, int $daysRemaining): bool {
        $email = $this->mailer->createMessage();
        
        $emailTemplate = $this->createEmailTemplate($driver, $daysRemaining);
        
        $email->setTo([$notification->getEmail()]);
        $email->setSubject($this->getEmailSubject($driver, $daysRemaining));
        $email->setHtmlBody($emailTemplate);
        $email->setPlainBody(strip_tags($emailTemplate));
        
        return $this->mailer->send($email);
    }

    /**
     * Create email template for reminder
     * 
     * @param \OCA\DriverLicenseMgmt\Db\Driver $driver
     * @param int $daysRemaining
     * @return string
     */
    private function createEmailTemplate($driver, int $daysRemaining): string {
        $fullName = $driver->getName() . ' ' . $driver->getSurname();
        
        $template = '<h3>' . $this->l->t('Driver License Expiry Notification') . '</h3>';
        $template .= '<p>' . $this->l->t('This is an automated reminder that the following driver license will expire soon:') . '</p>';
        $template .= '<table style="border-collapse: collapse; width: 100%; margin-bottom: 20px;">';
        $template .= '<tr><td style="padding: 8px; border: 1px solid #ddd;"><strong>' . $this->l->t('Driver') . ':</strong></td>';
        $template .= '<td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($fullName) . '</td></tr>';
        
        $template .= '<tr><td style="padding: 8px; border: 1px solid #ddd;"><strong>' . $this->l->t('License Number') . ':</strong></td>';
        $template .= '<td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($driver->getLicenseNumber()) . '</td></tr>';
        
        $template .= '<tr><td style="padding: 8px; border: 1px solid #ddd;"><strong>' . $this->l->t('Expiry Date') . ':</strong></td>';
        $template .= '<td style="padding: 8px; border: 1px solid #ddd;">' . $driver->getExpiryDate()->format('Y-m-d') . '</td></tr>';
        
        $template .= '<tr><td style="padding: 8px; border: 1px solid #ddd;"><strong>' . $this->l->t('Phone Number') . ':</strong></td>';
        $template .= '<td style="padding: 8px; border: 1px solid #ddd;">' . htmlspecialchars($driver->getPhoneNumber()) . '</td></tr>';
        $template .= '</table>';
        
        if ($daysRemaining === 1) {
            $template .= '<p><strong>' . $this->l->t('The license will expire tomorrow!') . '</strong></p>';
        } else {
            $template .= '<p><strong>' . $this->l->t('Days remaining until expiry') . ': ' . $daysRemaining . '</strong></p>';
        }
        
        $template .= '<p>' . $this->l->t('Please ensure this license is renewed before it expires.') . '</p>';
        $template .= '<p>' . $this->l->t('This is an automated message from Driver License Management System.') . '</p>';
        
        return $template;
    }

    /**
     * Get email subject based on days remaining
     * 
     * @param \OCA\DriverLicenseMgmt\Db\Driver $driver
     * @param int $daysRemaining
     * @return string
     */
    private function getEmailSubject($driver, int $daysRemaining): string {
        $fullName = $driver->getName() . ' ' . $driver->getSurname();
        
        if ($daysRemaining === 1) {
            return $this->l->t('URGENT: Driver License Expiring Tomorrow - %s', [$fullName]);
        } elseif ($daysRemaining === 7) {
            return $this->l->t('Driver License Expiring in 7 Days - %s', [$fullName]);
        } else {
            return $this->l->t('Driver License Expiring in %s Days - %s', [$daysRemaining, $fullName]);
        }
    }
}