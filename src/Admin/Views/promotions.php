<?php
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle = 'Promotions Management';
$currentPage = 'promotions';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-megaphone"></i> Promotions Management</h2>
        <p class="text-muted">Create and manage promotional content</p>
    </div>
    <div class="col-auto">
        <button class="btn btn-eds-primary" data-bs-toggle="modal" data-bs-target="#addPromoModal">
            <i class="bi bi-plus-circle"></i> Add Promotion
        </button>
    </div>
</div>

<!-- Promotions Grid -->
<div class="row g-4" id="promotionsGrid">
    <div class="col-12 text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="text-muted mt-2">Loading promotions...</p>
    </div>
</div>

<!-- Add/Edit Promotion Modal -->
<div class="modal fade" id="addPromoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle"></i> Add New Promotion
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addPromoForm">
                    <div class="mb-3">
                        <label class="form-label">Promotion Image</label>
                        <input type="file" class="form-control mb-2" id="imageFile" accept="image/*" onchange="handleImageUpload(this, 'imageUrl', 'imagePreview')">
                        <div id="imagePreview" class="mt-2 d-none text-center">
                            <img src="" class="img-fluid rounded border" style="max-height: 200px">
                        </div>
                        <small class="text-muted d-block mt-1">Upload an image for the promotion (JPG, PNG)</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" placeholder="Enter promotion title..." required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" id="description" rows="4" placeholder="Enter promotion description..." required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-eds-primary" onclick="addPromotion()">
                    <i class="bi bi-check-circle"></i> Create Promotion
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Promotion Modal -->
<div class="modal fade" id="editPromoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil-square"></i> Edit Promotion
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editPromoForm">
                    <input type="hidden" id="editPromoId">
                    <div class="mb-3">
                        <label class="form-label">Promotion Image</label>
                        <input type="file" class="form-control mb-2" id="editImageFile" accept="image/*" onchange="handleImageUpload(this, 'editImageUrl', 'editImagePreview')">
                        <div id="editImagePreview" class="mt-2 d-none text-center">
                            <img src="" class="img-fluid rounded border" style="max-height: 200px">
                        </div>
                        <small class="text-muted d-block mt-1">Upload new image to replace current one</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" id="editTitle" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" id="editDescription" rows="4" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-eds-primary" onclick="updatePromotion()">
                    <i class="bi bi-check-circle"></i> Update Promotion
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentPromotions = [];

// Load all promotions
async function loadPromotions() {
    try {
        const data = await apiRequest(ADMIN_API_BASE + '/get_all_promotions.php', {
            body: { limit: 100, offset: 0 }
        });
        
        if (data.success) {
            currentPromotions = data.data;
            displayPromotions(data.data);
        }
    } catch (error) {
        document.getElementById('promotionsGrid').innerHTML = `
            <div class="col-12">
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i> Failed to load promotions: ${error.message}
                </div>
            </div>
        `;
    }
}

// Display promotions in grid
function displayPromotions(promotions) {
    const grid = document.getElementById('promotionsGrid');
    
    if (promotions.length === 0) {
        grid.innerHTML = `
            <div class="col-12 text-center py-5">
                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                <p class="text-muted mt-3">No promotions yet. Click "Add Promotion" to create one.</p>
            </div>
        `;
        return;
    }
    
    grid.innerHTML = promotions.map(promo => {
        // API now returns presigned URLs directly
        const imageUrl = promo.image_url;
            
        return `
        <div class="col-md-4">
            <div class="card promo-card">
                <img src="${imageUrl}" alt="Promotion" onerror="this.src='https://via.placeholder.com/400x200?text=Image+Not+Found'">
                <div class="card-body">
                    ${promo.title ? `<h6 class="card-title">${truncateText(promo.title, 50)}</h6>` : ''}
                    <p class="card-text">${truncateText(promo.description, 100)}</p>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <small class="text-muted">
                            <i class="bi bi-person-circle"></i> ${promo.user.email || 'System'}
                        </small>
                        <small class="text-muted">
                            <i class="bi bi-clock"></i> ${formatDate(promo.created_at)}
                        </small>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-sm btn-outline-primary" onclick="openEditModal('${promo.id}')">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deletePromotion('${promo.id}')">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `}).join('');
}

// Add new promotion
async function addPromotion() {
    const title = document.getElementById('title').value.trim();
    const description = document.getElementById('description').value.trim();
    
    if (!title || !description) {
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
        const s3Key = await uploadFile(pendingImageFile, 'promotions');
        
        // Then create promotion with S3 key
        const data = await apiRequest(API_BASE + '/add_promotion.php', {
            body: { image_url: s3Key, title: title, description: description }
        });
        
        if (data.success) {
            showToast('Promotion created successfully!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('addPromoModal')).hide();
            document.getElementById('addPromoForm').reset();
            resetUploadPreview('imagePreview');
            loadPromotions();
        }
    } catch (error) {
        showToast('Failed to create promotion: ' + error.message, 'danger');
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
function openEditModal(promoId) {
    const promo = currentPromotions.find(p => p.id === promoId);
    if (!promo) return;
    
    document.getElementById('editPromoId').value = promo.id;
    document.getElementById('editTitle').value = promo.title || '';
    document.getElementById('editDescription').value = promo.description;
    
    // Clear pending edit file
    pendingEditImageFile = null;
    document.getElementById('editImageFile').value = '';
    
    // Show current image preview using presigned URL
    const imageUrl = promo.image_url;
    
    const preview = document.getElementById('editImagePreview');
    preview.classList.remove('d-none');
    preview.querySelector('img').src = imageUrl;
    
    new bootstrap.Modal(document.getElementById('editPromoModal')).show();
}

// Update promotion
async function updatePromotion() {
    const promoId = document.getElementById('editPromoId').value;
    const title = document.getElementById('editTitle').value.trim();
    const description = document.getElementById('editDescription').value.trim();
    
    if (!title || !description) {
        showToast('Title and description are required', 'warning');
        return;
    }
    
    try {
        const requestBody = { promotionId: promoId, title: title, description: description };
        
        // If a new image was selected, upload it to S3 first
        if (pendingEditImageFile) {
            showToast('Uploading new image...', 'info');
            const s3Key = await uploadFile(pendingEditImageFile, 'promotions');
            requestBody.image_url = s3Key;
        }
        
        const data = await apiRequest(ADMIN_API_BASE + '/update_promotion.php', {
            body: requestBody
        });
        
        if (data.success) {
            showToast('Promotion updated successfully!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('editPromoModal')).hide();
            loadPromotions();
        }
    } catch (error) {
        showToast('Failed to update promotion: ' + error.message, 'danger');
    }
}

// Delete promotion
async function deletePromotion(promoId) {
    if (!confirmAction('Are you sure you want to delete this promotion? This cannot be undone.')) return;
    
    try {
        const data = await apiRequest(ADMIN_API_BASE + '/delete_promotion.php', {
            body: { promotionId: promoId }
        });
        
        if (data.success) {
            showToast('Promotion deleted successfully!', 'success');
            loadPromotions();
        }
    } catch (error) {
        showToast('Failed to delete promotion: ' + error.message, 'danger');
    }
}

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

// Load promotions on page load
document.addEventListener('DOMContentLoaded', loadPromotions);
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
