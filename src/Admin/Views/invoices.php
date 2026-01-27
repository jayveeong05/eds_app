<?php
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle = 'Invoice Management';
$currentPage = 'invoices';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

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
                Expected format: <code>AA001001-Jan-2025-001.pdf</code>, <code>TOG002020-Dec-2025-002.pdf</code>, <code>3I001003-Dec-2024-IV001.pdf</code>, etc.<br>
                <strong>Note:</strong> Files with the same filename (code+month+year+invoice_number) will replace existing invoices. Multiple invoices per code+month+year are supported with different invoice numbers.
            </small>
            <div id="fileCount" class="mt-2 text-muted"></div>
        </div>
        
        <!-- Step 2: Upload to S3 -->
        <div class="mb-4">
            <h6 class="text-eds-primary"><strong>Step 2:</strong> Upload to S3</h6>
            <div class="d-flex gap-2 mb-2">
                <button class="btn btn-eds-primary" id="uploadBtn" onclick="uploadToS3()" disabled>
                    <i class="bi bi-upload"></i> Upload Files to S3
                </button>
                <button class="btn btn-outline-secondary" id="clearUploadsBtn" onclick="clearUploadedFiles()" style="display:none;">
                    <i class="bi bi-x-circle"></i> Clear All Uploads
                </button>
            </div>
            <div id="uploadSummary" class="alert alert-info mb-2" style="display:none;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-info-circle"></i> 
                        <strong id="readyFilesCount">0</strong> files ready for database processing
                        <span id="failedFilesCountText" class="text-danger ms-2" style="display:none;">
                            (<span id="failedFilesCount">0</span> failed)
                        </span>
                    </div>
                </div>
            </div>
            <div id="uploadProgress" class="mt-3" style="display:none;">
                <!-- Enhanced Progress Header -->
                <div class="card border-primary mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="mb-1">
                                    <i class="bi bi-cloud-upload-fill text-primary"></i> 
                                    Uploading to S3
                                </h6>
                                <small class="text-muted" id="currentFileStatus">Preparing upload...</small>
                            </div>
                            <div class="text-end">
                                <div class="h5 mb-0">
                                    <strong id="uploadPercent">0%</strong>
                                </div>
                                <small class="text-muted">
                                    <span id="uploadCount">0</span> / <span id="totalCount">0</span> files
                                </small>
                            </div>
                        </div>
                        
                        <!-- Enhanced Progress Bar -->
                        <div class="progress mb-2" style="height: 30px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-eds-primary" 
                                 id="progressBar" role="progressbar" 
                                 style="width: 0%; transition: width 0.3s ease;">
                                <span class="fw-bold text-white" id="progressBarText">0%</span>
                            </div>
                        </div>
                        
                        <!-- Stats Row -->
                        <div class="row g-2 mt-2">
                            <div class="col-md-6">
                                <small class="text-muted d-block">Current File</small>
                                <strong id="currentUploadingFile" class="text-break">-</strong>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block">Upload Speed</small>
                                <strong id="uploadSpeed">-</strong>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block">Est. Time Remaining</small>
                                <strong id="estimatedTime">Calculating...</strong>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- File-by-File Status (Collapsible) -->
                <div class="card">
                    <div class="card-header bg-light" style="cursor: pointer;" onclick="toggleFileList()">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>
                                <i class="bi bi-list-ul"></i> 
                                File Status 
                                <span class="badge bg-primary" id="fileStatusBadge">0</span>
                            </span>
                            <i class="bi bi-chevron-down" id="fileListToggleIcon"></i>
                        </div>
                    </div>
                    <div class="card-body p-0" id="fileListContainer" style="display: none; max-height: 300px; overflow-y: auto;">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="width: 5%;">#</th>
                                    <th style="width: 50%;">Filename</th>
                                    <th style="width: 15%;">Size</th>
                                    <th style="width: 30%;">Status</th>
                                </tr>
                            </thead>
                            <tbody id="fileStatusList">
                                <!-- File status rows will be inserted here -->
                            </tbody>
                        </table>
                    </div>
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
        
        <!-- Enhanced Database Processing Progress (hidden by default) -->
        <div id="dbProgressContainer" style="display: none;">
            <div class="card border-success">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="spinner-border spinner-border-sm text-success me-3" role="status">
                            <span class="visually-hidden">Processing...</span>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">
                                <i class="bi bi-database-fill-check text-success"></i> 
                                Processing Database Save
                            </h6>
                            <small class="text-muted" id="dbStatusText">Parsing filenames and saving to database...</small>
                        </div>
                        <div class="text-end">
                            <div class="h5 mb-0">
                                <strong id="dbProgressPercent">0%</strong>
                            </div>
                            <small class="text-muted">
                                <span id="dbProcessedCount">0</span> / <span id="dbTotalCount">0</span> files
                            </small>
                        </div>
                    </div>
                    
                    <!-- Database Progress Bar -->
                    <div class="progress mb-2" style="height: 30px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                             id="dbProgressBar" role="progressbar" 
                             style="width: 0%; transition: width 0.3s ease;">
                            <span class="fw-bold text-white" id="dbProgressBarText">Initializing...</span>
                        </div>
                    </div>
                    
                    <!-- Database Stats -->
                    <div class="row g-2 mt-2">
                        <div class="col-md-6">
                            <small class="text-muted d-block">Processing Time</small>
                            <strong id="dbElapsedTime">0s</strong>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Est. Time Remaining</small>
                            <strong id="dbEstimatedTime">Calculating...</strong>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3 mb-0">
                        <small>
                            <i class="bi bi-info-circle"></i> 
                            <strong>Please wait:</strong> This process parses filenames, validates data, and saves to the database. 
                            Large batches may take several minutes. Do not close this page.
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<!-- Preview Card -->
<div class="card mb-4" id="previewCard" style="display:none;">
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
                        <th>Invoice #</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="previewTableBody"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Search/Filter Card -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-10">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" id="searchInput" 
                           placeholder="Search by code, month, or year..."
                           onkeypress="if(event.key === 'Enter') loadInvoices(true)">
                </div>
            </div>
            <div class="col-md-2">
                <button class="btn btn-eds-primary w-100" onclick="loadInvoices(true)">
                    <i class="bi bi-search"></i> Search
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Invoice List Card -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-table"></i> All Invoices</h5>
        <span class="badge bg-secondary" id="invoiceCount">0 invoices</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-custom table-hover">
                <thead>
                    <tr>
                        <th>Machine Code</th>
                        <th>Month</th>
                        <th>Year</th>
                        <th>Invoice #</th>
                        <th>Uploaded</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="invoicesTableBody">
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-hourglass-split"></i> Loading invoices...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- Auto-load indicator (shows when loading more) -->
        <div class="text-center mt-3 py-2" id="loadingMoreIndicator" style="display: none;">
            <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <span class="text-muted">Loading more invoices...</span>
        </div>
        <!-- End of list indicator -->
        <div class="text-center mt-3 py-2" id="endOfListIndicator" style="display: none;">
            <small class="text-muted">
                <i class="bi bi-check-circle"></i> All invoices loaded
            </small>
        </div>
    </div>
</div>

<script>
// Track uploaded files by filename to preserve across retries
let uploadedKeys = []; // Array of S3 keys
let uploadedFilesMap = new Map(); // Map<filename, s3Key> to track and prevent duplicates
let allSelectedFiles = []; // Store all selected File objects for retry functionality
let failedFilesList = []; // Store failed files for retry functionality
let allInvoices = [];
let currentOffset = 0;
let currentLimit = 50;
let currentSearch = '';
let totalInvoices = 0;
let isLoadingMore = false;

// Load invoices on page load
document.addEventListener('DOMContentLoaded', function() {
    loadInvoices(true);
    setupInfiniteScroll();
});

// Setup infinite scroll - auto-load when user scrolls near bottom
function setupInfiniteScroll() {
    let scrollTimeout;
    
    window.addEventListener('scroll', function() {
        // Throttle scroll events (check every 200ms)
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(function() {
            checkScrollPosition();
        }, 200);
    }, { passive: true });
}

// Check if user has scrolled near the bottom
function checkScrollPosition() {
    // Don't check if already loading or no more invoices to load
    if (isLoadingMore || allInvoices.length >= totalInvoices) {
        return;
    }
    
    // Calculate scroll position
    const windowHeight = window.innerHeight;
    const documentHeight = document.documentElement.scrollHeight;
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    
    // Trigger load when user is within 300px of bottom
    const threshold = 300;
    const distanceFromBottom = documentHeight - (scrollTop + windowHeight);
    
    if (distanceFromBottom < threshold) {
        loadMoreInvoices();
    }
}

// Load invoices (reset = true to start from beginning, false to append)
async function loadInvoices(reset = false) {
    if (reset) {
        currentOffset = 0;
        allInvoices = [];
        currentSearch = document.getElementById('searchInput').value.trim();
    }
    
    if (isLoadingMore) return; // Prevent multiple simultaneous requests
    isLoadingMore = true;
    
    try {
        const searchInput = document.getElementById('searchInput');
        const searchValue = reset ? currentSearch : currentSearch;
        
        // Show loading state
        if (reset) {
            document.getElementById('invoicesTableBody').innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="bi bi-hourglass-split"></i> Loading invoices...
                    </td>
                </tr>
            `;
            document.getElementById('loadingMoreIndicator').style.display = 'none';
            document.getElementById('endOfListIndicator').style.display = 'none';
        } else {
            // Show loading indicator at bottom when loading more
            document.getElementById('loadingMoreIndicator').style.display = 'block';
            document.getElementById('endOfListIndicator').style.display = 'none';
        }
        
        const data = await apiRequest(ADMIN_API_BASE + '/get_all_invoices.php', {
            body: {
                limit: currentLimit,
                offset: currentOffset,
                search: searchValue
            }
        });
        
        if (data.success) {
            totalInvoices = data.total;
            
            if (reset) {
                allInvoices = data.data;
            } else {
                // Append new invoices
                allInvoices = allInvoices.concat(data.data);
            }
            
            displayInvoices(allInvoices);
            document.getElementById('invoiceCount').textContent = `${allInvoices.length} of ${totalInvoices} invoices`;
            
            // Show/hide loading indicators
            const hasMore = allInvoices.length < totalInvoices;
            document.getElementById('loadingMoreIndicator').style.display = 'none';
            document.getElementById('endOfListIndicator').style.display = hasMore ? 'none' : 'block';
            
            if (hasMore) {
                currentOffset = allInvoices.length;
            }
        }
    } catch (error) {
        document.getElementById('invoicesTableBody').innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-danger py-4">
                    <i class="bi bi-exclamation-triangle"></i> ${error.message}
                </td>
            </tr>
        `;
        document.getElementById('loadingMoreIndicator').style.display = 'none';
        document.getElementById('endOfListIndicator').style.display = 'none';
    } finally {
        isLoadingMore = false;
        // Hide loading indicator
        document.getElementById('loadingMoreIndicator').style.display = 'none';
    }
}

// Load more invoices (append to existing list)
async function loadMoreInvoices() {
    await loadInvoices(false);
}

// Display invoices in table
function displayInvoices(invoices) {
    const tbody = document.getElementById('invoicesTableBody');
    
    if (invoices.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-muted py-4">
                    <i class="bi bi-inbox"></i> No invoices found${currentSearch ? ` for "${currentSearch}"` : ''}
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = invoices.map(invoice => `
        <tr>
            <td>
                <code>${escapeHtml(invoice.code)}</code>
            </td>
            <td>${escapeHtml(invoice.month)}</td>
            <td>${invoice.invoice_year ? escapeHtml(invoice.invoice_year) : '<span class="text-muted">-</span>'}</td>
            <td>${invoice.invoice_number ? escapeHtml(invoice.invoice_number) : '<span class="text-muted">-</span>'}</td>
            <td>${formatDate(invoice.created_at)}</td>
            <td>
                <div class="btn-group" role="group">
                    <button class="btn btn-sm btn-primary" onclick="viewInvoice('${escapeHtml(invoice.file_url)}', '${escapeHtml(invoice.code)}-${escapeHtml(invoice.month)}${invoice.invoice_year ? '-' + invoice.invoice_year : ''}${invoice.invoice_number ? '-' + escapeHtml(invoice.invoice_number) : ''}')" title="View Invoice">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteInvoice('${invoice.id}', '${escapeHtml(invoice.code)}', '${escapeHtml(invoice.month)}')" title="Delete Invoice">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Format date helper - properly handles timezone-less timestamps
function formatDate(dateStr) {
    if (!dateStr) return '-';
    
    // If timestamp doesn't have timezone info, treat it as UTC
    let dateStrToParse = dateStr;
    if (!dateStr.includes('T') && !dateStr.includes('Z') && !dateStr.includes('+') && !dateStr.includes('-', 10)) {
        // Format: "2025-12-19 09:56:40.727965" - treat as UTC
        dateStrToParse = dateStr.replace(' ', 'T') + 'Z';
    } else if (dateStr.includes('T') && !dateStr.includes('Z') && !dateStr.includes('+') && !dateStr.match(/[+-]\d{2}:\d{2}$/)) {
        // Format: "2025-12-19T09:56:40.727965" - add Z for UTC
        dateStrToParse = dateStr + 'Z';
    }
    
    const date = new Date(dateStrToParse);
    
    // Check if date is valid
    if (isNaN(date.getTime())) {
        return dateStr; // Return original if parsing fails
    }
    
    // Format in user's local timezone
    const options = { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric', 
        hour: '2-digit', 
        minute: '2-digit',
        timeZoneName: 'short'
    };
    return date.toLocaleString('en-US', options);
}

// View invoice - opens PDF in new tab
async function viewInvoice(fileUrl, invoiceName) {
    if (!fileUrl) {
        showToast('Invoice file URL not available', 'warning');
        return;
    }
    
    try {
        // Get presigned URL from backend
        const response = await fetch('/api/get_presigned_url.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                's3_key': fileUrl
            })
        });
        
        const data = await response.json();
        
        if (data.success && data.url) {
            // Open PDF in new tab
            window.open(data.url, '_blank');
        } else {
            showToast('Failed to load invoice: ' + (data.message || 'Unknown error'), 'danger');
        }
    } catch (error) {
        showToast('Failed to view invoice: ' + error.message, 'danger');
    }
}

// Delete invoice
async function deleteInvoice(invoiceId, code, month) {
    if (!confirmAction(`Are you sure you want to delete invoice ${code}-${month}? This will permanently delete the invoice and its PDF file from S3.`)) return;
    
    try {
        const data = await apiRequest(ADMIN_API_BASE + '/delete_invoice.php', {
            body: { invoiceId }
        });
        
        if (data.success) {
            showToast(data.message || 'Invoice deleted successfully', 'success');
            loadInvoices(true); // Reload the list from beginning
        }
    } catch (error) {
        showToast('Failed to delete invoice: ' + error.message, 'danger');
    }
}

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
    
    // Must have exactly 4 parts: CODE-MONTH-YEAR-INVOICENUMBER
    if (parts.length !== 4) {
        return { valid: false, reason: 'Must have format: CODE-MONTH-YEAR-INVOICENUMBER.pdf (e.g., AA001001-Jan-2025-001.pdf)' };
    }
    
    const code = parts[0].trim();
    const monthAbbr = parts[1].trim();
    const year = parts[2].trim();
    const invoiceNumber = parts[3].trim();
    
    // Validate code format: AA001001, TOG002020, 3I001003 (1-3 alphanumeric + 6 digits)
    if (!/^[A-Z0-9]{1,3}[0-9]{6}$/.test(code)) {
        return { valid: false, reason: 'Code must be 1-3 alphanumeric characters (A-Z, 0-9) + 6 digits (e.g., AA001001, TOG002020, 3I001003)' };
    }
    
    // Validate month abbreviation
    if (!VALID_MONTHS[monthAbbr]) {
        return { valid: false, reason: 'Month must be 3-letter abbreviation (Jan, Feb, Mar, etc.)' };
    }
    
    // Validate year (4 digits, between 2000-2100)
    if (!/^\d{4}$/.test(year)) {
        return { valid: false, reason: 'Year must be 4 digits (e.g., 2025)' };
    }
    const yearNum = parseInt(year);
    if (yearNum < 2000 || yearNum > 2100) {
        return { valid: false, reason: 'Year must be between 2000 and 2100' };
    }
    
    // Validate invoice number (alphanumeric, at least 1 character)
    if (!invoiceNumber || !/^[A-Z0-9]+$/i.test(invoiceNumber)) {
        return { valid: false, reason: 'Invoice number must be alphanumeric (e.g., 001, IV001, 123)' };
    }
    
    return { valid: true };
}

// File selection handler with validation
document.getElementById('invoiceFiles').addEventListener('change', function(e) {
    const files = Array.from(e.target.files);
    // Store all selected files globally for retry functionality
    allSelectedFiles = files;
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

// Upload to S3 (only valid files) - Enhanced with detailed progress
// Optional parameter: filesToUpload - if provided, use these files instead of reading from input
async function uploadToS3(filesToUpload = null) {
    let files;
    if (filesToUpload && Array.isArray(filesToUpload)) {
        // Use provided files (for retry functionality)
        files = filesToUpload;
    } else {
        // Read from file input (normal flow)
        files = Array.from(document.getElementById('invoiceFiles').files);
    }
    
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
    
    // Show progress container
    const uploadProgress = document.getElementById('uploadProgress');
    uploadProgress.style.display = 'block';
    document.getElementById('totalCount').textContent = totalCount;
    
    // Initialize file status list
    const fileStatusList = document.getElementById('fileStatusList');
    fileStatusList.innerHTML = '';
    
    // Show preserved files (already uploaded) first
    let rowIndex = 0;
    const preservedFiles = [];
    if (uploadedFilesMap.size > 0) {
        uploadedFilesMap.forEach((s3Key, filename) => {
            // Check if this file is in the current batch (will be re-uploaded)
            const isInCurrentBatch = validFiles.some(f => f.name === filename);
            if (!isInCurrentBatch) {
                // Only show preserved files that are NOT being re-uploaded
                preservedFiles.push({ filename, s3Key });
            }
        });
        
        // Display preserved files
        preservedFiles.forEach((preserved, index) => {
            const row = document.createElement('tr');
            row.id = `file-row-preserved-${index}`;
            row.className = 'table-light'; // Different styling for preserved files
            row.innerHTML = `
                <td>${rowIndex + 1}</td>
                <td>
                    <code class="small">${escapeHtml(preserved.filename)}</code>
                    <span class="badge bg-info ms-2" style="font-size: 0.7em;">Preserved</span>
                </td>
                <td>-</td>
                <td>
                    <span class="badge bg-success">
                        <i class="bi bi-check-circle"></i> Already Uploaded
                    </span>
                </td>
            `;
            fileStatusList.appendChild(row);
            rowIndex++;
        });
    }
    
    // Initialize new files as pending (including files being re-uploaded)
    validFiles.forEach((file, index) => {
        const isAlreadyUploaded = uploadedFilesMap.has(file.name);
        const row = document.createElement('tr');
        row.id = `file-row-${rowIndex}`;
        if (isAlreadyUploaded) {
            row.className = 'table-warning'; // Highlight files being re-uploaded
        }
        row.innerHTML = `
            <td>${rowIndex + 1}</td>
            <td>
                <code class="small">${escapeHtml(file.name)}</code>
                ${isAlreadyUploaded ? '<span class="badge bg-warning ms-2" style="font-size: 0.7em;">Re-uploading</span>' : ''}
            </td>
            <td>${formatFileSize(file.size)}</td>
            <td>
                <span class="badge bg-secondary" id="file-status-${rowIndex}">
                    <i class="bi bi-hourglass-split"></i> ${isAlreadyUploaded ? 'Re-uploading...' : 'Pending'}
                </span>
            </td>
        `;
        fileStatusList.appendChild(row);
        rowIndex++;
    });
    
    // Store preserved count for later use
    const preservedCount = preservedFiles.length;
    
    // Time tracking for speed calculation
    const startTime = Date.now();
    let lastUpdateTime = startTime;
    let lastUploadCount = 0;
    let currentFileIndex = 0;
    
    // Update time display
    const updateTimeDisplay = () => {
        // Calculate upload speed (files per second)
        const timeSinceLastUpdate = (Date.now() - lastUpdateTime) / 1000;
        if (timeSinceLastUpdate >= 1 && uploadCount > lastUploadCount) {
            const speed = (uploadCount - lastUploadCount) / timeSinceLastUpdate;
            document.getElementById('uploadSpeed').textContent = speed.toFixed(1) + ' files/s';
            lastUpdateTime = Date.now();
            lastUploadCount = uploadCount;
        }
        
        // Estimate remaining time
        if (uploadCount > 0 && uploadCount < totalCount) {
            const elapsed = (Date.now() - startTime) / 1000;
            const avgTimePerFile = elapsed / uploadCount;
            const remaining = (totalCount - uploadCount) * avgTimePerFile;
            document.getElementById('estimatedTime').textContent = formatTime(remaining);
        } else if (uploadCount === totalCount) {
            document.getElementById('estimatedTime').textContent = 'Complete';
        }
    };
    
    // Start time update interval
    const timeInterval = setInterval(updateTimeDisplay, 100);
    
    // Keep global loading overlay hidden during upload - check periodically
    const keepOverlayHidden = setInterval(() => {
        if (typeof hideLoading === 'function') {
            hideLoading();
        }
        const loadingSpinner = document.getElementById('loadingSpinner');
        if (loadingSpinner) {
            loadingSpinner.classList.remove('show');
            loadingSpinner.style.display = 'none';
        }
    }, 50); // Check every 50ms to ensure it stays hidden
    
    // Don't reset uploadedKeys - preserve previous successful uploads
    // Only track new uploads from this batch
    let uploadCount = 0;
    let failedCount = 0;
    let failedFiles = []; // Track failed files with details
    // Store failed files globally for retry functionality
    failedFilesList = [];
    let newUploadsThisBatch = 0; // Track how many new files were uploaded in this batch
    
    // Calculate starting row index (after preserved files)
    let fileRowIndex = preservedCount;
    
    for (let i = 0; i < validFiles.length; i++) {
        const file = validFiles[i];
        const rowId = `file-row-${fileRowIndex}`;
        const statusId = `file-status-${fileRowIndex}`;
        const isAlreadyUploaded = uploadedFilesMap.has(file.name);
        currentFileIndex = i + 1;
        
        // Ensure global loading overlay stays hidden before each upload
        if (typeof hideLoading === 'function') {
            hideLoading();
        }
        const loadingSpinner = document.getElementById('loadingSpinner');
        if (loadingSpinner) {
            loadingSpinner.classList.remove('show');
            loadingSpinner.style.display = 'none';
        }
        
        // Update current file status
        document.getElementById('currentFileStatus').textContent = `Uploading: ${file.name}`;
        document.getElementById('currentUploadingFile').textContent = file.name;
        document.getElementById('currentUploadingFile').className = 'text-break text-primary';
        
        // Update file row to uploading
        const statusBadge = document.getElementById(statusId);
        if (statusBadge) {
            statusBadge.className = 'badge bg-info';
            statusBadge.innerHTML = '<i class="bi bi-arrow-up-circle"></i> Uploading...';
        }
        
        try {
            // Upload with original filename
            const s3Key = await uploadFile(file, 'invoices', file.name);
            
            // Ensure overlay stays hidden after upload
            if (loadingSpinner) {
                loadingSpinner.classList.remove('show');
                loadingSpinner.style.display = 'none';
            }
            
            if (s3Key) {
                // Check if file already exists (from previous upload)
                if (!uploadedFilesMap.has(file.name)) {
                    uploadedKeys.push(s3Key);
                    uploadedFilesMap.set(file.name, s3Key);
                    newUploadsThisBatch++;
                } else {
                    // File already uploaded previously - update it
                    const existingIndex = uploadedKeys.indexOf(uploadedFilesMap.get(file.name));
                    if (existingIndex !== -1) {
                        uploadedKeys[existingIndex] = s3Key; // Update with new S3 key
                    }
                    uploadedFilesMap.set(file.name, s3Key);
                    newUploadsThisBatch++;
                }
                uploadCount++;
                
                // Update file row to success
                if (statusBadge) {
                    statusBadge.className = 'badge bg-success';
                    statusBadge.innerHTML = '<i class="bi bi-check-circle"></i> Success';
                }
            } else {
                failedCount++;
                failedFiles.push({
                    filename: file.name,
                    reason: 'Upload returned no S3 key (unknown error)'
                });
                
                // Update file row to failed
                if (statusBadge) {
                    statusBadge.className = 'badge bg-danger';
                    statusBadge.innerHTML = '<i class="bi bi-x-circle"></i> Failed';
                }
            }
            
            // Update progress (only count new files being uploaded, not preserved ones)
            const totalReady = uploadedKeys.length;
            const percent = Math.round((uploadCount / totalCount) * 100);
            document.getElementById('uploadCount').textContent = uploadCount;
            document.getElementById('uploadPercent').textContent = percent + '%';
            document.getElementById('progressBar').style.width = percent + '%';
            document.getElementById('progressBarText').textContent = percent + '%';
            document.getElementById('fileStatusBadge').textContent = totalReady;
            
        } catch (error) {
            console.error('Upload error:', error);
            failedCount++;
            const technicalError = error.message || error.toString() || 'Unknown error';
            const userFriendlyError = getUserFriendlyError(technicalError);
            const failedFileInfo = {
                filename: file.name,
                reason: userFriendlyError,
                technicalError: technicalError, // Keep technical error for debugging
                file: file // Store the File object for retry
            };
            failedFiles.push(failedFileInfo);
            failedFilesList.push(failedFileInfo); // Store globally for retry
            
            // Update file row to failed
            if (statusBadge) {
                statusBadge.className = 'badge bg-danger';
                statusBadge.innerHTML = '<i class="bi bi-x-circle"></i> Failed';
            }
        }
        
        // Scroll current file into view
        const currentRow = document.getElementById(rowId);
        if (currentRow) {
            currentRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        
        fileRowIndex++;
    }
    
    // Clear intervals
    clearInterval(timeInterval);
    clearInterval(keepOverlayHidden);
    updateTimeDisplay(); // Final update
    
    // Ensure global loading overlay is hidden after upload completes
    if (typeof hideLoading === 'function') {
        hideLoading();
    }
    const loadingSpinner = document.getElementById('loadingSpinner');
    if (loadingSpinner) {
        loadingSpinner.classList.remove('show');
        loadingSpinner.style.display = 'none';
    }
    
    // Update final status
    document.getElementById('currentFileStatus').textContent = 
        `Upload complete: ${uploadCount} succeeded, ${failedCount} failed`;
    document.getElementById('currentUploadingFile').textContent = 'Upload complete';
    document.getElementById('currentUploadingFile').className = 'text-break text-success';
    
    // Upload complete - show results
    const totalReadyFiles = uploadedKeys.length;
    
    // Update upload summary
    updateUploadSummary(totalReadyFiles, failedCount);
    
    // Show/hide clear button
    if (totalReadyFiles > 0) {
        document.getElementById('clearUploadsBtn').style.display = 'inline-block';
    }
    
    let resultMessage = '';
    if (uploadCount > 0) {
        if (newUploadsThisBatch > 0) {
            showToast(`Uploaded ${uploadCount} of ${totalCount} files to S3. Total ready: ${totalReadyFiles} files`, 'success');
        } else {
            showToast(`All ${totalCount} files were already uploaded. Total ready: ${totalReadyFiles} files`, 'info');
        }
    }
    
    // Disable save button if there are failed files
    if (failedCount > 0) {
        document.getElementById('saveBtn').disabled = true;
        showToast(`${failedCount} files failed to upload. Please re-upload failed files before proceeding.`, 'warning');
        
        // Display failed files in an alert below the progress bar
        let failedFilesHtml = `
            <div class="alert alert-warning mt-3 mb-0">
                <h6 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> ${failedCount} File(s) Failed to Upload</h6>
                <p class="mb-2">The following files could not be uploaded to S3:</p>
                <ul class="mb-0" style="max-height: 200px; overflow-y: auto;">
        `;
        
        failedFiles.forEach(failed => {
            failedFilesHtml += `<li><code>${escapeHtml(failed.filename)}</code>: ${escapeHtml(failed.reason)}</li>`;
        });
        
        failedFilesHtml += `
                </ul>
                <div class="mt-3">
                    <button type="button" class="btn btn-warning" id="retryFailedBtn" onclick="retryFailedFiles()">
                        <i class="bi bi-arrow-clockwise"></i> Retry Failed Files
                    </button>
                </div>
            </div>
        `;
        
        // Insert failed files alert after the progress bar
        const uploadProgress = document.getElementById('uploadProgress');
        // Remove any existing failed files alert
        const existingAlert = uploadProgress.querySelector('.alert-warning');
        if (existingAlert) {
            existingAlert.remove();
        }
        // Append new alert
        uploadProgress.insertAdjacentHTML('beforeend', failedFilesHtml);
        
        // Scroll to show the failed files
        uploadProgress.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    } else {
        // Remove any existing failed files alert if all succeeded
        const uploadProgress = document.getElementById('uploadProgress');
        const existingAlert = uploadProgress.querySelector('.alert-warning');
        if (existingAlert) {
            existingAlert.remove();
        }
        
        // Enable save button when there are no failed files
        if (totalReadyFiles > 0) {
            document.getElementById('saveBtn').disabled = false;
        }
    }
}

// Retry failed files - Re-upload only the files that failed
async function retryFailedFiles() {
    if (!failedFilesList || failedFilesList.length === 0) {
        showToast('No failed files to retry', 'warning');
        return;
    }
    
    // Extract File objects from failed files
    const filesToRetry = failedFilesList
        .filter(failed => failed.file) // Only include files that have File objects
        .map(failed => failed.file);
    
    if (filesToRetry.length === 0) {
        showToast('Failed files are no longer available. Please re-select them manually.', 'warning');
        return;
    }
    
    // Show confirmation
    const confirmed = confirmAction(
        `Are you sure you want to retry uploading ${filesToRetry.length} failed file(s)?\n\nThe system will preserve your ${uploadedKeys.length} successfully uploaded files and only re-upload the failed ones.`
    );
    
    if (!confirmed) {
        return;
    }
    
    // Disable retry button to prevent multiple clicks
    const retryBtn = document.getElementById('retryFailedBtn');
    if (retryBtn) {
        retryBtn.disabled = true;
        retryBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Retrying...';
    }
    
    // Store the current failed files count for comparison
    const previousFailedCount = failedFilesList.length;
    
    // Clear the failed files list (will be repopulated during upload if they fail again)
    failedFilesList = [];
    
    // Call uploadToS3 with the failed files
    try {
        await uploadToS3(filesToRetry);
        
        // Check if retry was successful (no new failures)
        // Note: uploadToS3 will repopulate failedFilesList if files fail again
        const stillFailed = failedFilesList.length;
        
        // Re-enable retry button after upload completes
        if (retryBtn) {
            if (stillFailed > 0) {
                // Some files still failed - button remains available for another retry
                retryBtn.disabled = false;
                retryBtn.innerHTML = `<i class="bi bi-arrow-clockwise"></i> Retry Failed Files (${stillFailed} remaining)`;
                showToast(`${stillFailed} file(s) still failed. You can retry again.`, 'warning');
            } else {
                // All files succeeded - hide or disable button
                retryBtn.disabled = true;
                retryBtn.innerHTML = '<i class="bi bi-check-circle"></i> All Files Retried';
                retryBtn.classList.remove('btn-warning');
                retryBtn.classList.add('btn-success');
                showToast('All failed files have been successfully retried!', 'success');
            }
        }
    } catch (error) {
        console.error('Retry failed:', error);
        showToast('An error occurred while retrying failed files', 'error');
        
        // Re-enable retry button on error
        if (retryBtn) {
            retryBtn.disabled = false;
            retryBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Retry Failed Files';
        }
    }
}

// Parse and save to database - Enhanced with progress tracking
async function parseAndSave() {
    if (uploadedKeys.length === 0) {
        showToast('No files to save', 'warning');
        return;
    }
    
    const totalFiles = uploadedKeys.length;
    document.getElementById('saveBtn').disabled = true;
    
    // Show enhanced progress container
    const dbProgressContainer = document.getElementById('dbProgressContainer');
    const saveResult = document.getElementById('saveResult');
    
    dbProgressContainer.style.display = 'block';
    saveResult.innerHTML = ''; // Clear any previous results
    
    // Initialize progress
    document.getElementById('dbTotalCount').textContent = totalFiles;
    document.getElementById('dbProcessedCount').textContent = '0';
    document.getElementById('dbProgressPercent').textContent = '0%';
    document.getElementById('dbProgressBar').style.width = '0%';
    document.getElementById('dbProgressBarText').textContent = 'Initializing...';
    document.getElementById('dbStatusText').textContent = 'Preparing to parse filenames and save to database...';
    
    // Scroll to show progress
    dbProgressContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    
    // Time tracking
    const startTime = Date.now();
    const updateDbTimeDisplay = () => {
        const elapsed = (Date.now() - startTime) / 1000;
        document.getElementById('dbElapsedTime').textContent = formatTime(elapsed);
        
        // Estimate remaining time (rough estimate: ~0.1-0.5 seconds per file)
        const avgTimePerFile = 0.3; // Conservative estimate
        const processed = parseInt(document.getElementById('dbProcessedCount').textContent) || 0;
        if (processed > 0 && processed < totalFiles) {
            const remaining = (totalFiles - processed) * avgTimePerFile;
            document.getElementById('dbEstimatedTime').textContent = formatTime(remaining);
        } else if (processed >= totalFiles) {
            document.getElementById('dbEstimatedTime').textContent = 'Complete';
        }
    };
    
    // Start time update interval
    const dbTimeInterval = setInterval(updateDbTimeDisplay, 100);
    
    // Simulate progress updates (since we can't get real-time progress from API)
    // Update status text periodically
    let statusUpdateCount = 0;
    const statusMessages = [
        'Parsing filenames...',
        'Validating invoice data...',
        'Checking for duplicates...',
        'Saving to database...',
        'Finalizing...'
    ];
    
    const statusInterval = setInterval(() => {
        if (statusUpdateCount < statusMessages.length - 1) {
            statusUpdateCount++;
            document.getElementById('dbStatusText').textContent = statusMessages[statusUpdateCount];
        }
    }, Math.max(1000, (totalFiles * 50))); // Update every ~50ms per file, min 1 second
    
    try {
        // Update status to show we're making the API call
        document.getElementById('dbStatusText').textContent = 'Sending request to server...';
        document.getElementById('dbProgressBar').style.width = '10%';
        document.getElementById('dbProgressBarText').textContent = '10% - Connecting...';
        
        const requestBody = { s3Keys: uploadedKeys };
        
        // Simulate progress during API call (we'll update to 90% during the call)
        setTimeout(() => {
            document.getElementById('dbProgressBar').style.width = '50%';
            document.getElementById('dbProgressBarText').textContent = '50% - Processing...';
            document.getElementById('dbStatusText').textContent = 'Server is processing files...';
        }, 500);
        
        setTimeout(() => {
            document.getElementById('dbProgressBar').style.width = '75%';
            document.getElementById('dbProgressBarText').textContent = '75% - Almost done...';
            document.getElementById('dbStatusText').textContent = 'Finalizing database operations...';
        }, 2000);
        
        // Use non-blocking API request to avoid global loading overlay
        // We have our own detailed progress indicators
        const data = await apiRequestNoLoading(ADMIN_API_BASE + '/bulk_save_invoices.php', {
            body: requestBody
        });
        
        // Clear intervals
        clearInterval(dbTimeInterval);
        clearInterval(statusInterval);
        
        // Update to 100% before showing results
        document.getElementById('dbProgressBar').style.width = '100%';
        document.getElementById('dbProgressBarText').textContent = '100% - Complete!';
        document.getElementById('dbProcessedCount').textContent = totalFiles;
        document.getElementById('dbProgressPercent').textContent = '100%';
        document.getElementById('dbStatusText').textContent = 'Database save completed successfully!';
        updateDbTimeDisplay(); // Final time update
        
        if (data.success) {
            // Hide progress container
            dbProgressContainer.style.display = 'none';
            
            // Determine alert type based on failures
            const hasFailures = data.failedFiles && data.failedFiles.length > 0;
            const alertType = hasFailures ? 'alert-warning' : 'alert-success';
            const alertIcon = hasFailures ? '⚠️' : '✅';
            
            // Calculate processing time
            const processingTime = ((Date.now() - startTime) / 1000).toFixed(1);
            
            // Show enhanced results - match backend response fields
            let resultHtml = `
                <div class="alert ${alertType}">
                    <div class="d-flex align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="alert-heading mb-2">
                                ${alertIcon} Database Save Complete
                                <small class="text-muted ms-2">(${formatTime(processingTime)})</small>
                            </h6>
                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                                        <div>
                                            <strong>${data.processed || 0}</strong> invoices processed
                                        </div>
                                    </div>
                                </div>
                                ${data.inserted ? `
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-plus-circle-fill text-primary me-2"></i>
                                        <div>
                                            <strong>${data.inserted}</strong> new invoices added
                                        </div>
                                    </div>
                                </div>
                                ` : ''}
                                ${data.updated ? `
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-arrow-repeat text-info me-2"></i>
                                        <div>
                                            <strong>${data.updated}</strong> existing invoices replaced
                                        </div>
                                    </div>
                                </div>
                                ` : ''}
                                ${data.failed > 0 ? `
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-x-circle-fill text-danger me-2"></i>
                                        <div>
                                            <strong>${data.failed}</strong> files failed
                                        </div>
                                    </div>
                                </div>
                                ` : ''}
                            </div>
                            ${data.message ? `<p class="mb-0 mt-2"><em>${data.message}</em></p>` : ''}
                        </div>
                    </div>
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
            
            // Clear uploaded files after successful save
            uploadedKeys = [];
            uploadedFilesMap.clear();
            updateUploadSummary(0, 0);
            document.getElementById('clearUploadsBtn').style.display = 'none';
            
            showToast('Invoices saved to database!', 'success');
            
            // Refresh invoice list
            loadInvoices();
        } else {
            showToast('Failed to save: ' + (data.message || 'Unknown error'), 'danger');
        }
    } catch (error) {
        console.error('Save error:', error);
        
        // Clear intervals
        clearInterval(dbTimeInterval);
        clearInterval(statusInterval);
        
        // Hide progress container
        dbProgressContainer.style.display = 'none';
        
        // Show error in result area
        document.getElementById('saveResult').innerHTML = `
            <div class="alert alert-danger">
                <h6 class="alert-heading">
                    <i class="bi bi-exclamation-triangle"></i> Save Failed
                    <small class="text-muted ms-2">(${formatTime((Date.now() - startTime) / 1000)})</small>
                </h6>
                <p class="mb-0">${escapeHtml(error.message || 'Unknown error occurred')}</p>
                <small class="text-muted mt-2 d-block">
                    <i class="bi bi-info-circle"></i> Please check your connection and try again. 
                    The files are still in S3 and can be processed later.
                </small>
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

// Update upload summary display
function updateUploadSummary(readyCount, failedCount) {
    const summaryDiv = document.getElementById('uploadSummary');
    const readyCountSpan = document.getElementById('readyFilesCount');
    const failedCountSpan = document.getElementById('failedFilesCount');
    const failedCountText = document.getElementById('failedFilesCountText');
    
    if (readyCount > 0) {
        summaryDiv.style.display = 'block';
        readyCountSpan.textContent = readyCount;
        
        if (failedCount > 0) {
            failedCountText.style.display = 'inline';
            failedCountSpan.textContent = failedCount;
        } else {
            failedCountText.style.display = 'none';
        }
    } else {
        summaryDiv.style.display = 'none';
    }
}

// Clear all uploaded files
function clearUploadedFiles() {
    if (!confirmAction('Are you sure you want to clear all uploaded files? You will need to upload them again.')) {
        return;
    }
    
    uploadedKeys = [];
    uploadedFilesMap.clear();
    allSelectedFiles = [];
    failedFilesList = [];
    updateUploadSummary(0, 0);
    document.getElementById('clearUploadsBtn').style.display = 'none';
    document.getElementById('saveBtn').disabled = true;
    document.getElementById('uploadProgress').style.display = 'none';
    
    // Clear any failed files alerts
    const uploadProgress = document.getElementById('uploadProgress');
    const existingAlert = uploadProgress.querySelector('.alert-warning');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    showToast('All uploaded files cleared', 'info');
}


// Toggle file list visibility
function toggleFileList() {
    const container = document.getElementById('fileListContainer');
    const icon = document.getElementById('fileListToggleIcon');
    const isVisible = container.style.display !== 'none';
    
    container.style.display = isVisible ? 'none' : 'block';
    icon.className = isVisible ? 'bi bi-chevron-down' : 'bi bi-chevron-up';
}

// Format file size helper
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
}

// Format time helper
function formatTime(seconds) {
    if (seconds < 60) return Math.round(seconds) + 's';
    const mins = Math.floor(seconds / 60);
    const secs = Math.round(seconds % 60);
    return mins + 'm ' + secs + 's';
}

// Non-blocking API request (doesn't show global loading overlay)
// Used for invoice operations where we have our own detailed progress indicators
async function apiRequestNoLoading(url, options = {}) {
    const token = await getAuthToken();

    if (!token) {
        throw new Error('Authentication required');
    }

    // Construct the payload with token + any data from options
    const payload = {
        idToken: token,
        ...(options.body || {})
    };

    // Create fetch options
    const fetchOptions = {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            ...(options.headers || {})
        },
        ...options,
        body: JSON.stringify(payload)
    };

    try {
        // Don't call showLoading() - we have our own progress indicators
        const response = await fetch(url, fetchOptions);

        let data;
        const responseText = await response.text();

        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error("JSON Parse Error. Raw Response:", responseText);
            throw new Error(`Invalid server response (not JSON). Check console for details. Response start: ${responseText.substring(0, 50)}...`);
        }

        if (!response.ok) {
            throw new Error(data.message || 'Request failed');
        }

        return data;
    } catch (error) {
        throw error;
    }
}

// Upload single file with custom filename (non-blocking - doesn't show global loading overlay)
async function uploadFile(file, folder, customFilename) {
    console.log('Uploading:', customFilename); // DEBUG: Check what filename is being sent
    
    // Ensure global loading overlay stays hidden during file upload
    if (typeof hideLoading === 'function') {
        hideLoading();
    }
    const loadingSpinner = document.getElementById('loadingSpinner');
    if (loadingSpinner) {
        loadingSpinner.classList.remove('show');
        loadingSpinner.style.display = 'none'; // Force hide
    }
    
    const formData = new FormData();
    formData.append('file', file);
    formData.append('folder', folder);
    formData.append('custom_filename', customFilename); // Preserve original name
    
    console.log('FormData custom_filename:', formData.get('custom_filename')); // DEBUG
    
    try {
        // Use direct fetch instead of global uploadFile to avoid blocking overlay
        // We have our own detailed progress indicators
        const response = await fetch(API_BASE + '/upload.php', {
            method: 'POST',
            body: formData
        });
        
        // Keep overlay hidden after fetch
        if (loadingSpinner) {
            loadingSpinner.classList.remove('show');
            loadingSpinner.style.display = 'none';
        }
        
        const data = await response.json();
        
        console.log('Upload response:', data); // DEBUG: Check server response
        
        if (response.ok && data.url) {
            return data.url; // Returns S3 key
        }
        
        // Return error details if available
        if (data.message) {
            throw new Error(data.message);
        }
        
        throw new Error(`Upload failed: HTTP ${response.status}`);
    } catch (error) {
        console.error('Upload error:', error);
        // Ensure overlay stays hidden even on error
        if (loadingSpinner) {
            loadingSpinner.classList.remove('show');
            loadingSpinner.style.display = 'none';
        }
        // Re-throw to let caller handle it
        throw error;
    }
}

// Non-blocking API request (doesn't show global loading overlay)
async function apiRequestNoLoading(url, options = {}) {
    const token = await getAuthToken();

    if (!token) {
        throw new Error('Authentication required');
    }

    // Construct the payload with token + any data from options
    const payload = {
        idToken: token,
        ...(options.body || {})
    };

    // Create fetch options
    const fetchOptions = {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            ...(options.headers || {})
        },
        ...options,
        body: JSON.stringify(payload)
    };

    try {
        // Don't call showLoading() - we have our own progress indicators
        const response = await fetch(url, fetchOptions);

        let data;
        const responseText = await response.text();

        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error("JSON Parse Error. Raw Response:", responseText);
            throw new Error(`Invalid server response (not JSON). Check console for details. Response start: ${responseText.substring(0, 50)}...`);
        }

        if (!response.ok) {
            throw new Error(data.message || 'Request failed');
        }

        return data;
    } catch (error) {
        throw error;
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
