<?php
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle = 'Printer Requests';
$currentPage = 'printer_requests';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="row mb-4">
    <div class="col">
        <h2><i class="bi bi-printer"></i> Printer Recommendation Requests</h2>
        <p class="text-muted">View and analyze anonymous printer matcher requests from mobile app</p>
    </div>
</div>

<!-- Knowledge Base Upload Section -->
<div class="row mb-4">
    <div class="col">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-cloud-upload"></i> Upload Printer Knowledge Base
            </div>
            <div class="card-body">
                <p class="mb-3">Upload a JSON file containing printer data to update the DigitalOcean Agent Knowledge Base.</p>
                
                <!-- Help Section -->
                <div class="accordion mb-3" id="helpAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#helpContent">
                                <i class="bi bi-question-circle me-2"></i> JSON Format Guide & Template
                            </button>
                        </h2>
                        <div id="helpContent" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                            <div class="accordion-body">
                                <h6><i class="bi bi-file-earmark-code"></i> Required JSON Structure:</h6>
                                <pre class="bg-light p-3 rounded" style="max-height: 400px; overflow-y: auto;"><code>{
  "printers": [
    {
      "brand": "Pantum",
      "series": "Utility 230/320 Series",
      "model": "BM230N",
      "type": "Mono",
      "functions": "Print/Copy/Scan",
      "performance": {
        "print_speed_color_ppm": "N/A",
        "print_speed_bw_ppm": "22",
        "first_print_time_color": "N/A",
        "first_print_time_bw": "<8.5"
      },
      "paper_handling": {
        "max_paper_size": "A3",
        "standard_capacity": "250-sheet tray",
        "max_capacity": "1450"
      },
      "scanning": {
        "scanner_type": "Flatbed + RADF (100-sheet)",
        "scan_speed_simplex": "30",
        "scan_speed_duplex": "N/A"
      },
      "specifications": {
        "print_resolution": "",
        "memory_gb": "1",
        "storage_gb": "N/A",
        "mobile_printing": "AirPrint, Mopria",
        "connectivity": "USB 2.0; Ethernet"
      },
      "energy": {
        "power_consumption_max_w": "<=550",
        "tec_kwh": "N/A"
      },
      "physical": {
        "dimensions_mm": "565 x 593 x 450",
        "weight_kg": "27.8"
      },
      "business": {
        "monthly_recommended_volume": "4000",
        "toner_yield_black": "12,000 pages",
        "toner_yield_color": "N/A",
        "optional_features": "Optional trays"
      },
      "targeting": {
        "target_office_size": "Small to medium-sized businesses",
        "use_case_notes": "Entry A3 mono MFP for SMB teams",
        "product_url": "https://eds.com/product/bm230n"
      }
    }
  ]
}</code></pre>
                                
                                <h6 class="mt-3"><i class="bi bi-list-check"></i> Field Descriptions:</h6>
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Section</th>
                                            <th>Field</th>
                                            <th>Required</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td rowspan="5"><strong>Basic Info</strong></td>
                                            <td><code>brand</code></td>
                                            <td><span class="badge bg-danger">Required</span></td>
                                            <td>Manufacturer (e.g., "Pantum", "Ricoh")</td>
                                        </tr>
                                        <tr>
                                            <td><code>series</code></td>
                                            <td><span class="badge bg-warning">Recommended</span></td>
                                            <td>Product series/family</td>
                                        </tr>
                                        <tr>
                                            <td><code>model</code></td>
                                            <td><span class="badge bg-danger">Required</span></td>
                                            <td>Specific model number</td>
                                        </tr>
                                        <tr>
                                            <td><code>type</code></td>
                                            <td><span class="badge bg-danger">Required</span></td>
                                            <td>"Mono" or "Color"</td>
                                        </tr>
                                        <tr>
                                            <td><code>functions</code></td>
                                            <td><span class="badge bg-warning">Recommended</span></td>
                                            <td>"Print/Copy/Scan" or "Print/Copy/Scan/Fax"</td>
                                        </tr>
                                        
                                        <tr>
                                            <td rowspan="2"><strong>Performance</strong></td>
                                            <td><code>print_speed_bw_ppm</code></td>
                                            <td><span class="badge bg-warning">Recommended</span></td>
                                            <td>Black & white print speed</td>
                                        </tr>
                                        <tr>
                                            <td><code>print_speed_color_ppm</code></td>
                                            <td><span class="badge bg-warning">Recommended</span></td>
                                            <td>Color print speed (or "N/A" for mono)</td>
                                        </tr>
                                        
                                        <tr>
                                            <td rowspan="1"><strong>Paper Handling</strong></td>
                                            <td><code>max_paper_size</code></td>
                                            <td><span class="badge bg-danger">Required</span></td>
                                            <td>"A4", "A3", etc.</td>
                                        </tr>
                                        
                                        <tr>
                                            <td rowspan="1"><strong>Scanning</strong></td>
                                            <td><code>scanner_type</code></td>
                                            <td><span class="badge bg-danger">Required</span></td>
                                            <td>"Flatbed", "Flatbed + RADF", "SPDF", etc.</td>
                                        </tr>
                                        
                                        <tr>
                                            <td rowspan="1"><strong>Business</strong></td>
                                            <td><code>monthly_recommended_volume</code></td>
                                            <td><span class="badge bg-danger">Required</span></td>
                                            <td>Recommended monthly page volume</td>
                                        </tr>
                                        
                                        <tr>
                                            <td rowspan="3"><strong>Targeting</strong></td>
                                            <td><code>target_office_size</code></td>
                                            <td><span class="badge bg-danger">Required</span></td>
                                            <td>Target customer/office size</td>
                                        </tr>
                                        <tr>
                                            <td><code>use_case_notes</code></td>
                                            <td><span class="badge bg-warning">Recommended</span></td>
                                            <td>Description of ideal use case</td>
                                        </tr>
                                        <tr>
                                            <td><code>product_url</code></td>
                                            <td><span class="badge bg-danger">Required</span></td>
                                            <td>Link to product page</td>
                                        </tr>
                                    </tbody>
                                </table>
                                
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> <strong>Tip:</strong> The AI Agent uses these fields to match user requirements:
                                    <ul class="mb-0 mt-2">
                                        <li><strong>Office Size</strong> → <code>targeting.target_office_size</code></li>
                                        <li><strong>Monthly Volume</strong> → <code>business.monthly_recommended_volume</code></li>
                                        <li><strong>Color Needs</strong> → <code>type</code> (Mono/Color)</li>
                                        <li><strong>Paper Size</strong> → <code>paper_handling.max_paper_size</code></li>
                                        <li><strong>Scanning</strong> → <code>scanning.scanner_type</code></li>
                                    </ul>
                                </div>
                                
                                <button class="btn btn-outline-primary btn-sm" onclick="downloadTemplate()">
                                    <i class="bi bi-download"></i> Download Template
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Upload Form -->
                <div class="row align-items-end">
                    <div class="col-md-8">
                        <label for="printerKbFile" class="form-label">Select JSON File</label>
                        <input type="file" class="form-control" id="printerKbFile" accept=".json">
                        <small class="text-muted">Expected format: { "printers": [...] }</small>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary w-100" onclick="uploadKnowledgeBase()" id="uploadBtn">
                            <i class="bi bi-upload"></i> Upload to Knowledge Base
                        </button>
                    </div>
                </div>
                <div id="uploadStatus" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<!-- Filter and Search -->
<div class="row mb-4">
    <div class="col-md-4">
        <input type="text" class="form-control" id="searchInput" placeholder="Search by device ID...">
    </div>
    <div class="col-md-3">
        <select class="form-select" id="colorFilter">
            <option value="">All Color Preferences</option>
            <option value="Color">Color</option>
            <option value="Mono">Mono</option>
        </select>
    </div>
    <div class="col-md-3">
        <select class="form-select" id="paperFilter">
            <option value="">All Paper Sizes</option>
            <option value="A4">A4</option>
            <option value="A3">A3</option>
        </select>
    </div>
    <div class="col-md-2">
        <button class="btn btn-primary w-100" onclick="exportToCSV()">
            <i class="bi bi-download"></i> Export CSV
        </button>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="stats-label">Total Requests</p>
                        <h3 class="stats-value" id="totalRequests">0</h3>
                    </div>
                    <div class="stats-icon blue">
                        <i class="bi bi-clipboard-data"></i>
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
                        <p class="stats-label">Unique Devices</p>
                        <h3 class="stats-value" id="uniqueDevices">0</h3>
                    </div>
                    <div class="stats-icon green">
                        <i class="bi bi-phone"></i>
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
                        <p class="stats-label">Avg. Volume</p>
                        <h3 class="stats-value" id="avgVolume">0</h3>
                    </div>
                    <div class="stats-icon purple">
                        <i class="bi bi-graph-up"></i>
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
                        <p class="stats-label">Today</p>
                        <h3 class="stats-value" id="todayRequests">0</h3>
                    </div>
                    <div class="stats-icon orange">
                        <i class="bi bi-calendar-day"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Requests Table -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-table"></i> All Requests
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="requestsTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Device ID</th>
                        <th>Office Size</th>
                        <th>Monthly Volume</th>
                        <th>Color</th>
                        <th>Paper Size</th>
                        <th>Scanning</th>
                        <th>Budget</th>
                    </tr>
                </thead>
                <tbody id="requestsTableBody">
                    <tr>
                        <td colspan="8" class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
let allRequests = [];
let filteredRequests = [];

// Upload Knowledge Base
async function uploadKnowledgeBase() {
    const fileInput = document.getElementById('printerKbFile');
    const uploadBtn = document.getElementById('uploadBtn');
    const statusDiv = document.getElementById('uploadStatus');
    
    if (!fileInput.files || fileInput.files.length === 0) {
        statusDiv.innerHTML = '<div class="alert alert-warning">Please select a JSON file first.</div>';
        return;
    }
    
    const file = fileInput.files[0];
    
    // Validate file type
    if (!file.name.endsWith('.json')) {
        statusDiv.innerHTML = '<div class="alert alert-danger">Please select a valid JSON file.</div>';
        return;
    }
    
    // Read and validate JSON
    const reader = new FileReader();
    reader.onload = async function(e) {
        try {
            const jsonData = JSON.parse(e.target.result);
            
            // Validate structure
            if (!jsonData.printers || !Array.isArray(jsonData.printers)) {
                statusDiv.innerHTML = '<div class="alert alert-danger">Invalid JSON format. Expected: { "printers": [...] }</div>';
                return;
            }
            
            // Show loading state
            uploadBtn.disabled = true;
            uploadBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Uploading...';
            statusDiv.innerHTML = '<div class="alert alert-info"><i class="bi bi-hourglass-split"></i> Uploading ' + jsonData.printers.length + ' printers to Knowledge Base...</div>';
            
            // Upload to API
            const data = await apiRequest(ADMIN_API_BASE + '/upload_printer_kb.php', {
                body: { kb_data: jsonData }
            });
            
            if (data.success) {
                statusDiv.innerHTML = `
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> Successfully uploaded ${data.count || jsonData.printers.length} printers to Knowledge Base!
                    </div>
                `;
                fileInput.value = ''; // Clear file input
            } else {
                throw new Error(data.message || 'Upload failed');
            }
            
        } catch (error) {
            statusDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i> Failed to upload: ${error.message}
                </div>
            `;
        } finally {
            uploadBtn.disabled = false;
            uploadBtn.innerHTML = '<i class="bi bi-upload"></i> Upload to Knowledge Base';
        }
    };
    
    reader.onerror = function() {
        statusDiv.innerHTML = '<div class="alert alert-danger">Failed to read file.</div>';
    };
    
    reader.readAsText(file);
}

// Download JSON Template
function downloadTemplate() {
    const template = {
        "printers": [
            {
                "brand": "Pantum",
                "series": "Utility 230/320 Series",
                "model": "BM230N",
                "type": "Mono",
                "functions": "Print/Copy/Scan",
                "performance": {
                    "print_speed_color_ppm": "N/A",
                    "print_speed_bw_ppm": "22",
                    "first_print_time_color": "N/A",
                    "first_print_time_bw": "<8.5"
                },
                "paper_handling": {
                    "max_paper_size": "A3",
                    "standard_capacity": "250-sheet tray + 100-sheet multipurpose",
                    "max_capacity": "1450"
                },
                "scanning": {
                    "scanner_type": "Flatbed + RADF (100-sheet)",
                    "scan_speed_simplex": "30",
                    "scan_speed_duplex": "N/A"
                },
                "specifications": {
                    "print_resolution": "",
                    "memory_gb": "1",
                    "storage_gb": "N/A",
                    "mobile_printing": "Optional PWF-230 (Android/iOS app, AirPrint, Mopria)",
                    "connectivity": "USB 2.0; 10/100/1000Base-T; optional Wi-Fi"
                },
                "energy": {
                    "power_consumption_max_w": "<=550 (print average)",
                    "tec_kwh": "N/A"
                },
                "physical": {
                    "dimensions_mm": "565 x 593 x 450",
                    "weight_kg": "27.8"
                },
                "business": {
                    "monthly_recommended_volume": "4000",
                    "toner_yield_black": "TL-3200: 3,000 pages (std); TL-3200H: 12,000 pages",
                    "toner_yield_color": "N/A",
                    "optional_features": "Optional dual 550-sheet trays, Workbench/cabinet"
                },
                "targeting": {
                    "target_office_size": "Small to medium-sized businesses and workgroups",
                    "use_case_notes": "Entry A3 mono MFP with one-click driver install for SMB teams focused on secure workflows.",
                    "product_url": "https://eds.com/product/bm230n"
                }
            },
            {
                "brand": "Pantum",
                "series": "Utility 230 Series",
                "model": "CM230ADN",
                "type": "Color",
                "functions": "Print/Copy/Scan",
                "performance": {
                    "print_speed_color_ppm": "25",
                    "print_speed_bw_ppm": "25",
                    "first_print_time_color": "9.4",
                    "first_print_time_bw": "7"
                },
                "paper_handling": {
                    "max_paper_size": "A3 / 11\"x17\"",
                    "standard_capacity": "250-sheet tray + 100-sheet multipurpose",
                    "max_capacity": "1450"
                },
                "scanning": {
                    "scanner_type": "Flatbed + RADF (100-sheet)",
                    "scan_speed_simplex": "30",
                    "scan_speed_duplex": "14"
                },
                "specifications": {
                    "print_resolution": "",
                    "memory_gb": "2",
                    "storage_gb": "32",
                    "mobile_printing": "Option: CPWF-230 Android/iOS app, AirPrint, Mopria",
                    "connectivity": "USB 3.0; 10/100/1000Base-T; optional dual-band Wi-Fi"
                },
                "energy": {
                    "power_consumption_max_w": "<1100 (run); <35 ready; <1 sleep",
                    "tec_kwh": "N/A"
                },
                "physical": {
                    "dimensions_mm": "565 x 630 x 650",
                    "weight_kg": "56 (no consumables) / 58.5 (with)"
                },
                "business": {
                    "monthly_recommended_volume": "4000",
                    "toner_yield_black": "CTL-2300K 3,000 pages (std); CTL-2300HK 15,000 pages",
                    "toner_yield_color": "CTL-2300C/M/Y 2,000 pages (std); CTL-2300HC/HM/HY 12,000 pages",
                    "optional_features": "Optional CPT-230 550-sheet trays (x2), CPC-230 cabinet"
                },
                "targeting": {
                    "target_office_size": "SMB and workgroup environments needing quick A3 color turnaround",
                    "use_case_notes": "Utility color platform delivering pro-quality print/copy/scan plus strong paper handling and security.",
                    "product_url": "https://eds.com/product/cm230adn"
                }
            }
        ]
    };
    
    const jsonString = JSON.stringify(template, null, 2);
    const blob = new Blob([jsonString], { type: 'application/json' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'printer_kb_template.json';
    a.click();
    window.URL.revokeObjectURL(url);
    
    showToast('Template downloaded successfully', 'success');
}

// Load printer requests
async function loadPrinterRequests() {
    try {
        const data = await apiRequest(ADMIN_API_BASE + '/get_printer_requests.php', {
            body: {}
        });
        
        if (data.success) {
            allRequests = data.requests;
            applyFilters();
            updateStatistics(data.stats);
        }
    } catch (error) {
        showToast('Failed to load printer requests: ' + error.message, 'danger');
        document.getElementById('requestsTableBody').innerHTML = 
            '<tr><td colspan="8" class="text-center text-danger">Failed to load data</td></tr>';
    }
}

// Update statistics
function updateStatistics(stats) {
    document.getElementById('totalRequests').textContent = stats.total_requests || 0;
    document.getElementById('uniqueDevices').textContent = stats.unique_devices || 0;
    document.getElementById('avgVolume').textContent = stats.avg_volume || 0;
    document.getElementById('todayRequests').textContent = stats.today_requests || 0;
}

// Apply filters
function applyFilters() {
    const searchValue = document.getElementById('searchInput').value.toLowerCase();
    const colorValue = document.getElementById('colorFilter').value;
    const paperValue = document.getElementById('paperFilter').value;
    
    filteredRequests = allRequests.filter(request => {
        const matchesSearch = !searchValue || request.device_id.toLowerCase().includes(searchValue);
        const matchesColor = !colorValue || request.color_preference === colorValue;
        const matchesPaper = !paperValue || request.paper_size === paperValue;
        
        return matchesSearch && matchesColor && matchesPaper;
    });
    
    renderTable();
}

// Render table
function renderTable() {
    const tbody = document.getElementById('requestsTableBody');
    
    if (filteredRequests.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No requests found</td></tr>';
        return;
    }
    
    tbody.innerHTML = filteredRequests.map(request => `
        <tr>
            <td>${formatDate(request.created_at)}</td>
            <td><code class="small">${truncateDeviceId(request.device_id)}</code></td>
            <td>${request.office_size || '-'}</td>
            <td>${request.monthly_volume ? request.monthly_volume.toLocaleString() : '-'}</td>
            <td>
                ${request.color_preference ? 
                    `<span class="badge bg-${request.color_preference === 'Color' ? 'info' : 'secondary'}">${request.color_preference}</span>` : 
                    '-'
                }
            </td>
            <td>${request.paper_size || '-'}</td>
            <td>${request.scanning_frequency || '-'}</td>
            <td>${request.budget_level || '-'}</td>
        </tr>
    `).join('');
}

// Helper functions
function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
}

function truncateDeviceId(deviceId) {
    return deviceId.length > 16 ? deviceId.substring(0, 16) + '...' : deviceId;
}

// Export to CSV
function exportToCSV() {
    const headers = ['Date', 'Device ID', 'Office Size', 'Monthly Volume', 'Color', 'Paper Size', 'Scanning', 'Budget'];
    const rows = filteredRequests.map(r => [
        formatDate(r.created_at),
        r.device_id,
        r.office_size || '',
        r.monthly_volume || '',
        r.color_preference || '',
        r.paper_size || '',
        r.scanning_frequency || '',
        r.budget_level || ''
    ]);
    
    let csvContent = headers.join(',') + '\n';
    csvContent += rows.map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
    
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `printer_requests_${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    window.URL.revokeObjectURL(url);
    
    showToast('Exported ' + filteredRequests.length + ' requests to CSV', 'success');
}

// Event listeners
document.getElementById('searchInput').addEventListener('input', applyFilters);
document.getElementById('colorFilter').addEventListener('change', applyFilters);
document.getElementById('paperFilter').addEventListener('change', applyFilters);

// Load data on page load
document.addEventListener('DOMContentLoaded', loadPrinterRequests);

// Auto-refresh every 60 seconds
setInterval(loadPrinterRequests, 60000);
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
