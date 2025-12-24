<?php
require_once 'includes/auth_check.php';
$pageTitle = 'Knowledge Base Management';
$currentPage = 'knowledge_base';
?>
<?php include 'includes/header.php'; ?>

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
    </div>
</div>

<script>
// Load documents on page load
document.addEventListener('DOMContentLoaded', function() {
    loadDocuments();
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
            loadDocuments(); // Reload the table
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

// Load documents from API
async function loadDocuments() {
    try {
        const response = await fetch(API_BASE + '/get_knowledge_base.php');
        const data = await response.json();
        
        if (data.success) {
            displayDocuments(data.data);
        } else {
            showToast('Failed to load documents', 'danger');
        }
    } catch (error) {
        console.error('Load error:', error);
        document.getElementById('documentsTableBody').innerHTML = 
            '<tr><td colspan="4" class="text-center text-danger">Failed to load documents</td></tr>';
    }
}

// Display documents in table
function displayDocuments(documents) {
    const tbody = document.getElementById('documentsTableBody');
    
    if (documents.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No documents found</td></tr>';
        return;
    }
    
    tbody.innerHTML = documents.map(doc => `
        <tr>
            <td><strong>${escapeHtml(doc.title)}</strong></td>
            <td>${escapeHtml(doc.subtitle || '-')}</td>
            <td>${formatDate(doc.created_at)}</td>
            <td>
                <button class="btn btn-sm btn-danger" onclick="deleteDocument('${doc.id}', '${escapeHtml(doc.title)}')">
                    <i class="bi bi-trash"></i> Delete
                </button>
            </td>
        </tr>
    `).join('');
}

// Delete document
async function deleteDocument(id, title) {
    if (!confirm(`Are you sure you want to delete "${title}"?`)) {
        return;
    }
    
    showLoading();
    
    try {
        const response = await fetch(API_BASE + '/delete_knowledge_base.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'id=' + encodeURIComponent(id)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Document deleted successfully', 'success');
            loadDocuments(); // Reload the table
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

// Search functionality
document.getElementById('searchInput').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#documentsTableBody tr');
    
    rows.forEach(row => {
        const title = row.cells[0]?.textContent.toLowerCase() || '';
        const subtitle = row.cells[1]?.textContent.toLowerCase() || '';
        
        if (title.includes(searchTerm) || subtitle.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Helper: Format date
function formatDate(dateStr) {
    const date = new Date(dateStr);
    const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return date.toLocaleDateString('en-US', options);
}

// Helper: Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php include 'includes/footer.php'; ?>
