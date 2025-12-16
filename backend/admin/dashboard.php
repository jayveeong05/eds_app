<?php
require_once 'includes/auth_check.php';
$pageTitle = 'Dashboard';
$currentPage = 'dashboard';
?>
<?php include 'includes/header.php'; ?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-speedometer2"></i> Dashboard</h2>
        <p class="text-muted">Overview of your EDS App</p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4" id="statsCards">
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="stats-label">Total Users</p>
                        <h3 class="stats-value" id="totalUsers">-</h3>
                    </div>
                    <div class="stats-icon blue">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="stats-label">Active Users</p>
                        <h3 class="stats-value" id="activeUsers">-</h3>
                    </div>
                    <div class="stats-icon green">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="stats-label">Pending Users</p>
                        <h3 class="stats-value" id="inactiveUsers">-</h3>
                    </div>
                    <div class="stats-icon orange">
                        <i class="bi bi-person-x-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="stats-label">Promotions</p>
                        <h3 class="stats-value" id="totalPromotions">-</h3>
                    </div>
                    <div class="stats-icon purple">
                        <i class="bi bi-megaphone-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-lightning-fill"></i> Quick Actions
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="users.php" class="btn btn-outline-primary">
                        <i class="bi bi-people"></i> Manage Users
                    </a>
                    <a href="promotions.php" class="btn btn-outline-primary">
                        <i class="bi bi-megaphone"></i> Manage Promotions
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-graph-up"></i> Recent Activity
            </div>
            <div class="card-body">
                <p class="text-muted mb-2">
                    <i class="bi bi-person-plus me-2"></i> 
                    <span id="recentRegs">0</span> new registrations this week
                </p>
                <p class="text-muted mb-2">
                    <i class="bi bi-shield-check me-2"></i> 
                    <span id="totalAdmins">0</span> administrators
                </p>
                <small class="text-muted">
                    <i class="bi bi-clock"></i> Last updated: <span id="lastUpdate">Now</span>
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Welcome Message -->
<div class="row">
    <div class="col">
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            <strong>Welcome to EDS Admin Panel!</strong> 
            You can manage users, promotions, and view analytics from here.
        </div>
    </div>
</div>

<script>
// Load dashboard statistics
async function loadDashboardStats() {
    try {
        const data = await apiRequest(ADMIN_API_BASE + '/get_dashboard_stats.php', {
            body: {}
        });
        
        if (data.success) {
            const stats = data.stats;
            document.getElementById('totalUsers').textContent = stats.total_users;
            document.getElementById('activeUsers').textContent = stats.active_users;
            document.getElementById('inactiveUsers').textContent = stats.inactive_users;
            document.getElementById('totalPromotions').textContent = stats.total_promotions;
            document.getElementById('recentRegs').textContent = stats.recent_registrations;
            document.getElementById('totalAdmins').textContent = stats.total_admins;
            
            // Update timestamp
            const now = new Date();
            document.getElementById('lastUpdate').textContent = now.toLocaleTimeString();
        }
    } catch (error) {
        showToast('Failed to load statistics: ' + error.message, 'danger');
    }
}

// Load stats on page load
document.addEventListener('DOMContentLoaded', loadDashboardStats);

// Auto-refresh every 30 seconds
setInterval(loadDashboardStats, 30000);
</script>

<?php include 'includes/footer.php'; ?>
