// EDS Admin Panel - JavaScript Utilities

// API Base URL
const API_BASE = window.location.origin + '/api';
const ADMIN_API_BASE = API_BASE + '/admin';

// Get Firebase token from PHP session
let cachedToken = null;

async function getAuthToken() {
    // 1. Check cached token variable
    if (cachedToken) {
        return cachedToken;
    }

    // 2. Check sessionStorage (Primary source for Vercel/Client-side auth)
    const sessionToken = sessionStorage.getItem('adminToken');
    if (sessionToken) {
        cachedToken = sessionToken;
        return cachedToken;
    }

    // 3. Fallback: Check PHP session (Legacy/Local dev only)
    try {
        const response = await fetch('get_token.php');
        const data = await response.json();

        if (data.success) {
            cachedToken = data.token;
            // Sync to sessionStorage for future use
            sessionStorage.setItem('adminToken', data.token);
            return cachedToken;
        }
    } catch (error) {
        console.warn('Legacy token fetch failed', error);
    }

    // If no token found, return null (caller handles redirect)
    return null;
}

// Show loading spinner
function showLoading() {
    document.getElementById('loadingSpinner')?.classList.add('show');
}

// Hide loading spinner
function hideLoading() {
    document.getElementById('loadingSpinner')?.classList.remove('show');
}

// Show toast notification
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) return;

    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;

    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();

    // Remove from DOM after hidden
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

// Confirm dialog
function confirmAction(message) {
    return confirm(message);
}

// Make API request with error handling
async function apiRequest(url, options = {}) {
    const token = await getAuthToken();

    if (!token) {
        throw new Error('Authentication required');
    }

    // Construct the payload with token + any data from options
    const payload = {
        idToken: token,
        ...(options.body || {})
    };

    // Create fetch options where we explicitly set the body
    const fetchOptions = {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            ...(options.headers || {})
        },
        // We do NOT spread ...options here blindly if it contains body
        // Instead, we manually copy other options if needed, but usually options only has body
        // Safe way: merge options but override body
        ...options,
        body: JSON.stringify(payload)
    };

    try {
        showLoading();
        const response = await fetch(url, fetchOptions);
        hideLoading();

        let data;
        const responseText = await response.text();

        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error("JSON Parse Error. Raw Response:", responseText);
            throw new Error(`Invalid server response (not JSON). Check console for details. Response start: ${responseText.substring(0, 50)}...`);
        }

        if (!response.ok) {
            throw new Error(data.message || 'Request failed');
        }

        return data;
    } catch (error) {
        hideLoading();
        throw error;
    }
}

// Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Truncate text
function truncateText(text, maxLength) {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + '...';
}

// Upload file to S3
async function uploadFile(file, folder = 'promotions') {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('folder', folder);

    try {
        showLoading();
        const response = await fetch(API_BASE + '/upload.php', {
            method: 'POST',
            body: formData
            // Note: Content-Type header is not set for FormData so browser can set boundary
        });

        const data = await response.json();
        hideLoading();

        if (response.ok) {
            return data.url;
        } else {
            throw new Error(data.message || 'Upload failed');
        }
    } catch (error) {
        hideLoading();
        console.error('Upload Error:', error);
        throw error;
    }
}

// Logout function
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = '/admin/logout.php';
    }
}
