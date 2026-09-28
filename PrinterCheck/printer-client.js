/**
 * Printer Client API Module
 * Handles all client-side printer operations
 */

const PrinterAPI = (function() {
const API_BASE = '/sts/PrinterCheck/printer-api.php';
    
    /**
     * Make API requeste
     */
    async function makeRequest(action, params = {}, options = {}) {
        try {
            const url = new URL(API_BASE, window.location.origin);
            url.searchParams.append('action', action);
            
            // Add any additional query parameters
            Object.keys(params).forEach(key => {
                if (params[key] !== null && params[key] !== undefined) {
                    url.searchParams.append(key, params[key]);
                }
            });
            
            const fetchOptions = {
                method: options.method || 'GET',
                headers: {
                    'Accept': 'application/json',
                    ...options.headers
                }
            };

            // Add body for POST requests
            if (options.method === 'POST' && options.body) {
                fetchOptions.headers['Content-Type'] = 'application/json';
                fetchOptions.headers['X-CSRF-Token'] = sessionStorage.getItem('csrfToken') || '';
                fetchOptions.body = JSON.stringify(options.body);
            }
            
            console.log(`Making request to: ${url}`);
            
            const response = await fetch(url, fetchOptions);
            
            // Get the response text first for debugging
            const responseText = await response.text();
            console.log(`Response status: ${response.status}`);
            console.log(`Response text: ${responseText.substring(0, 500)}`);
            
            if (!response.ok) {
                console.error(`HTTP error! status: ${response.status}, body: ${responseText}`);
                throw new Error(`HTTP error! status: ${response.status} - ${responseText.substring(0, 200)}`);
            }
            
            // Try to parse as JSON
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Response was:', responseText);
                throw new Error(`Invalid JSON response: ${responseText.substring(0, 200)}`);
            }
            
            if (data.error) {
                console.error('API returned error:', data.error);
                throw new Error(data.error);
            }
            
            return data;
        } catch (error) {
            console.error(`API Error [${action}]:`, error);
            throw error;
        }
    }
    
    /**
     * Get all printers for a campus
     */
    async function getPrinters(campus) {
        return makeRequest('get_printers', { campus });
    }
    
    /**
     * Get status for a specific printer
     */
    async function getPrinterStatus(ip) {
        if (!ip) {
            throw new Error('IP address is required');
        }
        return makeRequest('get_printer_status', { ip });
    }
    
    /**
     * Trigger a check for a single printer
     */
    async function checkSinglePrinter(ip) {
        if (!ip) {
            throw new Error('IP address is required');
        }
        console.log(`Checking printer: ${ip}`);
        return makeRequest('check_single_printer', { ip });
    }
    
    /**
     * Get today's completed checks
     */
    async function getCurrentDayChecks() {
        return makeRequest('get_current_day_checks');
    }
    
    /**
     * Get active student workers
     */
    async function getActiveStudents() {
        return makeRequest('get_active_students');
    }
    
    /**
     * Submit a printer check
     */
    async function submitCheck(checkData) {
        return makeRequest('submit_check', {}, {
            method: 'POST',
            body: checkData
        });
    }
    
    /**
     * Get last scan timestamp
     */
    async function getLastScan() {
        return makeRequest('get_last_scan');
    }
    
    /**
     * Trigger full printer scan
     */
    async function triggerFullScan() {
        try {
            console.log('Triggering full scan...');
            const response = await fetch('http://10.9.5.21:8080/full_send', {
                method: 'POST'
            });
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Full scan failed:', errorText);
                throw new Error(`Full scan failed: ${response.status} - ${errorText.substring(0, 200)}`);
            }
            
            // Record timestamp
            await fetch('./timestamp.php', {
                method: 'POST',
                headers: { 'X-CSRF-Token': sessionStorage.getItem('csrfToken') || '' }
            });
            
            return { success: true, message: 'Full scan completed' };
        } catch (error) {
            console.error('Full scan error:', error);
            throw error;
        }
    }
    
    /**
     * Parse printer data from treeview format
     */
    function parsePrinterData(printerData) {
        if (!printerData || !printerData.Children) {
            console.warn('Invalid printer data:', printerData);
            return null;
        }

        console.log("JSON Printer Data: " + JSON.stringify(printerData))
        
        const parsed = {
            ip: printerData.RootNode,
            status: printerData.Status || 'unknown',
            modelNumber: '',
            cartridges: {},
            drums: {},
            kits: {},
            trays: {}
        };
        
        printerData.Children.forEach(child => {
            const text = child.Text;
            
            // Model number (usually second child)
            if (!text.includes(':') && !text.includes(parsed.ip)) {
                parsed.modelNumber = text;
                return;
            }
            
            // Parse consumables
            if (text.includes('Cartridge')) {
                if (text.includes('Black')) parsed.cartridges.black = extractValue(text);
                else if (text.includes('Cyan')) parsed.cartridges.cyan = extractValue(text);
                else if (text.includes('Yellow')) parsed.cartridges.yellow = extractValue(text);
                else if (text.includes('Magenta')) parsed.cartridges.magenta = extractValue(text);
            }
            
            // Parse drums
            else if (text.includes('Drum')) {
                if (text.includes('Black')) parsed.drums.black = extractValue(text);
                else if (text.includes('Cyan')) parsed.drums.cyan = extractValue(text);
                else if (text.includes('Yellow')) parsed.drums.yellow = extractValue(text);
                else if (text.includes('Magenta')) parsed.drums.magenta = extractValue(text);
            }
            
            // Parse kits
            else if (text.includes('Kit') || text.includes('Toner')) {
                if (text.includes('Maintenance')) parsed.kits.maintenance = extractValue(text);
                else if (text.includes('Transfer')) parsed.kits.transfer = extractValue(text);
                else if (text.includes('Fuser')) parsed.kits.fuser = extractValue(text);
                else if (text.includes('Waste')) parsed.kits.waste = extractValue(text);
            }
            
            // Parse trays
            else if (text.includes('Tray')) {
                const trayMatch = text.match(/Tray (\d+)/);
                if (trayMatch) {
                    parsed.trays[`tray${trayMatch[1]}`] = extractValue(text);
                }
            }
        });
        
        console.log('Parsed printer data:', parsed);
        return parsed;
    }
    
    /**
     * Extract value from "Name: Value" format
     */
    function extractValue(text) {
        const parts = text.split(':');
        return parts.length > 1 ? parts[1].trim() : text;
    }
    
    /**
     * Determine status level from percentage
     */
    function getStatusLevel(value) {
        const percentMatch = value.match(/(\d+)%/);
        if (percentMatch) {
            const percent = parseInt(percentMatch[1]);
            if (percent >= 50) return 'good';
            if (percent >= 20) return 'warning';
            return 'critical';
        }
        
        const lowerValue = value.toLowerCase();
        if (lowerValue.includes('empty') || lowerValue.includes('depleted')) {
            return 'critical';
        }
        if (lowerValue.includes('low')) {
            return 'warning';
        }
        if (lowerValue.includes('ok') || lowerValue.includes('good')) {
            return 'good';
        }
        
        return 'unknown';
    }
    
    /**
     * Format printer data for form submission
     */
    function formatForSubmission(printerInfo, parsedData, checkedBy) {
        return {
            Checked_By: checkedBy,
            IP_Address: printerInfo.IP_Address,
            Install_Location: printerInfo.install_location,
            Model_Number: parsedData.modelNumber || '',
            Black_Cartridge: parsedData.cartridges.black || '',
            Cyan_Cartridge: parsedData.cartridges.cyan || '',
            Yellow_Cartridge: parsedData.cartridges.yellow || '',
            Magenta_Cartridge: parsedData.cartridges.magenta || '',
            Black_Drum: parsedData.drums.black || '',
            Cyan_Drum: parsedData.drums.cyan || '',
            Yellow_Drum: parsedData.drums.yellow || '',
            Magenta_Drum: parsedData.drums.magenta || '',
            Maintenance_Kit: parsedData.kits.maintenance || '',
            Transfer_Kit: parsedData.kits.transfer || '',
            Fuser_Kit: parsedData.kits.fuser || '',
            Waste_Toner: parsedData.kits.waste || '',
            Tray_2: parsedData.trays.tray2 || '',
            Tray_3: parsedData.trays.tray3 || '',
            Tray_4: parsedData.trays.tray4 || '',
            Tray_5: parsedData.trays.tray5 || ''
        };
    }
    
    // Public API ///
    return {
        getPrinters,
        getPrinterStatus,
        checkSinglePrinter,
        getCurrentDayChecks,
        getActiveStudents,
        submitCheck,
        getLastScan,
        triggerFullScan,
        parsePrinterData,
        getStatusLevel,
        formatForSubmission
    };
})();

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PrinterAPI;
}