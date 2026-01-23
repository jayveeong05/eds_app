// Admin Authentication Helper
// Manages JWT token-based authentication in sessionStorage

const AdminAuth = {
    // Check if user is authenticated
    isAuthenticated() {
        const token = sessionStorage.getItem('adminToken');
        const email = sessionStorage.getItem('adminEmail');
        return !!(token && email);
    },

    // Get stored admin data
    getAdmin() {
        return {
            token: sessionStorage.getItem('adminToken'),
            email: sessionStorage.getItem('adminEmail'),
            name: sessionStorage.getItem('adminName')
        };
    },

    // Store admin session
    setAdmin(token, email, name) {
        sessionStorage.setItem('adminToken', token);
        sessionStorage.setItem('adminEmail', email);
        if (name) sessionStorage.setItem('adminName', name);
    },

    // Clear admin session
    clearAdmin() {
        sessionStorage.removeItem('adminToken');
        sessionStorage.removeItem('adminEmail');
        sessionStorage.removeItem('adminName');
    },

    // Get auth headers for API calls
    getAuthHeaders() {
        const token = sessionStorage.getItem('adminToken');
        return {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
        };
    },

    // Logout and redirect
    logout() {
        sessionStorage.clear();
        window.location.href = '/admin/';
    },

    // Redirect to login page if not already there
    redirectToLogin() {
        if (!this.isOnLoginPage()) {
            window.location.href = '/admin/?timeout=1';
        }
    },

    // Check if the current page is the login page
    isOnLoginPage() {
        const path = window.location.pathname;
        // Check if we're on the login page (either /admin/ or /admin/index.php)
        return path === '/admin/' || path === '/admin/index.php';
    },

    // Protect page - redirect to login if not authenticated
    requireAuth() {
        if (!this.isAuthenticated()) {
            this.redirectToLogin();
        }
    }
};

// Auto-protect admin pages (except login page)
// Wait for DOM to ensure sessionStorage is accessible
document.addEventListener('DOMContentLoaded', () => {
    // Don't protect login page
    if (AdminAuth.isOnLoginPage()) {
        return;
    }

    // For all other admin pages, require authentication
    if (window.location.pathname.startsWith('/admin/')) {
        AdminAuth.requireAuth();
    }
});
