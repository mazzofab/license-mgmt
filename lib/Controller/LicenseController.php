<?php

namespace OCA\DriverLicenseMgmt\Controller;

use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\Files\Node;
use Psr\Log\LoggerInterface;
use OCP\IUserSession;

use OCA\DriverLicenseMgmt\Service\LicenseService;

class LicenseController extends Controller {
    /** @var LicenseService */
    private $licenseService;
    
    /** @var LoggerInterface */
    private $logger;
    
    /** @var IUserSession */
    private $userSession;

    public function __construct(
        $AppName, 
        IRequest $request,
        LicenseService $licenseService, 
        LoggerInterface $logger,
        IUserSession $userSession
    ) {
        parent::__construct($AppName, $request);
        $this->licenseService = $licenseService;
        $this->logger = $logger;
        $this->userSession = $userSession;
    }

    /**
     * Import drivers from a CSV file
     * 
     * @return JSONResponse
     */
    public function importCSV(): JSONResponse {
        try {
            // Get current user
            $user = $this->userSession->getUser();
            if (!$user) {
                return new JSONResponse(
                    ['success' => false, 'message' => 'User not authenticated'], 
                    401
                );
            }
            $userId = $user->getUID();
            
            // Validate file upload
            $file = $this->request->getUploadedFile('csvFile');
            if (!$file || !isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
                return new JSONResponse(
                    ['success' => false, 'message' => 'Invalid file upload. Please try again.'], 
                    400
                );
            }

            // Validate file type
            $mimeType = $file['type'];
            $fileName = $file['name'];
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            
            if ($mimeType !== 'text/csv' && $mimeType !== 'application/vnd.ms-excel' && $extension !== 'csv') {
                return new JSONResponse(
                    ['success' => false, 'message' => 'Invalid file type. Please upload a CSV file.'], 
                    400
                );
            }

            // Validate file size (2MB max)
            if ($file['size'] > 2 * 1024 * 1024) {
                return new JSONResponse(
                    ['success' => false, 'message' => 'File size exceeds the maximum limit of 2MB.'], 
                    400
                );
            }

            // Read CSV data
            $csvData = file_get_contents($file['tmp_name']);
            if (empty($csvData)) {
                return new JSONResponse(
                    ['success' => false, 'message' => 'The CSV file is empty.'], 
                    400
                );
            }

            // Parse CSV
            $rows = array_map('str_getcsv', explode("\n", trim($csvData)));
            if (count($rows) < 2) { // At least header + 1 data row
                return new JSONResponse(
                    ['success' => false, 'message' => 'The CSV file must contain at least one data row.'], 
                    400
                );
            }

            $this->logger->debug('CSV parsed data: ' . json_encode($rows), ['app' => 'driverlicensemgmt']);

            // Get and validate header
            $header = array_map('trim', array_shift($rows));
            $requiredFields = ['name', 'surname', 'license_number', 'expiry_date'];
            $missingFields = array_diff($requiredFields, $header);
            
            if (!empty($missingFields)) {
                return new JSONResponse(
                    [
                        'success' => false, 
                        'message' => 'Missing required columns: ' . implode(', ', $missingFields)
                    ], 
                    400
                );
            }

            // Process rows
            $imported = 0;
            $errors = [];

            foreach ($rows as $index => $row) {
                // Skip empty rows
                if (empty($row) || (count($row) === 1 && empty($row[0]))) {
                    continue;
                }

                // Validate row length
                if (count($row) !== count($header)) {
                    $errors[] = "Row " . ($index + 2) . " has an invalid number of columns.";
                    continue;
                }

                // Combine header with data
                $data = array_combine($header, array_map('trim', $row));
                if (!$data) {
                    $errors[] = "Could not process row " . ($index + 2) . ".";
                    continue;
                }

                // Map CSV field names to class field names
                $licenseNumber = $data['license_number'] ?? '';
                $expiryDate = $data['expiry_date'] ?? '';
                $phoneNumber = $data['phone'] ?? $data['phone_number'] ?? '';
                
                // Validate required fields
                if (empty($data['name']) || empty($data['surname']) || empty($licenseNumber) || empty($expiryDate)) {
                    $errors[] = "Row " . ($index + 2) . " is missing required values.";
                    continue;
                }

                // Validate date format
                if (!$this->validateDate($expiryDate)) {
                    $errors[] = "Row " . ($index + 2) . " has an invalid date format for 'expiry_date'. Use YYYY-MM-DD format.";
                    continue;
                }

                // Import driver
                try {
                    $this->licenseService->addLicense(
                        $userId,
                        $data['name'],
                        $data['surname'],
                        $licenseNumber,
                        $expiryDate,
                        $phoneNumber
                    );
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Error in row " . ($index + 2) . ": " . $e->getMessage();
                    $this->logger->error('CSV import error: ' . $e->getMessage(), ['app' => 'driverlicensemgmt']);
                }
            }

            // Generate response message
            $message = "Successfully imported {$imported} driver(s).";
            if (!empty($errors)) {
                // Limit the number of errors to display
                $errorCount = count($errors);
                $displayErrors = array_slice($errors, 0, 5);
                $message .= " Encountered {$errorCount} error(s): " . implode(' ', $displayErrors);
                
                if ($errorCount > 5) {
                    $message .= " and " . ($errorCount - 5) . " more.";
                }
                
                // Log all errors
                $this->logger->warning('CSV import completed with errors: ' . implode("\n", $errors), 
                    ['app' => 'driverlicensemgmt']);
            }

            return new JSONResponse([
                'success' => true,
                'message' => $message,
                'imported' => $imported,
                'errors' => $errors,
                'total_errors' => count($errors)
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('CSV import exception: ' . $e->getMessage(), 
                ['app' => 'driverlicensemgmt']);
                
            return new JSONResponse([
                'success' => false,
                'message' => 'An unexpected error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate date format (YYYY-MM-DD)
     * 
     * @param string $date
     * @return bool
     */
    private function validateDate($date) {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $dateTime = \DateTime::createFromFormat('Y-m-d', $date);
            return $dateTime && $dateTime->format('Y-m-d') === $date;
        }
        return false;
    }
}