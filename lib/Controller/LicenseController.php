<?php

namespace OCA\DriverLicenseMgmt\Controller;

use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\Files\Node;
use OCP\ILogger;

use OCA\DriverLicenseMgmt\Service\LicenseService;

class LicenseController extends Controller {
    private $licenseService;
    private $logger;

    public function __construct($AppName, IRequest $request, LicenseService $licenseService, ILogger $logger) {
        parent::__construct($AppName, $request);
        $this->licenseService = $licenseService;
        $this->logger = $logger;
    }

    /**
     * Import drivers from a CSV file
     * 
     * @return JSONResponse
     */
    public function importCSV(): JSONResponse {
        try {
            // Validate file upload
            $file = $this->request->getUploadedFile('csvFile');
            if (!$file || !$file->isValid()) {
                return new JSONResponse(
                    ['success' => false, 'message' => 'Invalid file upload. Please try again.'], 
                    400
                );
            }

            // Validate file type
            $mimeType = $file->getType();
            $extension = pathinfo($file->getName(), PATHINFO_EXTENSION);
            
            if ($mimeType !== 'text/csv' && $mimeType !== 'application/vnd.ms-excel' && $extension !== 'csv') {
                return new JSONResponse(
                    ['success' => false, 'message' => 'Invalid file type. Please upload a CSV file.'], 
                    400
                );
            }

            // Validate file size (2MB max)
            if ($file->getSize() > 2 * 1024 * 1024) {
                return new JSONResponse(
                    ['success' => false, 'message' => 'File size exceeds the maximum limit of 2MB.'], 
                    400
                );
            }

            // Read CSV data
            $csvData = file_get_contents($file->getRealPath());
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
                if (empty(implode('', $row))) {
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

                // Validate required fields
                $hasEmptyRequired = false;
                foreach ($requiredFields as $field) {
                    if (empty($data[$field])) {
                        $errors[] = "Row " . ($index + 2) . " is missing required value for '{$field}'.";
                        $hasEmptyRequired = true;
                        break;
                    }
                }
                if ($hasEmptyRequired) continue;

                // Validate date format
                if (!$this->validateDate($data['expiry_date'])) {
                    $errors[] = "Row " . ($index + 2) . " has an invalid date format for 'expiry_date'. Use YYYY-MM-DD format.";
                    continue;
                }

                // Import driver
                try {
                    $this->licenseService->addLicense(
                        $data['name'],
                        $data['surname'],
                        $data['license_number'],
                        $data['expiry_date'],
                        $data['phone'] ?? ''
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
                ['app' => 'driverlicensemgmt', 'trace' => $e->getTraceAsString()]);
                
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