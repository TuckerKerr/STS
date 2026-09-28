/**
 * Complete Printer Dashboard Implementation
 */

// ============================================
// MAIN RENDERING FUNCTIONS
// ============================================

/**
 * Render a printer card to the grid
 */
let campus = '';
let listenerInitialized = false

// Escape untrusted text before inserting it into innerHTML
function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

function renderPrinterCard(printer) {
    const grid = document.getElementById('printerGrid');
    if (!grid) return;

    // Get printer status from treeview if available
    const statusLevel = determineOverallStatus(printer);

    const card = document.createElement('div');
    card.className = 'printer-card';
    card.setAttribute('data-ip', printer.IP_Address);


    card.innerHTML = `
        <div class="printer-header">
            <div>
                <div class="printer-name">
                    <span class="status-indicator status-${statusLevel}"></span>
                    ${escapeHtml(printer.install_location)}
                </div>
                <div class="printer-ip"><a style="color: #666;" href="https://${encodeURIComponent(printer.IP_Address)}" target="_blank">${escapeHtml(printer.IP_Address)}</a></div>
            </div>
        </div>

        <div class="printer-body" id=${printer.IP_Address}>
            <div class="consumable">
                <div class="consumable-name">
                    <span>Model</span>
                    <span>${escapeHtml(printer.model || 'Unknown')}</span>
                </div>
            </div>

            <div id="status-${printer.IP_Address.replace(/\./g, '_')}" class="status-content">
                <p style="text-align: center; color: #7f8c8d; padding: 20px;">
                    Click "Check Status" to load printer details
                </p>
            </div>
        </div>

        <div class="printer-footer">
            <button class="btn btn-primary" data-action="check-status">
                <i class="fas fa-sync-alt"></i> Check Status
            </button>
        </div>
    `;
    card.querySelector('[data-action="check-status"]').addEventListener('click', () => {
        checkIndividualPrinter(printer.IP_Address);
    });
    grid.appendChild(card);
}

/**
 * Update printer card with detailed status
 */
function updatePrinterDisplay(ipAddress, parsedData) {
    const statusContainer = document.getElementById(`status-${ipAddress.replace(/\./g, '_')}`);
    if (!statusContainer) return;
    
    let html = '';

    console.log("Model Number!!: " + parsedData.modelNumber);
    
    // Cartridges
    if (Object.keys(parsedData.cartridges).length > 0) {
        html += '<div class="consumable-group">';
        html += '<h4 style="margin-bottom: 10px; font-size: 14px;">Cartridges</h4>';
        for (const [color, value] of Object.entries(parsedData.cartridges)) {
            const level = PrinterAPI.getStatusLevel(value);
            const percent = extractPercent(value);
            html += renderConsumable(color, value, percent, level);
        }
        html += '</div>';
    }
    
    // Drums
    if (Object.keys(parsedData.drums).length > 0) {
        html += '<div class="consumable-group" style="margin-top: 15px;">';
        html += '<h4 style="margin-bottom: 10px; font-size: 14px;">Drums</h4>';
        for (const [color, value] of Object.entries(parsedData.drums)) {
            const level = PrinterAPI.getStatusLevel(value);
            const percent = extractPercent(value);
            html += renderConsumable(color, value, percent, level);
        }
        html += '</div>';
    }
    
    // Kits
    if (Object.keys(parsedData.kits).length > 0) {
        html += '<div class="consumable-group" style="margin-top: 15px;">';
        html += '<h4 style="margin-bottom: 10px; font-size: 14px;">Maintenance</h4>';
        for (const [name, value] of Object.entries(parsedData.kits)) {
            const level = PrinterAPI.getStatusLevel(value);
            const percent = extractPercent(value);
            html += renderConsumable(name, value, percent, level);
        }
        html += '</div>';
    }
    
    // Trays
    if (Object.keys(parsedData.trays).length > 0) {
        html += '<div class="tray-status">';
        html += '<h4 style="margin-bottom: 10px; font-size: 14px;">Paper Trays</h4>';
        for (const [tray, value] of Object.entries(parsedData.trays)) {
            html += `<div style="margin-bottom: 5px; font-size: 13px;">
                <strong>${escapeHtml(tray.replace('tray', 'Tray '))}:</strong> ${escapeHtml(value)}
            </div>`;
        }
        html += '</div>';
    }

    console.log("Time for pass: " + JSON.stringify(parsedData));
    console.log("Time Checked: " + JSON.stringify(parsedData.timeChecked));

    html += '<div class="tray-status">';
    html += '<h4 style="margin-bottom: 10px; font-size: 14px;">Time Last Checked</h4>';
    html += `<div class="timeSlot" style="margin-bottom: 10px; font-size: 14px;">${escapeHtml(parsedData.timeChecked || 'Not Checked')}</div>`;
    html += '</div>';

    
    statusContainer.innerHTML = html;
}

/**
 * Render a single consumable progress bar
 */
function renderConsumable(name, value, percent, level) {
    const displayName = name.charAt(0).toUpperCase() + name.slice(1);
    const colorClass = level === 'good' ? 'bg-success' : 
                       level === 'warning' ? 'bg-warning' : 'bg-danger';
    
    return `
        <div class="consumable">
            <div class="consumable-name">
                <span>${escapeHtml(displayName)}</span>
                <span>${escapeHtml(value)}</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill ${colorClass}" style="width: ${percent}%"></div>
            </div>
        </div>
    `;
}

/**
 * Extract percentage from value string
 */
function extractPercent(value) {
    const match = value.match(/(\d+)%/);
    return match ? parseInt(match[1]) : 0;
}

/**
 * Determine overall printer status
 */
function determineOverallStatus(printer) {
    // This could be enhanced to check actual status
    return 'good'; // Default to good, will be updated when status is checked
}

// ============================================
// API INTERACTION FUNCTIONS
// ============================================

/**
 * Load all printers for a campus
 */

async function loadCampusPrinters(campus) {
    try {
        showLoading(true);

        const response = await PrinterAPI.getPrinters(campus);
        
        console.log(`Loaded ${response.count} printers for ${campus}`);
        
        // Clear existing grid
        const grid = document.getElementById('printerGrid');
        if (grid) grid.innerHTML = '';
        
        // Render printers
        response.printers.forEach(printer => {
            renderPrinterCard(printer);
        });
        
        showLoading(false);
        
    } catch (error) {
        console.error('Failed to load printers:', error);
        showError('Failed to load printers. Please try again.');
        showLoading(false);
    }
}

/**
 * Check individual printer status
 */
async function checkIndividualPrinter(ipAddress) {
    try {
        // Show loading state
        const statusEl = document.getElementById(`status-${ipAddress.replace(/\./g, '_')}`);
        if (statusEl) {
            statusEl.innerHTML = '<p style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Checking printer...</p>';
        }
        
        // Trigger the check
        const response = await PrinterAPI.checkSinglePrinter(ipAddress);

        if (response.success) {
            // Parse the printer data
            const parsedData = PrinterAPI.parsePrinterData(response.data.printer);
            parsedData.timeChecked = response.timestamp;


            console.log("Check Data" + JSON.stringify(parsedData));
            const todaysDate = new Date().toISOString().slice(0,10);
            if (parsedData) {
                // Update UI with new data
                console.log("Ip Address: " + ipAddress);
                submitPrinterCheck(ipAddress, 'Tucker');
                updatePrinterDisplay(ipAddress, parsedData);
                markPrinterAsChecked(ipAddress, todaysDate);
                console.log(`Successfully checked printer ${ipAddress}`);
                
            } else {
                statusEl.innerHTML = '<p style="text-align: center; color: #e74c3c; padding: 20px;">Failed to parse printer data</p>';
            }
            
            return parsedData;
        }
        
    } catch (error) {
        console.error(`Failed to check printer ${ipAddress}:`, error);
        
        // Update UI to show error
        const statusEl = document.getElementById(`status-${ipAddress.replace(/\./g, '_')}`);
        if (statusEl) {
            statusEl.innerHTML = '<p style="text-align: center; color: #e74c3c; padding: 20px;">Check Failed</p>';
        }
        
        throw error;
    }
}

/**
 * Perform full scan of all printers
 */
async function performFullScan() {
    const startTime = Date.now();
    
    try {
        showLoading(true);
        showSuccess('Triggering full printer scan...');
        
        // Trigger full scan via Python backend
        await PrinterAPI.triggerFullScan();
        
        showSuccess('Full scan triggered. Waiting for data to update...');
        
        // Wait a bit for the scan to complete and write to treeview.json
        await new Promise(resolve => setTimeout(resolve, 3000));
        
        // Reload printer statuses from treeview.json
        await loadAllPrinterStatuses();
        
        const elapsed = Math.floor((Date.now() - startTime) / 1000);
        console.log(`Full scan completed in ${elapsed} seconds`);
        showSuccess(`All printers scanned successfully! (${elapsed}s)`);
        
    } catch (error) {
        console.error('Full scan failed:', error);
        showError('Full scan failed: ' + error.message);
        
    } finally {
        showLoading(false);
    }
}

/**
 * Load status for all printers from treeview.json
 */
async function loadAllPrinterStatuses() {
    const printerCards = document.querySelectorAll('.printer-card');
    let successCount = 0;
    let failCount = 0;
    
    for (const card of printerCards) {
        const ip = card.getAttribute('data-ip');
        if (ip) {
            try {
                // Get status from treeview.json (not triggering new scan)
                const response = await PrinterAPI.getPrinterStatus(ip);
                if (response.success && response.printer) {
                    const parsedData = PrinterAPI.parsePrinterData(response.printer);
                    if (parsedData) {
                        updatePrinterDisplay(ip, parsedData);
                        successCount++;
                    }
                }
            } catch (error) {
                failCount++;
                console.warn(`Could not load status for ${ip}:`, error.message);
                // Don't update display on error - keep existing content or initial message
            }
        }
    }
    
    console.log(`Loaded ${successCount} printer statuses, ${failCount} not found in treeview.json`);
    
    if (successCount === 0 && failCount > 0) {
        showError('No printer data found. Try running a full scan or check individual printers.');
    } else if (successCount > 0) {
        showSuccess(`Refreshed ${successCount} printer${successCount !== 1 ? 's' : ''} from cache`);
    }
}

/**
 * Submit printer check
 */
async function submitPrinterCheck(ipAddress, studentName) {
    try {
        // Get printer info from database
        const printersResponse = await PrinterAPI.getPrinters(campus);
        const printerInfo = printersResponse.printers.find(p => p.IP_Address === ipAddress);
        
        if (!printerInfo) {
            throw new Error('Printer not found in database');
        }
         //error somewhere here
        // Get current status from treeview
        const statusResponse = await PrinterAPI.getPrinterStatus(ipAddress);
        const parsedData = PrinterAPI.parsePrinterData(statusResponse.printer);
        
        // Format for submission
        const checkData = PrinterAPI.formatForSubmission(
            printerInfo,
            parsedData,
            studentName
        );
        console.log("Data for submitted " + checkData);
        
        // Submit the check
        const response = await PrinterAPI.submitCheck(checkData);
        
        if (response.success) {
            console.log('Check submitted successfully:', response.id);
            showSuccess('Printer check recorded successfully!');
            return response;
        }
        
    } catch (error) {
        console.error('Failed to submit check:', error);
        showError('Failed to record printer check');
        throw error;
    }
}

/**
 * Load today's completed checks
 */
async function loadTodaysChecks() {
    try {
        const response = await PrinterAPI.getCurrentDayChecks();
        
        console.log(`Found ${response.count} checks today`);

        // Mark printers that have been checked today
        response.checks.forEach(check => {
            const card = document.querySelector(`[data-ip="${check.IP_Address}"]`);
            if(card){
                const dateOnly = check.Check_Date.substring(0,10);
                markPrinterAsChecked(check.IP_Address, dateOnly);
                console.log("Time for check Date " + dateOnly);
            }

        });
        
        return response.checks;
        
    } catch (error) {
        console.error('Failed to load checks:', error);
        return [];
    }
}

/**
 * Update last scan time display
 */
async function updateLastScanTime() {
    try {
        const response = await PrinterAPI.getLastScan();
        
        // You can add a UI element to display this if needed
        console.log(`Last scan: ${response.time_ago}`);
        
        return response;
        
    } catch (error) {
        console.error('Failed to get last scan time:', error);
    }
}

// ============================================
// UI HELPER FUNCTIONS
// ============================================

function showLoading(show) {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.classList.toggle('hidden', !show);
    }
}

function showError(message) {
    const container = document.getElementById('errorContainer');
    const messageEl = document.getElementById('errorMessage');
    if (container && messageEl) {
        messageEl.textContent = message;
        container.classList.remove('hidden');
        setTimeout(() => container.classList.add('hidden'), 5000);
    }
}

function showSuccess(message) {
    const container = document.getElementById('successContainer');
    const messageEl = document.getElementById('successMessage');
    if (container && messageEl) {
        messageEl.textContent = message;
        container.classList.remove('hidden');
        setTimeout(() => container.classList.add('hidden'), 5000);
    }
}

function markPrinterAsChecked(ipAddress, Check_Date) {
    const newDate = new Date().toISOString().slice(0,10);

    const card = document.querySelector(`[data-ip="${ipAddress}"]`);
    if (card) {
        const header = card.querySelector('.printer-header');
        const text = header.querySelector('.printer-name')
        if (header) {
            header.style.backgroundColor = '#e7f5e7';
            text.style.color = "#fff"
        }
        const dataBody = card.querySelector('.timeSlot');
        dataBody.textContent = Check_Date;
    }
    if(newDate === Check_Date){
            const btn = card.querySelector(".btn.btn-primary");
            btn.style.display = "none";
        }
}

// ============================================
// EVENT LISTENERS SETUP
// ============================================

function setupEventListeners() {
    if(listenerInitialized) return;
    listenerInitialized = true;

    // Full Scan button - triggers Python backend to scan all printers
    const fullScanBtn = document.getElementById('fullScan');
    if (fullScanBtn) {
        fullScanBtn.addEventListener('click', async () => {
            await performFullScan();
        });
    }
    
    // Refresh Display button - just reload from treeview.json (no new scans)
    const refreshBtn = document.getElementById('refreshAll');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', async () => {
            try {
                await loadAllPrinterStatuses();
            } catch (error) {
                console.error('Refresh error:', error);
                showError('Failed to refresh display');
            }
        });
    }
    
    // Search functionality
    const searchInput = document.getElementById('ipSearch');
    const searchBtn = document.getElementById('checkSinglePrinter');
    if (searchBtn && searchInput) {
        searchBtn.addEventListener('click', async () => {
            const ip = searchInput.value.trim();
            if (ip) {
                await checkIndividualPrinter(ip);
            }
        });
        
        // Allow Enter key in search
        searchInput.addEventListener('keypress', async (e) => {
            if (e.key === 'Enter') {
                const ip = searchInput.value.trim();
                if (ip) {
                    await checkIndividualPrinter(ip);
                }
            }
        });
    }

    //Campus Toggle
    const campusToggle = document.getElementById('campusToggle');

    if(campusToggle){
        campusToggle.addEventListener('change', (e) => {
            if(e.target.checked){
                campus = '';
                campus = 'harborside';
                console.log("Campus " + campus);
                initializePrinterPage(campus);
            }
            else{
                campus = '';
                campus = 'downcity';
                console.log("Campus " + campus);
                initializePrinterPage(campus);
            }
        });
    }
    
    // Auto-refresh toggle
    const autoRefreshToggle = document.getElementById('autoRefreshToggle');
    if (autoRefreshToggle) {
        autoRefreshToggle.addEventListener('change', (e) => {
            if (e.target.checked) {
                startAutoRefresh();
            } else {
                stopAutoRefresh();
            }
        });
    }
}

// Auto-refresh functionality
let autoRefreshInterval = null;

function startAutoRefresh() {
    stopAutoRefresh(); // Clear any existing interval
    
    autoRefreshInterval = setInterval(async () => {
        console.log('Auto-refreshing printer statuses...');
        await loadAllPrinterStatuses();
    }, 5 * 60 * 1000); // 5 minutes
    
    showSuccess('Auto-refresh enabled (every 5 minutes)');
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
    }
}


// =========================================
// PAGE INITIALIZATION
// ============================================

async function initializePrinterPage(campus) {
    try {
        console.log('Initializing printer page...');
        
        // Load printers for the selected campus
        await loadCampusPrinters(campus);
        
        // Try to load existing data from treeview.json (silently fail if not available)
        try {
            await loadAllPrinterStatuses();
        } catch (error) {
            console.log('No existing treeview data - printers will show initial state');
        }
        
        // Load today's completed checks
        await loadTodaysChecks();
        
        // Update last scan time
        await updateLastScanTime();
        

        // Set up event listeners
        setupEventListeners();
        
        console.log('Page initialized successfully');
        
    } catch (error) {
        console.error('Failed to initialize page:', error);
        showError('Failed to load printer data. Please refresh the page.');
    }
}

// ============================================
// AUTO-INITIALIZATION
// ============================================

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {

        initializePrinterPage();
    });
} else {
    initializePrinterPage();
}

// Make checkIndividualPrinter globally accessible for onclick handlers
window.checkIndividualPrinter = checkIndividualPrinter;