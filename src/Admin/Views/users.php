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
                    <option value="deleted">Deleted</option>
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

<!-- Dropdown Menu Container (outside table for proper positioning) -->
<div id="dropdownMenuContainer" style="position: fixed; z-index: 1055; display: none;"></div>

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

<!-- Edit User Name Modal -->
<div class="modal fade" id="editNameModal" tabindex="-1" aria-labelledby="editNameModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-eds-primary text-white">
                <h5 class="modal-title" id="editNameModalLabel">
                    <i class="bi bi-pencil"></i> Edit User Name
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Editing name for: <strong id="editNameUserEmail"></strong></p>
                <div class="mb-3">
                    <label for="userNameInput" class="form-label">User Name</label>
                    <input type="text" class="form-control" id="userNameInput" placeholder="Enter user name">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-eds-primary" onclick="saveUserName()">
                    <i class="bi bi-check"></i> Save Changes
                </button>
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
                <span class="badge ${user.status === 'active' ? 'badge-active' : user.status === 'deleted' ? 'badge-danger' : 'badge-inactive'}">
                    ${user.status || 'unknown'}
                </span>
            </td>
            <td>
                <i class="bi bi-${getLoginIcon(user.login_method)} me-1"></i>
                ${user.login_method || 'email'}
            </td>
            <td>${formatDate(user.created_at)}</td>
            <td>
                <button class="btn btn-sm btn-outline-secondary" type="button" onclick="showUserActionsMenu(event, '${user.id}', '${user.email}', '${user.name || ''}', '${user.status}', '${user.role}', '${user.login_method}')">
                    <i class="bi bi-three-dots-vertical"></i>
                </button>
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
    if (!confirmAction(`Are you sure you want to delete ${email}? This will permanently mark their account as deleted.`)) return;
    
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

// Restore user
async function restoreUser(userId, email) {
    if (!confirmAction(`Are you sure you want to restore ${email}? The user will be set to inactive status.`)) return;
    
    try {
        const data = await apiRequest(ADMIN_API_BASE + '/restore_user.php', {
            body: { userId }
        });
        
        if (data.success) {
            showToast('User restored successfully. Status set to inactive.', 'success');
            loadUsers();
        }
    } catch (error) {
        showToast('Failed to restore user: ' + error.message, 'danger');
    }
}

// Edit User Name Functions
let editingUserId = null;

function openEditNameModal(userId, currentName, email) {
    editingUserId = userId;
    document.getElementById('editNameUserEmail').textContent = email;
    document.getElementById('userNameInput').value = currentName || '';
    
    const modal = new bootstrap.Modal(document.getElementById('editNameModal'));
    modal.show();
}

async function saveUserName() {
    if (!editingUserId) return;
    
    const newName = document.getElementById('userNameInput').value.trim();
    
    if (!newName) {
        showToast('Name cannot be empty', 'warning');
        return;
    }
    
    try {
        const data = await apiRequest(ADMIN_API_BASE + '/update_user_name.php', {
            body: {
                userId: editingUserId,
                name: newName
            }
        });
        
        if (data.success) {
            showToast('User name updated successfully', 'success');
            bootstrap.Modal.getInstance(document.getElementById('editNameModal')).hide();
            loadUsers();
        }
    } catch (error) {
        showToast('Failed to update user name: ' + error.message, 'danger');
    }
}

// Reset User Password
async function resetUserPassword(userId, email) {
    if (!confirmAction(`Send a password reset email to ${email}? The user will receive an email with instructions to reset their password.`)) return;
    
    try {
        const data = await apiRequest(ADMIN_API_BASE + '/reset_user_password.php', {
            body: { userId }
        });
        
        if (data.success) {
            showToast(data.message || 'Password reset email sent successfully', 'success');
        }
    } catch (error) {
        showToast('Failed to send password reset email: ' + error.message, 'danger');
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

// Show user actions menu (positioned outside table)
function showUserActionsMenu(event, userId, userEmail, userName, userStatus, userRole, loginMethod) {
    event.stopPropagation();
    
    const container = document.getElementById('dropdownMenuContainer');
    const button = event.target.closest('button');
    const buttonRect = button.getBoundingClientRect();
    
    // Build menu HTML
    let menuHtml = '<ul class="dropdown-menu dropdown-menu-end show" style="display: block; position: static;">';
    
    if (userStatus === 'deleted') {
        menuHtml += `
            <li><a class="dropdown-item" href="#" onclick="restoreUser('${userId}', '${userEmail}'); hideUserActionsMenu(); return false;">
                <i class="bi bi-arrow-counterclockwise me-2"></i> Restore User
            </a></li>`;
    } else {
        menuHtml += `
            <li><a class="dropdown-item" href="#" onclick="${userStatus === 'active' ? `updateUserStatus('${userId}', 'inactive')` : `updateUserStatus('${userId}', 'active')`}; hideUserActionsMenu(); return false;">
                <i class="bi ${userStatus === 'active' ? 'bi-x-circle' : 'bi-check-circle'} me-2"></i> ${userStatus === 'active' ? 'Deactivate' : 'Activate'}
            </a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="#" onclick="openEditNameModal('${userId}', '${userName}', '${userEmail}'); hideUserActionsMenu(); return false;">
                <i class="bi bi-pencil me-2"></i> Edit Name
            </a></li>
            ${loginMethod === 'email' 
                ? `<li><a class="dropdown-item" href="#" onclick="resetUserPassword('${userId}', '${userEmail}'); hideUserActionsMenu(); return false;">
                    <i class="bi bi-key me-2"></i> Reset Password
                </a></li>`
                : ''
            }
            <li><a class="dropdown-item" href="#" onclick="openMachineCodesModal('${userId}', '${userName || userEmail}'); hideUserActionsMenu(); return false;">
                <i class="bi bi-gear me-2"></i> Manage Machine Codes
            </a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="#" onclick="${userRole === 'user' ? `updateUserRole('${userId}', 'admin')` : `updateUserRole('${userId}', 'user')`}; hideUserActionsMenu(); return false;">
                <i class="bi ${userRole === 'user' ? 'bi-shield-fill-check' : 'bi-person'} me-2"></i> ${userRole === 'user' ? 'Promote to Admin' : 'Demote to User'}
            </a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="#" onclick="deleteUser('${userId}', '${userEmail}'); hideUserActionsMenu(); return false;">
                <i class="bi bi-trash me-2"></i> Delete User
            </a></li>`;
    }
    
    menuHtml += '</ul>';
    
    // Position menu - align to right edge of button
    container.innerHTML = menuHtml;
    container.style.display = 'block';
    
    // Calculate position (align dropdown to right edge of button)
    const menuWidth = 200; // Approximate menu width
    const leftPos = Math.max(10, buttonRect.right - menuWidth); // Ensure it doesn't go off-screen left
    const topPos = buttonRect.bottom + 5; // Small gap below button
    
    container.style.left = leftPos + 'px';
    container.style.top = topPos + 'px';
    
    // Adjust if menu would go off-screen right
    setTimeout(() => {
        const menuRect = container.getBoundingClientRect();
        if (menuRect.right > window.innerWidth) {
            container.style.left = (window.innerWidth - menuWidth - 10) + 'px';
        }
        // Adjust if menu would go off-screen bottom
        if (menuRect.bottom > window.innerHeight) {
            container.style.top = (buttonRect.top - menuRect.height - 5) + 'px';
        }
    }, 0);
    
    // Close menu when clicking outside
    setTimeout(() => {
        document.addEventListener('click', function closeMenu(e) {
            if (!container.contains(e.target) && !button.contains(e.target)) {
                hideUserActionsMenu();
                document.removeEventListener('click', closeMenu);
            }
        }, { once: true });
    }, 0);
}

function hideUserActionsMenu() {
    const container = document.getElementById('dropdownMenuContainer');
    container.style.display = 'none';
    container.innerHTML = '';
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
