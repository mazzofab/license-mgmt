<?php

namespace OCA\DrivingLicenseReminder\Controller;

use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\Files\Node;

use OCA\DrivingLicenseReminder\Service\LicenseService;

class LicenseController extends Controller {
    private $licenseService;

    public function __construct($AppName, IRequest $request, LicenseService $licenseService) {
        parent::__construct($AppName, $request);
        $this->licenseService = $licenseService;
    }

    public function importCSV(): JSONResponse {
        $file = $this->request->getUploadedFile('csvFile');
        if (!$file || !$file->isValid()) {
            return new JSONResponse(['success' => false, 'message' => 'Invalid file upload'], 400);
        }

        $csvData = file_get_contents($file->getRealPath());
        $rows = array_map('str_getcsv', explode("\n", trim($csvData)));
        $header = array_map('trim', array_shift($rows));

        foreach ($rows as $row) {
            if (count($row) !== count($header)) continue;
            $data = array_combine($header, array_map('trim', $row));
            if (!$data) continue;

            $this->licenseService->addLicense(
                $data['name'] ?? '',
                $data['surname'] ?? '',
                $data['license_number'] ?? '',
                $data['expiry_date'] ?? '',
                $data['phone'] ?? ''
            );
        }

        return new JSONResponse(['success' => true, 'message' => 'CSV imported successfully']);
    }
}