<?php
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle = 'Knowledge Base Management';
$currentPage = 'knowledge_base';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-book"></i> Knowledge Base Management</h2>
        <p class="text-muted">Upload and manage PDF documents for the knowledge base</p>
    </div>
</div>

<!-- Upload Card -->
<div class="card mb-4">
    <div class="card-header bg-eds-primary text-white">
        <h5 class="mb-0"><i class="bi bi-cloud-upload"></i> Upload New Document</h5>
    </div>
    <div class="card-body">
        <form id="uploadForm" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" required maxlength="255">
                    <small class="text-muted">Enter a clear, descriptive title</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="subtitle" class="form-label">Subtitle</label>
                    <input type="text" class="form-control" id="subtitle" name="subtitle" maxlength="500">
                    <small class="text-muted">Optional additional description</small>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="pdfFile" class="form-label">PDF File <span class="text-danger">*</span></label>
                <input type="file" class="form-control" id="pdfFile" name="file" accept=".pdf" required>
                <small class="text-muted">Only PDF files are allowed</small>
            </div>
            
            <button type="submit" class="btn btn-eds-primary" id="uploadBtn">
                <i class="bi bi-upload"></i> Upload Document
            </button>
        </form>
        
        <div id="uploadResult" class="mt-3"></div>
    </div>
</div>

<!-- Existing Documents Card -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-file-earmark-text"></i> Existing Documents</h5>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <input type="text" class="form-control" id="searchInput" placeholder="Search by title or subtitle...">
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Subtitle</th>
                        <th>Upload Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="documentsTableBody">
                    <tr>
                        <td colspan="4" class="text-center">
                            <div class="spinner-border text-eds-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
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
            <span class="text-muted">Loading more documents...</span>
        </div>
        <!-- End of list indicator -->
        <div class="text-center mt-3 py-2" id="endOfListIndicator" style="display: none;">
            <small class="text-muted">
                <i class="bi bi-check-circle"></i> All documents loaded
            </small>
        </div>
    </div>
</div>

<!-- Edit Document Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-eds-primary text-white">
                <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Document</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" id="editDocId">
                    <input type="hidden" id="editDocFileUrl">
                    
                    <div class="mb-3">
                        <label for="editTitle" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editTitle" required maxlength="255">
                    </div>
                    
                    <div class="mb-3">
                        <label for="editSubtitle" class="form-label">Subtitle</label>
                        <input type="text" class="form-control" id="editSubtitle" maxlength="500">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Replace PDF File (Optional)</label>
                        <input type="file" class="form-control" id="editPdfFile" accept=".pdf">
                        <small class="text-muted">Leave empty to keep existing file</small>
                    </div>
                    
                    <div id="editResult"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-eds-primary" onclick="saveEdit()">
                    <i class="bi bi-save"></i> Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let allDocuments = [];
let currentOffset = 0;
let currentLimit = 50;
let currentSearch = '';
let totalDocuments = 0;
let isLoadingMore = false;

// Load documents on page load
document.addEventListener('DOMContentLoaded', function() {
    loadDocuments(true);
    setupInfiniteScroll();
});

// Handle form submission
document.getElementById('uploadForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const uploadBtn = document.getElementById('uploadBtn');
    
    uploadBtn.disabled = true;
    uploadBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Uploading...';
    
    try {
        const response = await fetch(API_BASE + '/upload_knowledge_base.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Document uploaded successfully!', 'success');
            document.getElementById('uploadForm').reset();
            loadDocuments(true); // Reload the table from beginning
        } else {
            showToast('Upload failed: ' + (data.message || 'Unknown error'), 'danger');
        }
    } catch (error) {
        console.error('Upload error:', error);
        showToast('Upload failed: ' + error.message, 'danger');
    } finally {
        uploadBtn.disabled = false;
        uploadBtn.innerHTML = '<i class="bi bi-upload"></i> Upload Document';
    }
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
    // Don't check if already loading or no more documents to load
    if (isLoadingMore || allDocuments.length >= totalDocuments) {
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
        loadDocuments(false);
    }
}

// Load documents from API (reset = true to start from beginning, false to append)
async function loadDocuments(reset = false) {
    if (reset) {
        currentOffset = 0;
        allDocuments = [];
        currentSearch = document.getElementById('searchInput').value.trim();
    }
    
    if (isLoadingMore) return; // Prevent multiple simultaneous requests
    isLoadingMore = true;
    
    try {
        const searchValue = reset ? currentSearch : currentSearch;
        
        // Show loading state
        if (reset) {
            document.getElementById('documentsTableBody').innerHTML = `
                <tr>
                    <td colspan="4" class="text-center">
                        <div class="spinner-border text-eds-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
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
        
        const url = new URL(API_BASE + '/get_knowledge_base.php');
        url.searchParams.append('limit', currentLimit);
        url.searchParams.append('offset', currentOffset);
        if (searchValue) {
            url.searchParams.append('search', searchValue);
        }
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            totalDocuments = data.total;
            
            if (reset) {
                allDocuments = data.data;
            } else {
                // Append new documents
                allDocuments = allDocuments.concat(data.data);
            }
            
            displayDocuments(allDocuments);
            
            // Show/hide loading indicators
            const hasMore = allDocuments.length < totalDocuments;
            document.getElementById('loadingMoreIndicator').style.display = 'none';
            document.getElementById('endOfListIndicator').style.display = hasMore ? 'none' : 'block';
            
            if (hasMore) {
                currentOffset = allDocuments.length;
            }
        } else {
            showToast('Failed to load documents', 'danger');
            if (reset) {
                document.getElementById('documentsTableBody').innerHTML = 
                    '<tr><td colspan="4" class="text-center text-danger">Failed to load documents</td></tr>';
            }
            document.getElementById('loadingMoreIndicator').style.display = 'none';
            document.getElementById('endOfListIndicator').style.display = 'none';
        }
    } catch (error) {
        console.error('Load error:', error);
        if (reset) {
            document.getElementById('documentsTableBody').innerHTML = 
                '<tr><td colspan="4" class="text-center text-danger">Failed to load documents</td></tr>';
        }
        document.getElementById('loadingMoreIndicator').style.display = 'none';
        document.getElementById('endOfListIndicator').style.display = 'none';
    } finally {
        isLoadingMore = false;
        document.getElementById('loadingMoreIndicator').style.display = 'none';
    }
}

// Display documents in table
function displayDocuments(documents) {
    const tbody = document.getElementById('documentsTableBody');
    
    if (documents.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted">No documents found${currentSearch ? ` for "${currentSearch}"` : ''}</td></tr>`;
        return;
    }
    
    tbody.innerHTML = documents.map(doc => `
        <tr>
            <td><strong>${escapeHtml(doc.title)}</strong></td>
            <td>${escapeHtml(doc.subtitle || '-')}</td>
            <td>${formatDate(doc.created_at)}</td>
            <td>
                <div class="btn-group" role="group">
                    <button class="btn btn-sm btn-info" onclick="viewDocument('${doc.file_url}', '${escapeHtml(doc.title)}')" title="View Document">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-primary" onclick="editDocument('${doc.id}', '${escapeHtml(doc.title)}', '${escapeHtml(doc.subtitle || '')}', '${doc.file_url}')" title="Edit Document">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteDocument('${doc.id}', '${escapeHtml(doc.title)}')" title="Delete Document">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// View document - opens PDF in new tab
async function viewDocument(fileUrl, documentTitle) {
    if (!fileUrl) {
        showToast('Document file URL not available', 'warning');
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
            showToast('Failed to load document: ' + (data.message || 'Unknown error'), 'danger');
        }
    } catch (error) {
        showToast('Failed to view document: ' + error.message, 'danger');
    }
}

// Delete document
async function deleteDocument(id, title) {
    if (!confirmAction(`Are you sure you want to delete "${title}"? This will permanently delete the document and its PDF file from S3.`)) {
        return;
    }
    
    showLoading();
    
    try {
        // Get auth token for admin authentication
        const idToken = await getAuthToken();
        
        const data = await apiRequest(API_BASE + '/delete_knowledge_base.php', {
            body: {
                idToken: idToken,
                id: id
            }
        });
        
        if (data.success) {
            showToast(data.message || 'Document deleted successfully', 'success');
            loadDocuments(true); // Reload the table from beginning
        } else {
            showToast('Delete failed: ' + (data.message || 'Unknown error'), 'danger');
        }
    } catch (error) {
        console.error('Delete error:', error);
        showToast('Delete failed: ' + error.message, 'danger');
    } finally {
        hideLoading();
    }
}

// Edit document
function editDocument(id, title, subtitle, fileUrl) {
    document.getElementById('editDocId').value = id;
    document.getElementById('editTitle').value = title;
    document.getElementById('editSubtitle').value = subtitle;
    document.getElementById('editDocFileUrl').value = fileUrl;
    document.getElementById('editPdfFile').value = '';
    document.getElementById('editResult').innerHTML = '';
    
    const modal = new bootstrap.Modal(document.getElementById('editModal'));
    modal.show();
}

// Save edited document
async function saveEdit() {
    const docId = document.getElementById('editDocId').value;
    const title = document.getElementById('editTitle').value.trim();
    const subtitle = document.getElementById('editSubtitle').value.trim();
    const fileInput = document.getElementById('editPdfFile');
    const currentFileUrl = document.getElementById('editDocFileUrl').value;
    
    if (!title) {
        showToast('Title is required', 'warning');
        return;
    }
    
    showLoading();
    
    try {
        let fileUrl = currentFileUrl;
        
        // If new file is selected, upload it first
        if (fileInput.files.length > 0) {
            const formData = new FormData();
            formData.append('file', fileInput.files[0]);
            formData.append('folder', 'knowledge_base');
            
            const uploadResponse = await fetch(API_BASE + '/upload.php', {
                method: 'POST',
                body: formData
            });
            
            const uploadData = await uploadResponse.json();
            
            if (!uploadData.success) {
                showToast('File upload failed: ' + (uploadData.message || 'Unknown error'), 'danger');
                hideLoading();
                return;
            }
            
            fileUrl = uploadData.url;
        }
        
        // Get auth token
        const idToken = await getAuthToken();
        
        // Update document
        const response = await fetch(API_BASE + '/update_knowledge_base.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                idToken: idToken,
                documentId: docId,
                title: title,
                subtitle: subtitle || null,
                file_url: fileUrl
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Document updated successfully!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
            loadDocuments(true); // Reload the table from beginning
        } else {
            showToast('Update failed: ' + (data.message || 'Unknown error'), 'danger');
        }
    } catch (error) {
        console.error('Update error:', error);
        showToast('Update failed: ' + error.message, 'danger');
    } finally {
        hideLoading();
    }
}

// Search functionality - reload from API with search
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        loadDocuments(true);
    }
});

// Also trigger search on input change with debounce
let searchTimeout;
document.getElementById('searchInput').addEventListener('input', function(e) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() {
        loadDocuments(true);
    }, 500); // Wait 500ms after user stops typing
});

// Helper: Format date - properly handles timezone-less timestamps
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

// Helper: Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
