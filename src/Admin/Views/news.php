<?php
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle = 'News Management';
$currentPage = 'news';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-newspaper"></i> News Management</h2>
        <p class="text-muted">Create and manage news content</p>
    </div>
    <div class="col-auto">
        <button class="btn btn-eds-primary" data-bs-toggle="modal" data-bs-target="#addNewsModal">
            <i class="bi bi-plus-circle"></i> Add News
        </button>
    </div>
</div>

<!-- News Grid -->
<div class="row g-4" id="newsGrid">
    <div class="col-12 text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="text-muted mt-2">Loading news...</p>
    </div>
</div>

<!-- Add News Modal -->
<div class="modal fade" id="addNewsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle"></i> Add New News Item
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addNewsForm">
                    <div class="mb-3">
                        <label class="form-label">News Image</label>
                        <input type="file" class="form-control mb-2" id="imageFile" accept="image/*" onchange="handleImageUpload(this, 'imageUrl', 'imagePreview')">
                        <div id="imagePreview" class="mt-2 d-none text-center">
                            <img src="" class="img-fluid rounded border" style="max-height: 200px">
                        </div>
                        <small class="text-muted d-block mt-1">Upload an image for the news (JPG, PNG)</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" placeholder="Enter news title..." required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Short Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="shortDescription" rows="2" placeholder="Brief summary (max 500 characters)..." maxlength="500" required></textarea>
                        <small class="text-muted"><span id="shortDescCount">0</span>/500 characters</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Details <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="details" rows="4" placeholder="Full news content..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Website Link <span class="text-danger">*</span></label>
                        <input type="url" class="form-control" id="link" placeholder="https://www.company-website.com" required>
                        <small class="text-muted">External link to company website</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-eds-primary" onclick="addNews()">
                    <i class="bi bi-check-circle"></i> Create News
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit News Modal -->
<div class="modal fade" id="editNewsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil-square"></i> Edit News
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editNewsForm">
                    <input type="hidden" id="editNewsId">
                    <div class="mb-3">
                        <label class="form-label">News Image</label>
                        <input type="file" class="form-control mb-2" id="editImageFile" accept="image/*" onchange="handleImageUpload(this, 'editImageUrl', 'editImagePreview')">
                        <div id="editImagePreview" class="mt-2 d-none text-center">
                            <img src="" class="img-fluid rounded border" style="max-height: 200px">
                        </div>
                        <small class="text-muted d-block mt-1">Upload new image to replace current one</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editTitle" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Short Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="editShortDescription" rows="2" maxlength="500" required></textarea>
                        <small class="text-muted"><span id="editShortDescCount">0</span>/500 characters</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Details <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="editDetails" rows="4" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Website Link <span class="text-danger">*</span></label>
                        <input type="url" class="form-control" id="editLink" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-eds-primary" onclick="updateNews()">
                    <i class="bi bi-check-circle"></i> Update News
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentNews = [];

// Character counters
document.getElementById('shortDescription')?.addEventListener('input', function() {
    document.getElementById('shortDescCount').textContent = this.value.length;
});
document.getElementById('editShortDescription')?.addEventListener('input', function() {
    document.getElementById('editShortDescCount').textContent = this.value.length;
});

// Load all news
async function loadNews() {
    try {
        const data = await apiRequest(ADMIN_API_BASE + '/get_all_news.php', {
            body: { limit: 100, offset: 0 }
        });
        
        if (data.success) {
            currentNews = data.data;
            displayNews(data.data);
        }
    } catch (error) {
        document.getElementById('newsGrid').innerHTML = `
            <div class="col-12">
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i> Failed to load news: ${error.message}
                </div>
            </div>
        `;
    }
}

// Display news in grid
function displayNews(newsItems) {
    const grid = document.getElementById('newsGrid');
    
    if (newsItems.length === 0) {
        grid.innerHTML = `
            <div class="col-12 text-center py-5">
                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                <p class="text-muted mt-3">No news yet. Click "Add News" to create one.</p>
            </div>
        `;
        return;
    }
    
    grid.innerHTML = newsItems.map(news => {
        const imageUrl = news.image_url;
            
        return `
        <div class="col-md-4">
            <div class="card promo-card">
                <img src="${imageUrl}" alt="News" onerror="this.src='https://via.placeholder.com/400x200?text=Image+Not+Found'">
                <div class="card-body">
                    <h6 class="card-title">${truncateText(news.title, 50)}</h6>
                    <p class="card-text text-muted small">${truncateText(news.short_description, 80)}</p>
                    <p class="card-text">${truncateText(news.details, 100)}</p>
                    <div class="mb-2">
                        <small class="text-muted d-block">
                            <i class="bi bi-link-45deg"></i> ${truncateText(news.link, 40)}
                        </small>
                        <small class="text-muted">
                            <i class="bi bi-clock"></i> ${formatDate(news.created_at)}
                        </small>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-sm btn-outline-primary" onclick="openEditModal('${news.id}')">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteNews('${news.id}')">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `}).join('');
}

// Add new news
async function addNews() {
    const title = document.getElementById('title').value.trim();
    const shortDescription = document.getElementById('shortDescription').value.trim();
    const details = document.getElementById('details').value.trim();
    const link = document.getElementById('link').value.trim();
    
    if (!title || !shortDescription || !details || !link) {
        showToast('Please fill all required fields', 'warning');
        return;
    }
    
    if (!pendingImageFile) {
        showToast('Please select an image', 'warning');
        return;
    }
    
    try {
        // Upload image to S3 first
        showToast('Uploading image...', 'info');
        const s3Key = await uploadFile(pendingImageFile, 'news');
        
        // Then create news record with S3 key
        const data = await apiRequest(API_BASE + '/add_news.php', {
            body: { 
                image_url: s3Key, 
                title: title,
                short_description: shortDescription,
                details: details,
                link: link
            }
        });
        
        if (data.success) {
            showToast('News created successfully!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('addNewsModal')).hide();
            document.getElementById('addNewsForm').reset();
            document.getElementById('shortDescCount').textContent = '0';
            resetUploadPreview('imagePreview');
            loadNews();
        }
    } catch (error) {
        showToast('Failed to create news: ' + error.message, 'danger');
    }
}

// Storage for selected files (not uploaded yet)
let pendingImageFile = null;
let pendingEditImageFile = null;

// Handle Image Selection (preview only, no upload)
function handleImageUpload(fileInput, urlInputId, previewId) {
    const file = fileInput.files[0];
    if (!file) return;

    // Store file for later upload
    if (previewId === 'imagePreview') {
        pendingImageFile = file;
    } else if (previewId === 'editImagePreview') {
        pendingEditImageFile = file;
    }

    // Show instant local preview
    const preview = document.getElementById(previewId);
    const reader = new FileReader();
    reader.onload = function(e) {
        preview.classList.remove('d-none');
        preview.querySelector('img').src = e.target.result;
    };
    reader.readAsDataURL(file);
}

function resetUploadPreview(previewId) {
    const preview = document.getElementById(previewId);
    preview.classList.add('d-none');
    preview.querySelector('img').src = '';
    
    // Clear pending file
    if (previewId === 'imagePreview') {
        pendingImageFile = null;
    } else if (previewId === 'editImagePreview') {
        pendingEditImageFile = null;
    }
}

// Open edit modal
function openEditModal(newsId) {
    const newsItem = currentNews.find(n => n.id === newsId);
    if (!newsItem) return;
    
    document.getElementById('editNewsId').value = newsItem.id;
    document.getElementById('editTitle').value = newsItem.title;
    document.getElementById('editShortDescription').value = newsItem.short_description;
    document.getElementById('editDetails').value = newsItem.details;
    document.getElementById('editLink').value = newsItem.link;
    document.getElementById('editShortDescCount').textContent = newsItem.short_description.length;
    
    // Clear pending edit file
    pendingEditImageFile = null;
    document.getElementById('editImageFile').value = '';
    
    // Show current image preview using presigned URL
    const imageUrl = newsItem.image_url;
    
    const preview = document.getElementById('editImagePreview');
    preview.classList.remove('d-none');
    preview.querySelector('img').src = imageUrl;
    
    new bootstrap.Modal(document.getElementById('editNewsModal')).show();
}

// Update news
async function updateNews() {
    const newsId = document.getElementById('editNewsId').value;
    const title = document.getElementById('editTitle').value.trim();
    const shortDescription = document.getElementById('editShortDescription').value.trim();
    const details = document.getElementById('editDetails').value.trim();
    const link = document.getElementById('editLink').value.trim();
    
    if (!title || !shortDescription || !details || !link) {
        showToast('All fields except image are required', 'warning');
        return;
    }
    
    try {
        const requestBody = { 
            newsId: newsId, 
            title: title,
            short_description: shortDescription,
            details: details,
            link: link
        };
        
        // If a new image was selected, upload it to S3 first
        if (pendingEditImageFile) {
            showToast('Uploading new image...', 'info');
            const s3Key = await uploadFile(pendingEditImageFile, 'news');
            requestBody.image_url = s3Key;
        }
        
        const data = await apiRequest(ADMIN_API_BASE + '/update_news.php', {
            body: requestBody
        });
        
        if (data.success) {
            showToast('News updated successfully!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('editNewsModal')).hide();
            loadNews();
        }
    } catch (error) {
        showToast('Failed to update news: ' + error.message, 'danger');
    }
}

// Delete news
async function deleteNews(newsId) {
    if (!confirmAction('Are you sure you want to delete this news? This cannot be undone.')) return;
    
    try {
        const data = await apiRequest(ADMIN_API_BASE + '/delete_news.php', {
            body: { newsId: newsId }
        });
        
        if (data.success) {
            showToast('News deleted successfully!', 'success');
            loadNews();
        }
    } catch (error) {
        showToast('Failed to delete news: ' + error.message, 'danger');
    }
}

// Load news on page load
document.addEventListener('DOMContentLoaded', loadNews);
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
