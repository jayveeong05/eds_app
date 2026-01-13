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

<!-- Management Modules -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <h5 class="text-secondary border-bottom pb-2">
            <i class="bi bi-grid-fill me-2"></i>Management Modules
        </h5>
    </div>

    <!-- Users -->
    <div class="col-md-4 col-lg-3">
        <a href="users.php" class="text-decoration-none">
            <div class="card h-100 hover-shadow">
                <div class="card-body text-center py-4">
                    <div class="display-5 text-primary mb-3"><i class="bi bi-people"></i></div>
                    <h6 class="card-title text-dark">User Management</h6>
                    <small class="text-muted">Manage app users & approvals</small>
                </div>
            </div>
        </a>
    </div>

    <!-- Invoices -->
    <div class="col-md-4 col-lg-3">
        <a href="invoices.php" class="text-decoration-none">
            <div class="card h-100 hover-shadow">
                <div class="card-body text-center py-4">
                    <div class="display-5 text-success mb-3"><i class="bi bi-receipt-cutoff"></i></div>
                    <h6 class="card-title text-dark">Invoices</h6>
                    <small class="text-muted">Upload & view invoices</small>
                </div>
            </div>
        </a>
    </div>

    <!-- Promotions -->
    <div class="col-md-4 col-lg-3">
        <a href="promotions.php" class="text-decoration-none">
            <div class="card h-100 hover-shadow">
                <div class="card-body text-center py-4">
                    <div class="display-5 text-warning mb-3"><i class="bi bi-megaphone"></i></div>
                    <h6 class="card-title text-dark">Promotions</h6>
                    <small class="text-muted">Manage banners & ads</small>
                </div>
            </div>
        </a>
    </div>

    <!-- News -->
    <div class="col-md-4 col-lg-3">
        <a href="news.php" class="text-decoration-none">
            <div class="card h-100 hover-shadow">
                <div class="card-body text-center py-4">
                    <div class="display-5 text-info mb-3"><i class="bi bi-newspaper"></i></div>
                    <h6 class="card-title text-dark">News & Updates</h6>
                    <small class="text-muted">Post company news</small>
                </div>
            </div>
        </a>
    </div>

    <!-- Knowledge Base -->
    <div class="col-md-4 col-lg-3">
        <a href="knowledge_base.php" class="text-decoration-none">
            <div class="card h-100 hover-shadow">
                <div class="card-body text-center py-4">
                    <div class="display-5 text-danger mb-3"><i class="bi bi-book"></i></div>
                    <h6 class="card-title text-dark">Knowledge Base</h6>
                    <small class="text-muted">Manage FAQ & Guides</small>
                </div>
            </div>
        </a>
    </div>

    <!-- Printer Requests -->
    <div class="col-md-4 col-lg-3">
        <a href="printer_requests.php" class="text-decoration-none">
            <div class="card h-100 hover-shadow">
                <div class="card-body text-center py-4">
                    <div class="display-5 text-dark mb-3"><i class="bi bi-printer"></i></div>
                    <h6 class="card-title text-dark">Printer Requests</h6>
                    <small class="text-muted">Printer chat leads</small>
                </div>
            </div>
        </a>
    </div>

    <!-- Scan QR -->
    <div class="col-md-4 col-lg-3">
        <a href="scan.php" class="text-decoration-none">
            <div class="card h-100 hover-shadow">
                <div class="card-body text-center py-4">
                    <div class="display-5 text-secondary mb-3"><i class="bi bi-qr-code-scan"></i></div>
                    <h6 class="card-title text-dark">Scan QR</h6>
                    <small class="text-muted">Quick user lookup</small>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Recent Activity -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-light">
                <i class="bi bi-clock-history me-2"></i> System Activity
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                                <i class="bi bi-person-plus text-primary h4 mb-0"></i>
                            </div>
                            <div>
                                <h5 class="mb-0" id="recentRegs">0</h5>
                                <small class="text-muted">New registrations this week</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0 border-start border-end">
                        <div class="d-flex align-items-center justify-content-center">
                            <div class="bg-success bg-opacity-10 p-3 rounded-circle me-3">
                                <i class="bi bi-shield-check text-success h4 mb-0"></i>
                            </div>
                            <div>
                                <h5 class="mb-0" id="totalAdmins">0</h5>
                                <small class="text-muted">Active Administrators</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center justify-content-end">
                            <div class="text-end">
                                <small class="text-muted d-block">Last Updated</small>
                                <span class="badge bg-secondary" id="lastUpdate">Just now</span>
                            </div>
                        </div>
                    </div>
                </div>
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
