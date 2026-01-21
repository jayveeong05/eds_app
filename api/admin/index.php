<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: /admin/dashboard.php');
    exit;
}

$error = '';
if (isset($_GET['timeout'])) {
    $error = 'Session expired. Please login again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - EDS App</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!--Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <link href="/admin/assets/style.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-card card">
            <div class="card-body p-5">
                <!-- Logo -->
                <div class="login-logo">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                
                <!-- Title -->
                <h2 class="text-center mb-2">Admin Panel</h2>
                <p class="text-center text-muted mb-4">EDS App Management</p>
                
                <!-- Error Message -->
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <div id="errorMessage" class="alert alert-danger d-none" role="alert"></div>
                
                <!-- Login Instructions -->
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>Login with your admin account</strong><br>
                    Use your Firebase email/password credentials. Email: <code>admin@email.com</code>
                </div>
                
                <!-- Login Form -->
                <form id="loginForm">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" placeholder="admin@example.com" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" placeholder="Enter password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-eds-primary w-100 py-2" id="loginBtn">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </button>
                </form>
                
                <!-- Footer -->
                <div class="text-center mt-4">
                    <small class="text-muted">© 2025 EDS App. Admin Access Only.</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div id="loadingSpinner" class="spinner-overlay">
        <div class="spinner-border text-light" style="width: 3rem; height: 3rem;"></div>
    </div>

    <!-- Firebase SDK -->
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-auth-compat.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Firebase Config (from google-services.json)
        const firebaseConfig = {
            apiKey: "AIzaSyCN1gcyoMxhlLe-lMSbWXLWoEX1di9DYZY",
            authDomain: "eds-app-1758d.firebaseapp.com",
            projectId: "eds-app-1758d",
            storageBucket: "eds-app-1758d.firebasestorage.app",
            messagingSenderId: "937666435898",
            appId: "1:937666435898:android:ba01066b47f5eae069ce03"
        };
        
        firebase.initializeApp(firebaseConfig);
        
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        const errorMessage = document.getElementById('errorMessage');
        const loadingSpinner = document.getElementById('loadingSpinner');
        
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            loginBtn.disabled = true;
            loginBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Logging in...';
            errorMessage.classList.add('d-none');
            
            try {
                // Sign in with Firebase
                const userCredential = await firebase.auth().signInWithEmailAndPassword(email, password);
                const user = userCredential.user;
                const idToken = await user.getIdToken();
                
                // Verify admin access with backend
                loadingSpinner.classList.add('show');
                const response = await fetch('/api/verify_admin.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ idToken })
                });
                
                const data = await response.json();
                loadingSpinner.classList.remove('show');
                
                if (data.success && data.admin.role === 'admin') {
                    // Store session data in sessionStorage (JWT-based auth)
                    sessionStorage.setItem('adminToken', idToken);
                    sessionStorage.setItem('adminEmail', data.admin.email);
                    sessionStorage.setItem('adminName', data.admin.name || 'Admin');
                    
                    // Redirect directly to dashboard (no PHP session needed)
                    window.location.href = '/admin/dashboard.php';
                } else {
                    throw new Error(data.message || 'Admin access denied. You need admin privileges.');
                }
                
            } catch (error) {
                errorMessage.textContent = error.message;
                errorMessage.classList.remove('d-none');
                loginBtn.disabled = false;
                loginBtn.innerHTML = '<i class="bi bi-box-arrow-in-right"></i> Login';
            }
        });
    </script>
</body>
</html>
