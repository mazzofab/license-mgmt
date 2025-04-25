/* global OC */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('csvUploadForm');
        if (!form) return;

        // Add CSV preview elements
        const previewContainer = document.createElement('div');
        previewContainer.className = 'csv-preview-container';
        
        // Create preview panel HTML structure
        const previewHTML = `
            <div class="panel csv-preview" style="display: none; margin-top: 15px;">
                <div class="panel-header">
                    <h3>CSV Preview</h3>
                </div>
                <div class="panel-body">
                    <div class="csv-preview-content">
                        <table class="csv-preview-table">
                            <thead></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="preview-validation">
                        <div class="preview-validation-message" style="margin-bottom: 10px; display: none;"></div>
                    </div>
                    <div class="preview-actions">
                        <button type="button" class="button primary" id="confirmImport">Confirm Import</button>
                        <button type="button" class="button" id="cancelImport">Cancel</button>
                    </div>
                </div>
            </div>
        `;
        
        previewContainer.innerHTML = previewHTML;
        form.parentNode.insertBefore(previewContainer, form.nextSibling);

        const previewPanel = document.querySelector('.csv-preview');
        const previewTable = document.querySelector('.csv-preview-table');
        const validationMsg = document.querySelector('.preview-validation-message');
        const confirmBtn = document.getElementById('confirmImport');
        const cancelBtn = document.getElementById('cancelImport');
        let csvData = null;

        // File input change handler to show preview
        const fileInput = document.getElementById('csvFile');
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                if (!this.files.length) return;
                
                const file = this.files[0];
                
                // Validate file type
                if (!file.name.toLowerCase().endsWith('.csv') && file.type !== 'text/csv') {
                    showNotification('error', 'Invalid file type. Please select a CSV file.');
                    this.value = '';
                    return;
                }

                // Validate file size (max 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    showNotification('error', 'File is too large. Maximum size is 2MB.');
                    this.value = '';
                    return;
                }

                // Read and preview the CSV
                const reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        const content = e.target.result;
                        csvData = parseCSV(content);
                        displayPreview(csvData);
                        validateCSVData(csvData);
                        showNotification('info', 'Please review the CSV data before importing.');
                    } catch (error) {
                        showNotification('error', 'Error parsing CSV: ' + error.message);
                    }
                };
                reader.onerror = function() {
                    showNotification('error', 'Failed to read the file.');
                };
                reader.readAsText(file);
            });
        }

        // Upload CSV function
        function uploadCSV(file) {
            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Uploading...';
            submitBtn.disabled = true;
            
            const formData = new FormData();
            formData.append('csvFile', file);

            // Use the proper URL from OC.generateUrl
            const url = OC.generateUrl('/apps/driverlicensemgmt/import-csv');
            
            fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'requesttoken': OC.requestToken
                }
            })
            .then(function(res) {
                if (!res.ok) {
                    throw new Error('Server responded with status: ' + res.status);
                }
                return res.json();
            })
            .then(function(data) {
                if (data.success) {
                    showNotification('success', data.message || "Import completed successfully.");
                    
                    // Reset the form and preview
                    fileInput.value = '';
                    previewPanel.style.display = 'none';
                    validationMsg.style.display = 'none';
                    csvData = null;
                    
                    // Reload after a short delay to show the notification
                    setTimeout(function() { 
                        window.location.reload(); 
                    }, 2000);
                } else {
                    showNotification('error', data.message || "Import failed.");
                }
            })
            .catch(function(err) {
                console.error(err);
                showNotification('error', "Upload failed: " + err.message);
            })
            .finally(function() {
                // Reset button state
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        }

        // Use Nextcloud's notification system instead of alerts
        function showNotification(type, message) {
            if (type === 'success') {
                OC.Notification.showTemporary(message);
            } else if (type === 'error') {
                OC.Notification.showTemporary(message);
            } else {
                OC.Notification.showTemporary(message);
            }
        }

        // Parse CSV string into an array of objects
        function parseCSV(text) {
            // Try to detect the delimiter
            let delimiter = ',';
            if (text.indexOf(';') !== -1) {
                // If semicolons are present, check if they're more common than commas
                const commaCount = (text.match(/,/g) || []).length;
                const semicolonCount = (text.match(/;/g) || []).length;
                
                if (semicolonCount > commaCount) {
                    delimiter = ';';
                }
            }
            
            const lines = text.split(/\r\n|\n/);
            const result = [];
            
            // Skip empty lines
            const filteredLines = lines.filter(line => line.trim().length > 0);
            if (filteredLines.length === 0) {
                throw new Error('The CSV file is empty.');
            }
            
            // Parse headers
            const headers = parseCSVLine(filteredLines[0], delimiter);
            
            // Map headers to standard names
            const headerMap = {
                name: ['name', 'first name', 'firstname', 'first_name'],
                surname: ['surname', 'last name', 'lastname', 'last_name'],
                license_number: ['license number', 'license_number', 'licensenumber', 'license', 'license no', 'license_no', 'license id', 'license_id'],
                expiry_date: ['expiry date', 'expiry_date', 'expirydate', 'expiry', 'expire date', 'expire_date', 'expires', 'expiration date', 'expiration_date']
            };
            
            // Normalize headers
            const normalizedHeaders = headers.map(header => {
                const headerLower = header.toLowerCase().trim();
                
                for (const [standardName, variations] of Object.entries(headerMap)) {
                    if (variations.includes(headerLower)) {
                        return standardName;
                    }
                }
                
                return header; // Keep original if no match
            });
            
            // Validate required headers
            const requiredHeaders = ['name', 'surname', 'license_number', 'expiry_date'];
            const missingHeaders = requiredHeaders.filter(header => !normalizedHeaders.includes(header));
            
            if (missingHeaders.length > 0) {
                throw new Error('Missing required columns: ' + missingHeaders.join(', '));
            }
            
            // Parse data rows
            for (let i = 1; i < filteredLines.length; i++) {
                const values = parseCSVLine(filteredLines[i], delimiter);
                if (values.length !== normalizedHeaders.length) {
                    // Skip rows that don't match header count
                    continue;
                }
                
                const row = {};
                normalizedHeaders.forEach((header, index) => {
                    row[header] = values[index];
                });
                result.push(row);
            }
            
            return { headers: normalizedHeaders, rows: result, originalHeaders: headers };
        }
        
        // Parse a single CSV line, handling quoted values
        function parseCSVLine(line, delimiter = ',') {
            const result = [];
            let currentValue = '';
            let inQuotes = false;
            
            for (let i = 0; i < line.length; i++) {
                const char = line[i];
                
                if (char === '"') {
                    // Toggle quote mode
                    inQuotes = !inQuotes;
                } else if (char === delimiter && !inQuotes) {
                    // End of current value
                    result.push(currentValue.trim());
                    currentValue = '';
                } else {
                    // Add character to current value
                    currentValue += char;
                }
            }
            
            // Add the last value
            result.push(currentValue.trim());
            
            return result;
        }

        // Validate CSV data and show warnings
        function validateCSVData(data) {
            const warnings = [];
            
            // Check if we have all required columns
            const requiredColumns = ['name', 'surname', 'license_number', 'expiry_date'];
            const missingColumns = [];
            
            requiredColumns.forEach(column => {
                if (!data.headers.includes(column)) {
                    missingColumns.push(column);
                }
            });
            
            if (missingColumns.length > 0) {
                warnings.push(`Missing required columns: ${missingColumns.join(', ')}`);
            }
            
            // Check for valid expiry dates
            const dateRegex = /^\d{4}-\d{2}-\d{2}$/; // YYYY-MM-DD format
            const altDateRegex1 = /^\d{2}\/\d{2}\/\d{4}$/; // MM/DD/YYYY or DD/MM/YYYY format
            const altDateRegex2 = /^\d{2}-\d{2}-\d{4}$/; // MM-DD-YYYY or DD-MM-YYYY format
            
            const expiryDateIndex = data.headers.indexOf('expiry_date');
            if (expiryDateIndex !== -1) {
                let validDateCount = 0;
                
                data.rows.forEach((row, index) => {
                    const expiryDate = row.expiry_date;
                    if (!dateRegex.test(expiryDate) && !altDateRegex1.test(expiryDate) && !altDateRegex2.test(expiryDate)) {
                        // Don't add individual warnings to avoid flooding
                        if (validDateCount === 0) {
                            warnings.push(`Some expiry dates are not in a recognized format (YYYY-MM-DD, MM/DD/YYYY, or DD/MM/YYYY).`);
                        }
                    } else {
                        validDateCount++;
                    }
                });
                
                if (validDateCount < data.rows.length && validDateCount > 0) {
                    warnings.push(`${validDateCount} out of ${data.rows.length} dates are in a valid format.`);
                }
            }
            
            // Display warnings if any
            if (warnings.length > 0) {
                validationMsg.style.display = 'block';
                validationMsg.style.color = '#e67e22'; // Warning color
                validationMsg.innerHTML = '<strong>Warning:</strong> ' + warnings.join('<br>');
            } else {
                validationMsg.style.display = 'none';
            }
        }

        // Display CSV preview in table
        function displayPreview(data) {
            // Clear previous preview
            const theadElement = previewTable.querySelector('thead');
            const tbodyElement = previewTable.querySelector('tbody');
            
            theadElement.innerHTML = '';
            tbodyElement.innerHTML = '';
            
            // Display headers
            const headerRow = document.createElement('tr');
            data.headers.forEach(header => {
                const th = document.createElement('th');
                th.textContent = header;
                headerRow.appendChild(th);
            });
            theadElement.appendChild(headerRow);
            
            // Display rows (max 10 for preview)
            const maxRowsToShow = Math.min(data.rows.length, 10);
            for (let i = 0; i < maxRowsToShow; i++) {
                const row = data.rows[i];
                const tr = document.createElement('tr');
                
                data.headers.forEach(header => {
                    const td = document.createElement('td');
                    td.textContent = row[header] || '';
                    
                    // Highlight invalid date formats
                    if (header === 'expiry_date') {
                        const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
                        const altDateRegex1 = /^\d{2}\/\d{2}\/\d{4}$/;
                        const altDateRegex2 = /^\d{2}-\d{2}-\d{4}$/;
                        
                        if (!dateRegex.test(row[header]) && !altDateRegex1.test(row[header]) && !altDateRegex2.test(row[header])) {
                            td.style.color = 'red';
                            td.title = 'Invalid date format';
                        }
                    }
                    
                    tr.appendChild(td);
                });
                
                tbodyElement.appendChild(tr);
            }
            
            // Show preview panel
            previewPanel.style.display = 'block';
            
            // Show message if not all rows are displayed
            if (data.rows.length > maxRowsToShow) {
                const messageRow = document.createElement('tr');
                const messageCell = document.createElement('td');
                messageCell.colSpan = data.headers.length;
                messageCell.textContent = `... and ${data.rows.length - maxRowsToShow} more rows`;
                messageCell.style.textAlign = 'center';
                messageCell.style.fontStyle = 'italic';
                messageRow.appendChild(messageCell);
                tbodyElement.appendChild(messageRow);
            }
        }

        // Preview confirmation action
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function() {
                if (!csvData || csvData.rows.length === 0) {
                    showNotification('error', 'No valid data to import.');
                    return;
                }
                
                uploadCSV(fileInput.files[0]);
            });
        }
        
        // Preview cancel action
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function() {
                previewPanel.style.display = 'none';
                validationMsg.style.display = 'none';
                fileInput.value = '';
                csvData = null;
            });
        }

        // Handle form submission
        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                
                if (!fileInput.files.length) {
                    showNotification('error', 'Please choose a CSV file.');
                    return;
                }
                
                // If we haven't previewed the file yet, trigger the change event
                if (!csvData) {
                    const event = new Event('change');
                    fileInput.dispatchEvent(event);
                    return;
                }
                
                uploadCSV(fileInput.files[0]);
            });
        }