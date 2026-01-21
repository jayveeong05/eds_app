<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Admin Panel'; ?> - EDS App</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link href="/assets/style.css" rel="stylesheet">
    
    <!-- Auth Helper -->
    <script src="/assets/auth.js"></script>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/admin/dashboard.php">
                <i class="bi bi-shield-check"></i> EDS Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage == 'dashboard') ? 'active' : ''; ?>" href="/admin/dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage == 'scan') ? 'active' : ''; ?>" href="/admin/scan.php">
                            <i class="bi bi-qr-code-scan"></i> Scan QR
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage == 'users') ? 'active' : ''; ?>" href="/admin/users.php">
                            <i class="bi bi-people"></i> Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage == 'promotions') ? 'active' : ''; ?>" href="/admin/promotions.php">
                            <i class="bi bi-megaphone"></i> Promotions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage == 'news') ? 'active' : ''; ?>" href="/admin/news.php">
                            <i class="bi bi-newspaper"></i> News
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage == 'invoices') ? 'active' : ''; ?>" href="/admin/invoices.php">
                            <i class="bi bi-receipt-cutoff"></i> Invoices
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage == 'knowledge_base') ? 'active' : ''; ?>" href="/admin/knowledge_base.php">
                            <i class="bi bi-book"></i> Knowledge Base
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage == 'printer_requests') ? 'active' : ''; ?>" href="/admin/printer_requests.php">
                            <i class="bi bi-printer"></i> Printer Requests
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> 
                            <span id="adminEmailDisplay">Admin</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" onclick="AdminAuth.logout(); return false;">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
                
                <script>
                // Display admin email from sessionStorage
                document.addEventListener('DOMContentLoaded', () => {
                    const email = sessionStorage.getItem('adminEmail');
                    if (email) {
                        document.getElementById('adminEmailDisplay').textContent = email;
                    }
                });
                </script>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid py-4">
