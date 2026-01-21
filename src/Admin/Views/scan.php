<?php
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle = 'Scan QR Code';
$currentPage = 'scan';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-qr-code-scan"></i> Scan User QR Code</h2>
        <p class="text-muted">Scan a user's QR code to activate their account instantly.</p>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card">
            <div class="card-body p-0">
                <style>
                    #reader {
                        border-radius: 4px;
                        overflow: hidden;
                    }
                    #reader__scan_region {
                        background: #f8f9fa;
                    }
                    #reader__dashboard_section_csr span {
                        display: block;
                        margin-bottom: 10px;
                        color: #666;
                    }
                    #reader button {
                        display: inline-block;
                        font-weight: 400;
                        line-height: 1.5;
                        color: #fff;
                        text-align: center;
                        text-decoration: none;
                        vertical-align: middle;
                        cursor: pointer;
                        -webkit-user-select: none;
                        -moz-user-select: none;
                        user-select: none;
                        background-color: #3F51B5 !important;
                        border: 1px solid #3F51B5 !important;
                        padding: 0.375rem 0.75rem;
                        font-size: 1rem;
                        border-radius: 0.25rem;
                        transition: color 0.15s, background-color 0.15s;
                        margin: 5px;
                    }
                    #reader button:hover {
                        background-color: #303F9F !important;
                        border-color: #303F9F !important;
                    }
                    #reader__dashboard_section_swaplink {
                        text-decoration: none !important;
                        color: #3F51B5 !important;
                        font-weight: 500;
                        display: inline-block;
                        margin-top: 10px;
                        padding: 5px 10px;
                        border: 1px solid #e0e0e0;
                        border-radius: 4px;
                        background: #f8f9fa;
                    }
                    #reader__camera_selection {
                        display: block;
                        width: 100%;
                        padding: 0.375rem 2.25rem 0.375rem 0.75rem;
                        -moz-padding-start: calc(0.75rem - 3px);
                        font-size: 1rem;
                        font-weight: 400;
                        line-height: 1.5;
                        color: #212529;
                        background-color: #fff;
                        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
                        background-repeat: no-repeat;
                        background-position: right 0.75rem center;
                        background-size: 16px 12px;
                        border: 1px solid #ced4da;
                        border-radius: 0.25rem;
                        margin-bottom: 10px;
                    }
                </style>
                <div id="reader" style="width: 100%; min-height: 300px; background: #f8f9fa;"></div>
            </div>
            <div class="card-footer text-center">
                <div id="scanResult" class="d-none">
                    <div class="alert alert-success mb-3">
                        <i class="bi bi-check-circle-fill"></i> QR Code Detected!
                    </div>
                    
                    <!-- User Details Section -->
                    <div id="userDetails" class="mb-4 text-start p-3 border rounded bg-light">
                        <div class="d-flex align-items-center mb-2">
                            <span class="text-muted me-2" style="width: 60px;">Name:</span>
                            <strong id="userName" class="fs-5">Loading...</strong>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <span class="text-muted me-2" style="width: 60px;">Email:</span>
                            <span id="userEmail">...</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="text-muted me-2" style="width: 60px;">ID:</span>
                            <code id="scannedUserId" class="text-secondary">...</code>
                        </div>
                        <div id="userStatusContainer" class="mt-2 text-center d-none">
                             <span id="userStatusBadge" class="badge"></span>
                        </div>
                    </div>

                    <button class="btn btn-eds-primary btn-lg w-100 mb-2" id="activateBtn">
                        <i class="bi bi-person-check"></i> Activate User
                    </button>
                    <button class="btn btn-outline-secondary w-100" onclick="resetScanner()">
                        <i class="bi bi-arrow-repeat"></i> Scan Another
                    </button>
                </div>
                <div id="scanStatus" class="text-muted py-2">
                    <i class="bi bi-camera"></i> Point camera at user's QR code
                </div>
            </div>
        </div>
    </div>
</div>

<!-- HTML5-QRCode Library -->
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

<script>
let html5QrcodeScanner = null;
let isScanning = true;

document.addEventListener('DOMContentLoaded', function() {
    startScanner();
});

function startScanner() {
    const config = { 
        fps: 10, 
        qrbox: { width: 250, height: 250 },
        aspectRatio: 1.0
    };
    
    html5QrcodeScanner = new Html5QrcodeScanner(
        "reader", config, /* verbose= */ false);
        
    html5QrcodeScanner.render(onScanSuccess, onScanFailure);
    
    document.getElementById('scanResult').classList.add('d-none');
    document.getElementById('scanStatus').classList.remove('d-none');
    isScanning = true;
}

function onScanSuccess(decodedText, decodedResult) {
    if (!isScanning) return;
    
    // Format: EDSAPP:USER:{UUID}
    if (decodedText.startsWith('EDSAPP:USER:')) {
        isScanning = false;
        const userId = decodedText.split('EDSAPP:USER:')[1];
        
        // Stop scanning
        html5QrcodeScanner.clear();
        
        // Show result
        document.getElementById('scanResult').classList.remove('d-none');
        document.getElementById('scanStatus').classList.add('d-none');
        document.getElementById('scannedUserId').textContent = userId;
        
        // Fetch User Details
        fetchUserDetails(userId);
        
        // Setup activate button
        document.getElementById('activateBtn').onclick = () => activateUser(userId);
        document.getElementById('activateBtn').disabled = false;
        document.getElementById('activateBtn').innerHTML = '<i class="bi bi-person-check"></i> Activate User';
    } else {
        showToast('Invalid QR Code format', 'warning');
    }
}

async function fetchUserDetails(userId) {
    try {
        const token = await getAuthToken(); // Ensure we have token
        // Fetch user details from backend using proper API routing
        const response = await fetch(`${ADMIN_API_BASE}/get_user_details.php?id=${userId}`, {
            headers: {
                'Authorization': `Bearer ${token}` // Middleware might verify session anyway
            }
        });
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('userName').textContent = data.user.name;
            document.getElementById('userEmail').textContent = data.user.email;
            
            // Show current status
            const statusBadge = document.getElementById('userStatusBadge');
            const statusContainer = document.getElementById('userStatusContainer');
            
            statusBadge.className = 'badge ' + (data.user.status === 'active' ? 'bg-success' : 'bg-warning');
            statusBadge.textContent = 'Current Status: ' + data.user.status.toUpperCase();
            statusContainer.classList.remove('d-none');
            
            // If already active, maybe disable activate button?
            if (data.user.status === 'active') {
                document.getElementById('activateBtn').disabled = true;
                document.getElementById('activateBtn').innerHTML = '<i class="bi bi-check-lg"></i> Already Active';
            }
        } else {
            showToast('User not found: ' + data.message, 'danger');
        }
    } catch (error) {
        console.error(error);
        showToast('Failed to fetch user details', 'danger');
    }
}

function onScanFailure(error) {
    // console.warn(`Code scan error = ${error}`);
}

async function activateUser(userId) {
    try {
        const data = await apiRequest(ADMIN_API_BASE + '/update_user_status.php', {
            body: { 
                userId: userId, 
                status: 'active' 
            }
        });
        
        if (data.success) {
            showToast('User activated successfully!', 'success');
            document.getElementById('activateBtn').disabled = true;
            document.getElementById('activateBtn').className = 'btn btn-success w-100 mb-2';
            document.getElementById('activateBtn').innerHTML = '<i class="bi bi-check-lg"></i> User Activated';
            
            // Update status badge
            const statusBadge = document.getElementById('userStatusBadge');
            statusBadge.className = 'badge bg-success';
            statusBadge.textContent = 'Current Status: ACTIVE';
        }
    } catch (error) {
        showToast('Failed to activate user: ' + error.message, 'danger');
    }
}

function resetScanner() {
    document.getElementById('scanResult').classList.add('d-none');
    document.getElementById('userName').textContent = 'Loading...';
    document.getElementById('userEmail').textContent = '...';
    document.getElementById('userStatusContainer').classList.add('d-none');
    
    startScanner();
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
