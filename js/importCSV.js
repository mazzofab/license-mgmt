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

        // Parse CSV string into an array of objects
        function parseCSV(text) {
            const lines = text.split(/\r\n|\n/);
            const result = [];
            
            // Skip empty lines
            const filteredLines = lines.filter(line => line.trim().length > 0);
            if (filteredLines.length === 0) {
                throw new Error('The CSV file is empty.');
            }
            
            // Parse headers
            const headers = parseCSVLine(filteredLines[0]);
            
            // Validate required headers
            const requiredHeaders = ['name', 'surname', 'license_number', 'expiry_date'];
            const missingHeaders = requiredHeaders.filter(header => !headers.includes(header));
            
            if (missingHeaders.length > 0) {
                throw new Error('Missing required columns: ' + missingHeaders.join(', '));
            }
            
            // Parse data rows
            for (let i = 1; i < filteredLines.length; i++) {
                const values = parseCSVLine(filteredLines[i]);
                if (values.length !== headers.length) {
                    continue; // Skip malformed rows
                }
                
                const row = {};
                headers.forEach((header, index) => {
                    row[header] = values[index];
                });
                result.push(row);
            }
            
            return { headers, rows: result };
        }
        
        // Parse a single CSV line, handling quoted values
        function parseCSVLine(line) {
            const result = [];
            let currentValue = '';
            let inQuotes = false;
            
            for (let i = 0; i < line.length; i++) {
                const char = line[i];
                
                if (char === '"') {
                    // Toggle quote mode
                    inQuotes = !inQuotes;
                } else if (char === ',' && !inQuotes) {
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
    });
})();