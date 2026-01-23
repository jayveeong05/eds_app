<?php
require_once 'includes/auth_check.php';
$pageTitle = 'Invoice Management';
$currentPage = 'invoices';
?>
<?php include 'includes/header.php'; ?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-receipt-cutoff"></i> Bulk Invoice Upload</h2>
        <p class="text-muted">Upload invoices in bulk (supports thousands of files)</p>
    </div>
</div>

<!-- Upload Status Card -->
<div class="card mb-4">
    <div class="card-header bg-eds-primary text-white">
        <h5 class="mb-0"><i class="bi bi-cloud-upload"></i> Upload Process</h5>
    </div>
    <div class="card-body">
        
        <!-- Step 1: File Selection -->
        <div class="mb-4">
            <h6 class="text-eds-primary"><strong>Step 1:</strong> Select Invoice Files</h6>
            <input type="file" class="form-control" id="invoiceFiles" multiple accept=".pdf">
            <small class="text-muted">
                Expected format: <code>AA001001-Jan.pdf</code>, <code>TOG002020-Dec.pdf</code>, <code>3I001003-Dec.pdf</code>, etc.<br>
                <strong>Note:</strong> New uploads replace old data for the same code+month (max 12 records per machine)
            </small>
            <div id="fileCount" class="mt-2 text-muted"></div>
        </div>
        
        <!-- Step 2: Upload to S3 -->
        <div class="mb-4">
            <h6 class="text-eds-primary"><strong>Step 2:</strong> Upload to S3</h6>
            <button class="btn btn-eds-primary" id="uploadBtn" onclick="uploadToS3()" disabled>
                <i class="bi bi-upload"></i> Upload Files to S3
            </button>
            <div id="uploadProgress" class="mt-3" style="display:none;">
                <div class="d-flex justify-content-between mb-2">
                    <span>Progress: <strong id="uploadCount">0</strong> / <strong id="totalCount">0</strong></span>
                    <span><strong id="uploadPercent">0%</strong></span>
                </div>
                <div class="progress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-eds-primary" 
                         id="progressBar" role="progressbar" style="width: 0%"></div>
                </div>
            </div>
        </div>
        
        <!-- Step 3: Parse and Save -->
        <div class="mb-4">
            <h6 class="text-eds-primary"><strong>Step 3:</strong> Parse & Save to Database</h6>
            <button class="btn btn-success" id="saveBtn" onclick="parseAndSave()" disabled>
                <i class="bi bi-database-add"></i> Parse Filenames & Save to DB
            </button>
            <div id="saveResult" class="mt-3"></div>
        </div>
        
    </div>
</div>

<!-- Preview Card -->
<div class="card" id="previewCard" style="display:none;">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-eye"></i> Upload Preview</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>Filename</th>
                        <th>Code</th>
                        <th>Month</th>
                        <th>Year</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="previewTableBody"></tbody>
            </table>
        </div>
    </div>
</div>

<script>
let uploadedKeys = [];

// Month abbreviation mapping (must match InvoiceParser.php)
const VALID_MONTHS = {
    'Jan': 'January', 'Feb': 'February', 'Mar': 'March',
    'Apr': 'April', 'May': 'May', 'Jun': 'June',
    'Jul': 'July', 'Aug': 'August', 'Sep': 'September',
    'Oct': 'October', 'Nov': 'November', 'Dec': 'December'
};

// Validate invoice filename format (matches InvoiceParser.php logic)
function validateInvoiceFilename(filename) {
    // Remove .pdf extension
    const name = filename.replace(/\.pdf$/i, '');
    
    // Split by hyphen
    const parts = name.split('-');
    
    if (parts.length !== 2) {
        return { valid: false, reason: 'Must have format: CODE-MONTH.pdf' };
    }
    
    const code = parts[0].trim();
    const monthAbbr = parts[1].trim();
    
    // Validate code format: AA001001, TOG002020, 3I001003 (1-3 alphanumeric + 6 digits)
    if (!/^[A-Z0-9]{1,3}[0-9]{6}$/.test(code)) {
        return { valid: false, reason: 'Code must be 1-3 alphanumeric characters (A-Z, 0-9) + 6 digits (e.g., AA001001, TOG002020, 3I001003)' };
    }
    
    // Validate month abbreviation
    if (!VALID_MONTHS[monthAbbr]) {
        return { valid: false, reason: 'Month must be 3-letter abbreviation (Jan, Feb, Mar, etc.)' };
    }
    
    return { valid: true };
}

// File selection handler with validation
document.getElementById('invoiceFiles').addEventListener('change', function(e) {
    const files = Array.from(e.target.files);
    let validCount = 0;
    let invalidFiles = [];
    
    files.forEach(file => {
        const validation = validateInvoiceFilename(file.name);
        if (validation.valid) {
            validCount++;
        } else {
            invalidFiles.push({ name: file.name, reason: validation.reason });
        }
    });
    
    let message = `<strong>${validCount}</strong> valid files, <strong>${invalidFiles.length}</strong> invalid files`;
    
    if (invalidFiles.length > 0) {
        message += '<div class="alert alert-warning mt-2 mb-0"><strong>Invalid files (will not be uploaded):</strong><ul class="mb-0 mt-1">';
        invalidFiles.forEach(f => {
            message += `<li><code>${f.name}</code>: ${f.reason}</li>`;
        });
        message += '</ul></div>';
    }
    
    document.getElementById('fileCount').innerHTML = message;
    document.getElementById('uploadBtn').disabled = validCount === 0;
});

// Upload to S3 (only valid files)
async function uploadToS3() {
    const files = Array.from(document.getElementById('invoiceFiles').files);
    
    // Filter to only valid files
    const validFiles = files.filter(file => validateInvoiceFilename(file.name).valid);
    const totalCount = validFiles.length;
    
    if (totalCount === 0) {
        showToast('No valid files to upload', 'warning');
        return;
    }
    
    // Disable buttons during upload
    document.getElementById('uploadBtn').disabled = true;
    document.getElementById('saveBtn').disabled = true;
    
    // Show progress
    document.getElementById('uploadProgress').style.display = 'block';
    document.getElementById('totalCount').textContent = totalCount;
    
    uploadedKeys = [];
    let uploadCount = 0;
    let failedCount = 0;
    
    for (const file of validFiles) {
        try {
            // Upload with original filename
            const s3Key = await uploadFile(file, 'invoices', file.name);
            
            if (s3Key) {
                uploadedKeys.push(s3Key);
                uploadCount++;
            } else {
                failedCount++;
            }
            
            // Update progress
            document.getElementById('uploadCount').textContent = uploadCount;
            const percent = Math.round((uploadCount / totalCount) * 100);
            document.getElementById('uploadPercent').textContent = percent + '%';
            document.getElementById('progressBar').style.width = percent + '%';
            
        } catch (error) {
            console.error('Upload error:', error);
            failedCount++;
        }
    }
    
    // Upload complete
    if (uploadCount > 0) {
        showToast(`Uploaded ${uploadCount} of ${totalCount} files to S3`, 'success');
        document.getElementById('saveBtn').disabled = false;
    }
    
    if (failedCount > 0) {
        showToast(`${failedCount} files failed to upload`, 'warning');
    }
}

// Parse and save to database
async function parseAndSave() {
    if (uploadedKeys.length === 0) {
        showToast('No files to save', 'warning');
        return;
    }
    
    const totalFiles = uploadedKeys.length;
    document.getElementById('saveBtn').disabled = true;
    
    // Show progress indicator
    const progressHtml = `
        <div class="alert alert-info">
            <div class="d-flex align-items-center mb-2">
                <div class="spinner-border spinner-border-sm text-primary me-3" role="status">
                    <span class="visually-hidden">Processing...</span>
                </div>
                <div class="flex-grow-1">
                    <h6 class="alert-heading mb-1">Processing Database Save...</h6>
                    <p class="mb-2">Parsing and saving <strong>${totalFiles}</strong> invoice files to database</p>
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" 
                             role="progressbar" 
                             style="width: 100%" 
                             aria-valuenow="100" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            <span class="fw-bold">Processing ${totalFiles} files...</span>
                        </div>
                    </div>
                    <small class="text-muted mt-1 d-block">
                        <i class="bi bi-info-circle"></i> This may take a moment for large batches. Please do not close this page.
                    </small>
                </div>
            </div>
        </div>
    `;
    document.getElementById('saveResult').innerHTML = progressHtml;
    
    // Scroll to show progress
    document.getElementById('saveResult').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    
    try {
        const data = await apiRequest(ADMIN_API_BASE + '/bulk_save_invoices.php', {
            body: { s3Keys: uploadedKeys }
        });
        
        if (data.success) {
            // Determine alert type based on failures
            const hasFailures = data.failedFiles && data.failedFiles.length > 0;
            const alertType = hasFailures ? 'alert-warning' : 'alert-success';
            const alertIcon = hasFailures ? '⚠️' : '✅';
            
            // Show results - match backend response fields
            let resultHtml = `
                <div class="alert ${alertType}">
                    <h6 class="alert-heading">${alertIcon} Database Save Complete</h6>
                    <ul class="mb-0">
                        <li><strong>${data.processed || 0}</strong> invoices processed successfully</li>
                        <li><strong>${data.failed || 0}</strong> files failed</li>
                    </ul>
                    ${data.message ? `<p class="mb-0 mt-2"><em>${data.message}</em></p>` : ''}
                </div>
            `;
            
            // Show failed files if any - make it prominent
            if (hasFailures) {
                resultHtml += showFailedFiles(data.failedFiles);
            }
            
            document.getElementById('saveResult').innerHTML = resultHtml;
            
            // Scroll to result if there are failures
            if (hasFailures) {
                document.getElementById('saveResult').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
            
            // Clear file input
            document.getElementById('invoiceFiles').value = '';
            document.getElementById('fileCount').innerHTML = '';
            document.getElementById('uploadBtn').disabled = true;
            uploadedKeys = [];
            
            showToast('Invoices saved to database!', 'success');
        } else {
            showToast('Failed to save: ' + (data.message || 'Unknown error'), 'danger');
        }
    } catch (error) {
        console.error('Save error:', error);
        // Show error in result area
        document.getElementById('saveResult').innerHTML = `
            <div class="alert alert-danger">
                <h6 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Save Failed</h6>
                <p class="mb-0">${escapeHtml(error.message || 'Unknown error occurred')}</p>
            </div>
        `;
        showToast('Failed to save: ' + error.message, 'danger');
    } finally {
        document.getElementById('saveBtn').disabled = false;
    }
}


// Show failed files - returns HTML string
function showFailedFiles(failedFiles) {
    if (!failedFiles || failedFiles.length === 0) {
        return '';
    }
    
    let html = `
        <div class="alert alert-danger mt-3">
            <h6 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Failed Files (${failedFiles.length}):</h6>
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th style="width: 50%;">Filename</th>
                            <th style="width: 50%;">Reason</th>
                        </tr>
                    </thead>
                    <tbody>
    `;
    
    failedFiles.forEach((file, index) => {
        html += `<tr>
            <td><code>${escapeHtml(file.filename || 'Unknown')}</code></td>
            <td>${escapeHtml(file.reason || 'Unknown error')}</td>
        </tr>`;
    });
    
    html += `
                    </tbody>
                </table>
            </div>
        </div>
    `;
    
    return html;
}

// Helper: Escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Upload single file with custom filename
async function uploadFile(file, folder, customFilename) {
    console.log('Uploading:', customFilename); // DEBUG: Check what filename is being sent
    
    const formData = new FormData();
    formData.append('file', file);
    formData.append('folder', folder);
    formData.append('custom_filename', customFilename); // Preserve original name
    
    console.log('FormData custom_filename:', formData.get('custom_filename')); // DEBUG
    
    try {
        const response = await fetch(API_BASE + '/upload.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        console.log('Upload response:', data); // DEBUG: Check server response
        
        if (response.ok && data.url) {
            return data.url; // Returns S3 key
        }
        
        return null;
    } catch (error) {
        console.error('Upload error:', error);
        return null;
    }
}
</script>

<?php include 'includes/footer.php'; ?>
