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

            // Parse CSV - Try different delimiters
            $delimiter = ','; // Default delimiter
            
            // Check if semicolon is used as delimiter
            if (strpos($csvData, ';') !== false) {
                $delimiter = ';';
            }
            
            // Parse the CSV with the determined delimiter
            $rows = [];
            $lines = explode("\n", trim($csvData));
            foreach ($lines as $line) {
                if (trim($line) !== '') {
                    $rows[] = str_getcsv($line, $delimiter);
                }
            }
            
            // Debug log the parsed CSV data
            $this->logger->debug('CSV parsed data: ' . json_encode($rows), ['app' => 'driverlicensemgmt']);
            
            if (count($rows) < 2) { // At least header + 1 data row
                return new JSONResponse(
                    ['success' => false, 'message' => 'The CSV file must contain at least one data row.'], 
                    400
                );
            }

            // Get and validate header
            $header = array_map('trim', array_shift($rows));
            $this->logger->debug('CSV headers: ' . json_encode($header), ['app' => 'driverlicensemgmt']);
            
            // Map expected headers to possible variations
            $headerMap = [
                'name' => ['name', 'first_name', 'firstname'],
                'surname' => ['surname', 'last_name', 'lastname'],
                'license_number' => ['license_number', 'licensenumber', 'license', 'license_no', 'license_id'],
                'expiry_date' => ['expiry_date', 'expirydate', 'expiry', 'expire_date', 'expires', 'expiration_date', 'expiration'],
                'phone' => ['phone', 'phone_number', 'phonenumber', 'telephone', 'tel', 'mobile']
            ];
            
            // Create an index mapping for each field
            $fieldIndexes = [];
            foreach ($headerMap as $field => $possibleNames) {
                $fieldIndexes[$field] = -1;
                foreach ($possibleNames as $possibleName) {
                    $index = array_search(strtolower($possibleName), array_map('strtolower', $header));
                    if ($index !== false) {
                        $fieldIndexes[$field] = $index;
                        break;
                    }
                }
            }
            
            // Debug the field mappings
            $this->logger->debug('Field indexes: ' . json_encode($fieldIndexes), ['app' => 'driverlicensemgmt']);
            
            // Check if all required fields were found
            $missingFields = [];
            foreach (['name', 'surname', 'license_number', 'expiry_date'] as $requiredField) {
                if ($fieldIndexes[$requiredField] === -1) {
                    $missingFields[] = $requiredField;
                }
            }
            
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
                if (count($row) < max(array_values($fieldIndexes)) + 1) {
                    $errors[] = "Row " . ($index + 2) . " has an invalid number of columns.";
                    continue;
                }

                // Get data from the row using the field indexes
                $name = $row[$fieldIndexes['name']] ?? '';
                $surname = $row[$fieldIndexes['surname']] ?? '';
                $licenseNumber = $row[$fieldIndexes['license_number']] ?? '';
                $expiryDate = $row[$fieldIndexes['expiry_date']] ?? '';
                $phone = $fieldIndexes['phone'] !== -1 ? ($row[$fieldIndexes['phone']] ?? '') : '';
                
                // Debug the values for this row
                $this->logger->debug('Row ' . ($index + 2) . ' values: ', [
                    'name' => $name,
                    'surname' => $surname,
                    'licenseNumber' => $licenseNumber,
                    'expiryDate' => $expiryDate,
                    'phone' => $phone,
                    'app' => 'driverlicensemgmt'
                ]);
                
                // Validate required fields
                if (empty($name) || empty($surname) || empty($licenseNumber) || empty($expiryDate)) {
                    $errors[] = "Row " . ($index + 2) . " is missing required values.";
                    continue;
                }

                // Parse and format date - handle different date formats
                $parsedDate = $this->parseDate($expiryDate);
                if (!$parsedDate) {
                    $errors[] = "Row " . ($index + 2) . " has an invalid date format for 'expiry_date'. Use YYYY-MM-DD format.";
                    continue;
                }
                
                // Use the formatted date for import
                $formattedDate = $parsedDate->format('Y-m-d');
                
                // Log the data we're about to import
                $this->logger->debug('Importing driver: ' . json_encode([
                    'name' => $name,
                    'surname' => $surname,
                    'licenseNumber' => $licenseNumber,
                    'expiryDate' => $formattedDate,
                    'phone' => $phone
                ]), ['app' => 'driverlicensemgmt']);

                // Import driver
                try {
                    $this->licenseService->addLicense(
                        $userId,
                        $name,
                        $surname,
                        $licenseNumber,
                        $formattedDate,
                        $phone
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
     * Parse date in multiple formats
     * 
     * @param string $date
     * @return \DateTime|false
     */
    private function parseDate($date) {
        // Try to parse the date in YYYY-MM-DD format
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $dateTime = \DateTime::createFromFormat('Y-m-d', $date);
            if ($dateTime && $dateTime->format('Y-m-d') === $date) {
                return $dateTime;
            }
        }
        
        // Try other common date formats
        $formats = [
            'd/m/Y', // 31/12/2023
            'm/d/Y', // 12/31/2023
            'd-m-Y', // 31-12-2023
            'm-d-Y', // 12-31-2023
            'd.m.Y', // 31.12.2023
            'Y.m.d'  // 2023.12.31
        ];
        
        foreach ($formats as $format) {
            $dateTime = \DateTime::createFromFormat($format, $date);
            if ($dateTime !== false) {
                return $dateTime;
            }
        }
        
        return false;
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