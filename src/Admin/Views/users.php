<?php
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle = 'User Management';
$currentPage = 'users';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-people"></i> User Management</h2>
        <p class="text-muted">Manage user accounts and permissions</p>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <input type="text" class="form-control" id="searchInput" placeholder="Search by email or name...">
            </div>
            <div class="col-md-3">
                <select class="form-select" id="statusFilter">
                    <option value="all">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="roleFilter">
                    <option value="all">All Roles</option>
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-eds-primary w-100" onclick="loadUsers()">
                    <i class="bi bi-search"></i> Search
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-table"></i> Users List</span>
        <span class="badge bg-secondary" id="userCount">0 users</span>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-custom table-hover">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Login Method</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="bi bi-hourglass-split"></i> Loading users...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Machine Codes Management Modal -->
<div class="modal fade" id="machineCodesModal" tabindex="-1" aria-labelledby="machineCodesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-eds-primary text-white">
                <h5 class="modal-title" id="machineCodesModalLabel">
                    <i class="bi bi-gear"></i> Manage Machine Codes
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Managing codes for: <strong id="modalUserName"></strong></p>
                
                <!-- Add Machine Code Section -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bi bi-plus-circle"></i> Add Machine Code</h6>
                    </div>
                    <div class="card-body">
                        <label class="form-label">Search or enter machine code:</label>
                        <div class="position-relative">
                            <input type="text" class="form-control" id="codeSearchInput" 
                                   placeholder="Type to search or enter code (e.g., AA001001)" 
                                   maxlength="8" autocomplete="off">
                            <div id="codeSuggestions" class="list-group position-absolute w-100 shadow-sm border rounded mt-1" 
                                 style="z-index: 1000; display: none; max-height: 200px; overflow-y: auto; background: white;"></div>
                        </div>
                        <small class="text-muted">Format: 2 uppercase letters + 6 digits (e.g., AA001001)</small>
                        <div class="mt-3">
                            <button class="btn btn-success" onclick="addMachineCode()">
                                <i class="bi bi-plus"></i> Add Code
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Assigned Machine Codes Table -->
                <div class="card">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bi bi-list-ul"></i> Assigned Machine Codes</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Machine Code</th>
                                        <th>Assigned At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="machineCodesTableBody">
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">
                                            <i class="bi bi-hourglass-split"></i> Loading...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentUsers = [];
let currentUserId = null;
let allAvailableCodes = [];
let userMachineCodes = [];

// Load users from API
async function loadUsers() {
    const search = document.getElementById('searchInput').value;
    const statusFilter = document.getElementById('statusFilter').value;
    const roleFilter = document.getElementById('roleFilter').value;
    
    try {
        const data = await apiRequest(ADMIN_API_BASE + '/get_all_users.php', {
            body: {
                search: search,
                status: statusFilter,
                role: roleFilter,
                limit: 100,
                offset: 0
            }
        });
        
        if (data.success) {
            currentUsers = data.data;
            displayUsers(data.data);
            document.getElementById('userCount').textContent = data.total + ' users';
        }
    } catch (error) {
        document.getElementById('usersTableBody').innerHTML = `
            <tr>
                <td colspan="8" class="text-center text-danger py-4">
                    <i class="bi bi-exclamation-triangle"></i> ${error.message}
                </td>
            </tr>
        `;
    }
}

// Display users in table
function displayUsers(users) {
    const tbody = document.getElementById('usersTableBody');
    
    if (users.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center text-muted py-4">
                    <i class="bi bi-inbox"></i> No users found
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = users.map(user => `
        <tr>
            <td>
                <i class="bi bi-envelope me-1"></i>
                ${user.email}
            </td>
            <td>${user.name || '-'}</td>
            <td>
                <span class="badge ${user.role === 'admin' ? 'badge-admin' : 'badge-user'}">
                    ${user.role}
                </span>
            </td>
            <td>
                <span class="badge ${user.status === 'active' ? 'badge-active' : 'badge-inactive'}">
                    ${user.status}
                </span>
            </td>
            <td>
                <i class="bi bi-${getLoginIcon(user.login_method)} me-1"></i>
                ${user.login_method || 'email'}
            </td>
            <td>${formatDate(user.created_at)}</td>
            <td>
                <div class="btn-group btn-group-sm" role="group">
                    ${user.status === 'active' 
                        ? `<button class="btn btn-warning" onclick="updateUserStatus('${user.id}', 'inactive')" title="Deactivate">
                            <i class="bi bi-x-circle"></i>
                           </button>`
                        : `<button class="btn btn-success" onclick="updateUserStatus('${user.id}', 'active')" title="Activate">
                            <i class="bi bi-check-circle"></i>
                           </button>`
                    }
                    ${user.role === 'user'
                        ? `<button class="btn btn-info" onclick="updateUserRole('${user.id}', 'admin')" title="Promote to Admin">
                            <i class="bi bi-shield-fill-check"></i>
                           </button>`
                        : `<button class="btn btn-secondary" onclick="updateUserRole('${user.id}', 'user')" title="Demote to User">
                            <i class="bi bi-person"></i>
                           </button>`
                    }
                    <button class="btn btn-danger" onclick="deleteUser('${user.id}', '${user.email}')" title="Delete">
                        <i class="bi bi-trash"></i>
                    </button>
                    <button class="btn btn-primary" onclick="openMachineCodesModal('${user.id}', '${user.name || user.email}')" title="Manage Machine Codes">
                        <i class="bi bi-gear"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Get login method icon
function getLoginIcon(method) {
    const icons = {
        'email': 'envelope',
        'google': 'google',
        'apple': 'apple'
    };
    return icons[method] || 'envelope';
}

// Update user status
async function updateUserStatus(userId, newStatus) {
    const action = newStatus === 'active' ? 'activate' : 'deactivate';
    if (!confirmAction(`Are you sure you want to ${action} this user?`)) return;
    
    try {
        const data = await apiRequest(ADMIN_API_BASE + '/update_user_status.php', {
            body: { userId, status: newStatus }
        });
        
        if (data.success) {
            showToast(`User ${action}d successfully`, 'success');
            loadUsers();
        }
    } catch (error) {
        showToast('Failed to update status: ' + error.message, 'danger');
    }
}

// Update user role
async function updateUserRole(userId, newRole) {
    const action = newRole === 'admin' ? 'promote to admin' : 'demote to user';
    if (!confirmAction(`Are you sure you want to ${action}?`)) return;
    
    try {
        const data = await apiRequest(ADMIN_API_BASE + '/update_user_role.php', {
            body: { userId, role: newRole }
        });
        
        if (data.success) {
            showToast(`User role updated successfully`, 'success');
            loadUsers();
        }
    } catch (error) {
        showToast('Failed to update role: ' + error.message, 'danger');
    }
}

// Delete user
async function deleteUser(userId, email) {
    if (!confirmAction(`Are you sure you want to delete ${email}? This will deactivate their account.`)) return;
    
    try {
        const data = await apiRequest(ADMIN_API_BASE + '/delete_user.php', {
            body: { userId }
        });
        
        if (data.success) {
            showToast('User deleted successfully', 'success');
            loadUsers();
        }
    } catch (error) {
        showToast('Failed to delete user: ' + error.message, 'danger');
    }
}

// Load users on page load
document.addEventListener('DOMContentLoaded', loadUsers);

// Search on Enter key
document.getElementById('searchInput').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') loadUsers();
});

// Machine Codes Management Functions
async function openMachineCodesModal(userId, userName) {
    currentUserId = userId;
    document.getElementById('modalUserName').textContent = userName;
    
    // Clear search input and hide suggestions
    const searchInput = document.getElementById('codeSearchInput');
    if (searchInput) {
        searchInput.value = '';
        hideCodeSuggestions();
    }
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('machineCodesModal'));
    modal.show();
    
    // Load available codes and user's assigned codes
    await Promise.all([
        loadAllMachineCodes(),
        loadUserMachineCodes(userId)
    ]);
}

async function loadAllMachineCodes() {
    try {
        const data = await apiRequest(ADMIN_API_BASE + '/get_all_machine_codes.php', {
            body: {}
        });
        
        if (data.success) {
            allAvailableCodes = data.data || [];
            // Codes are now available for search/autocomplete
        }
    } catch (error) {
        console.error('Failed to load machine codes:', error);
        showToast('Failed to load available machine codes', 'warning');
    }
}

async function loadUserMachineCodes(userId) {
    try {
        const data = await apiRequest(ADMIN_API_BASE + '/get_user_machine_codes.php', {
            body: { userId: userId }
        });
        
        if (data.success) {
            userMachineCodes = data.data || [];
            displayUserMachineCodes();
        }
    } catch (error) {
        console.error('Failed to load user machine codes:', error);
        showToast('Failed to load user machine codes', 'danger');
        document.getElementById('machineCodesTableBody').innerHTML = `
            <tr>
                <td colspan="3" class="text-center text-danger py-3">
                    <i class="bi bi-exclamation-triangle"></i> Failed to load codes
                </td>
            </tr>
        `;
    }
}

function displayUserMachineCodes() {
    const tbody = document.getElementById('machineCodesTableBody');
    
    if (userMachineCodes.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="3" class="text-center text-muted py-3">
                    <i class="bi bi-inbox"></i> No machine codes assigned
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = userMachineCodes.map(item => `
        <tr>
            <td><code>${item.code}</code></td>
            <td>${formatDate(item.assigned_at)}</td>
            <td>
                <button class="btn btn-sm btn-danger" onclick="removeMachineCode('${item.code}')" title="Remove">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

async function addMachineCode() {
    if (!currentUserId) return;
    
    const input = document.getElementById('codeSearchInput');
    let code = input.value.trim().toUpperCase();
    
    if (!code) {
        showToast('Please enter a machine code', 'warning');
        return;
    }
    
    // Validate format: 2 uppercase letters + 6 digits
    if (!/^[A-Z]{2}[0-9]{6}$/.test(code)) {
        showToast('Invalid machine code format. Expected: 2 uppercase letters + 6 digits (e.g., AA001001)', 'danger');
        return;
    }
    
    // Check if already assigned
    if (userMachineCodes.some(item => item.code === code)) {
        showToast('This machine code is already assigned to this user', 'warning');
        return;
    }
    
    try {
        const data = await apiRequest(ADMIN_API_BASE + '/add_user_machine_code.php', {
            body: {
                userId: currentUserId,
                code: code
            }
        });
        
        if (data.success) {
            showToast('Machine code added successfully', 'success');
            // Clear input and hide suggestions
            input.value = '';
            hideCodeSuggestions();
            // Reload user's machine codes
            await loadUserMachineCodes(currentUserId);
        }
    } catch (error) {
        showToast('Failed to add machine code: ' + error.message, 'danger');
    }
}

function showCodeSuggestions(searchTerm) {
    const suggestionsDiv = document.getElementById('codeSuggestions');
    const upperTerm = (searchTerm || '').toUpperCase();
    
    // If no search term, show all available codes (limited to 20 for performance)
    let filtered;
    if (!upperTerm || upperTerm.length === 0) {
        filtered = allAvailableCodes.slice(0, 20);
    } else {
        // Filter codes that match the search term
        filtered = allAvailableCodes.filter(code => 
            code.toUpperCase().includes(upperTerm)
        ).slice(0, 20);
    }
    
    // If no matches found and user has typed something, check if it's a valid new code
    if (filtered.length === 0 && upperTerm.length > 0) {
        // Show option to add new code if format is valid
        if (/^[A-Z]{2}[0-9]{0,6}$/.test(upperTerm)) {
            suggestionsDiv.innerHTML = `
                <a href="#" class="list-group-item list-group-item-action" onclick="selectCode('${upperTerm}'); return false;">
                    <i class="bi bi-plus-circle me-2"></i> Add new code: <strong>${upperTerm}</strong>
                </a>
            `;
            suggestionsDiv.style.display = 'block';
        } else {
            hideCodeSuggestions();
        }
        return;
    }
    
    // Show filtered or all codes
    if (filtered.length === 0) {
        hideCodeSuggestions();
        return;
    }
    
    suggestionsDiv.innerHTML = filtered.map(code => {
        const isAssigned = userMachineCodes.some(item => item.code === code);
        const disabledClass = isAssigned ? 'disabled text-muted' : '';
        const icon = isAssigned ? 'bi-check-circle' : 'bi-circle';
        return `
            <a href="#" class="list-group-item list-group-item-action ${disabledClass}" 
               onclick="selectCode('${code}'); return false;" ${isAssigned ? 'title="Already assigned"' : ''}>
                <i class="bi ${icon} me-2"></i> ${code} ${isAssigned ? '<small>(assigned)</small>' : ''}
            </a>
        `;
    }).join('');
    suggestionsDiv.style.display = 'block';
}

function hideCodeSuggestions() {
    const suggestionsDiv = document.getElementById('codeSuggestions');
    suggestionsDiv.style.display = 'none';
}

function selectCode(code) {
    const input = document.getElementById('codeSearchInput');
    input.value = code;
    hideCodeSuggestions();
    // Auto-uppercase and format as user types
    input.value = code.toUpperCase();
}

async function removeMachineCode(code) {
    if (!currentUserId) return;
    
    if (!confirmAction(`Are you sure you want to remove machine code ${code} from this user?`)) {
        return;
    }
    
    try {
        const data = await apiRequest(ADMIN_API_BASE + '/delete_user_machine_code.php', {
            body: {
                userId: currentUserId,
                code: code
            }
        });
        
        if (data.success) {
            showToast('Machine code removed successfully', 'success');
            // Reload user's machine codes
            await loadUserMachineCodes(currentUserId);
        }
    } catch (error) {
        showToast('Failed to remove machine code: ' + error.message, 'danger');
    }
}

// Setup search input with autocomplete
document.addEventListener('DOMContentLoaded', () => {
    const codeSearchInput = document.getElementById('codeSearchInput');
    if (codeSearchInput) {
        // Show all codes when input is focused/clicked
        codeSearchInput.addEventListener('focus', (e) => {
            if (allAvailableCodes.length > 0) {
                showCodeSuggestions(e.target.value || '');
            }
        });
        
        // Auto-uppercase as user types
        codeSearchInput.addEventListener('input', (e) => {
            let value = e.target.value.toUpperCase();
            // Only allow letters and numbers, max 8 chars
            value = value.replace(/[^A-Z0-9]/g, '').substring(0, 8);
            e.target.value = value;
            
            // Show suggestions (all if empty, filtered if has value)
            showCodeSuggestions(value);
        });
        
        // Handle Enter key
        codeSearchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                hideCodeSuggestions();
                addMachineCode();
            } else if (e.key === 'Escape') {
                hideCodeSuggestions();
            }
        });
        
        // Hide suggestions when clicking outside
        const searchContainer = codeSearchInput.parentElement;
        document.addEventListener('click', (e) => {
            if (!searchContainer.contains(e.target)) {
                hideCodeSuggestions();
            }
        });
    }
});

// Helper: Format date
function formatDate(dateStr) {
    if (!dateStr) return '-';
    const date = new Date(dateStr);
    const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return date.toLocaleDateString('en-US', options);
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
