<?php
namespace OCA\DriverLicenseMgmt\Controller;

use OCA\DriverLicenseMgmt\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\Util;

class PageController extends Controller {
    private $userId;

    public function __construct(IRequest $request, ?string $userId) {
        parent::__construct(Application::APP_ID, $request);
        $this->userId = $userId;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index(): TemplateResponse {
        Util::addScript(Application::APP_ID, 'script');
        Util::addStyle(Application::APP_ID, 'style');
        
        return new TemplateResponse(Application::APP_ID, 'content/index');
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function drivers(): TemplateResponse {
        Util::addScript(Application::APP_ID, 'drivers');
        Util::addStyle(Application::APP_ID, 'style');
        
        return new TemplateResponse(Application::APP_ID, 'content/drivers');
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function notifications(): TemplateResponse {
        Util::addScript(Application::APP_ID, 'notifications');
        Util::addStyle(Application::APP_ID, 'style');
        
        return new TemplateResponse(Application::APP_ID, 'content/notifications');
    }
}