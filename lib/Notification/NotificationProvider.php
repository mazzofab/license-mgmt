<?php
namespace OCA\DriverLicenseMgmt\Notification;

use OCA\DriverLicenseMgmt\Db\DriverMapper;
use OCA\DriverLicenseMgmt\AppInfo\Application;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

class NotificationProvider implements INotifier {
    /** @var IFactory */
    private $l10nFactory;

    /** @var IURLGenerator */
    private $urlGenerator;

    /** @var DriverMapper */
    private $driverMapper;

    /**
     * @param IFactory $l10nFactory
     * @param IURLGenerator $urlGenerator
     * @param DriverMapper $driverMapper
     */
    public function __construct(
        IFactory $l10nFactory,
        IURLGenerator $urlGenerator,
        DriverMapper $driverMapper
    ) {
        $this->l10nFactory = $l10nFactory;
        $this->urlGenerator = $urlGenerator;
        $this->driverMapper = $driverMapper;
    }

    /**
     * Identifier of the notifier
     *
     * @return string
     */
    public function getID(): string {
        return Application::APP_ID;
    }

    /**
     * Human readable name describing the notifier
     *
     * @return string
     */
    public function getName(): string {
        return $this->l10nFactory->get(Application::APP_ID)->t('Driver License Management');
    }

    /**
     * @param INotification $notification
     * @param string $languageCode
     * @return INotification
     * @throws \InvalidArgumentException When the notification was not prepared by a notifier
     */
    public function prepare(INotification $notification, string $languageCode): INotification {
        if ($notification->getApp() !== Application::APP_ID) {
            // Not our notification
            throw new \InvalidArgumentException('Unhandled app');
        }

        // Getting the language
        $l = $this->l10nFactory->get(Application::APP_ID, $languageCode);

        // Process different notification subjects
        if ($notification->getSubject() === 'license_expiring') {
            $params = $notification->getSubjectParameters();
            $driverId = $params['driver'] ?? 0;
            $driverName = $params['name'] ?? '';
            $daysRemaining = $params['days'] ?? 0;
            $expiryDate = $params['expiry_date'] ?? '';

            // Set the title and message based on days remaining
            if ($daysRemaining === 0) {
                $subject = $l->t('License Expiring Today: %s', [$driverName]);
                $message = $l->t('The driver license for %1$s expires today (%2$s).', [$driverName, $expiryDate]);
            } else if ($daysRemaining === 1) {
                $subject = $l->t('License Expiring Tomorrow: %s', [$driverName]);
                $message = $l->t('The driver license for %1$s expires tomorrow (%2$s).', [$driverName, $expiryDate]);
            } else {
                $subject = $l->t('License Expiring Soon: %s', [$driverName]);
                $message = $l->t('The driver license for %1$s expires in %2$s days (%3$s).', [$driverName, $daysRemaining, $expiryDate]);
            }

            // Create link to the drivers page
            $driversUrl = $this->urlGenerator->linkToRouteAbsolute(
                'driverlicensemgmt.page.drivers'
            );

            $notification->setRichSubject($subject)
                ->setRichMessage($message)
                ->setIcon($this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('driverlicensemgmt', 'app.svg')))
                ->setLink($driversUrl);

            return $notification;
        }

        throw new \InvalidArgumentException('Unhandled subject: ' . $notification->getSubject());
    }
}