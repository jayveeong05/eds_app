<?php
require_once 'includes/auth_check.php';
$pageTitle = 'User Management';
$currentPage = 'users';
?>
<?php include 'includes/header.php'; ?>

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
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="bi bi-hourglass-split"></i> Loading users...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
let currentUsers = [];

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
                <td colspan="7" class="text-center text-danger py-4">
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
                <td colspan="7" class="text-center text-muted py-4">
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
</script>

<?php include 'includes/footer.php'; ?>
