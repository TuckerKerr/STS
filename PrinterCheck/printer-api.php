<?php
/**
 * Consolidated Printer Management API
 * Handles all printer-related operations with proper separation of concerns
 */

require_once('../security.php');
require_login();

// Set JSON header for API responses
header('Content-Type: application/json');

// Include database connection (credentials stay server-side)
require_once('../db_connection.php');

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Route requests to appropriate handlers
try {
    switch ($action) {
        case 'get_printers':
            handleGetPrinters();
            break;
        
        case 'get_printer_status':
            handleGetPrinterStatus();
            break;
        
        case 'check_single_printer':
            handleCheckSinglePrinter();
            break;
        
        case 'get_current_day_checks':
            handleGetCurrentDayChecks();
            break;
        
        case 'get_active_students':
            handleGetActiveStudents();
            break;
        
        case 'submit_check':
            handleSubmitCheck();
            break;
        
        case 'get_last_scan':
            handleGetLastScan();
            break;
        
        default:
            sendResponse(['error' => 'Invalid action'], 400);
    }
} catch (Exception $e) {
    error_log("Printer API Error: " . $e->getMessage());
    sendResponse(['error' => 'Internal server error'], 500);
}

/**
 * Get all printers for a specific campus
 */
function handleGetPrinters() {
    global $conn;
    
    $campus = $_GET['campus'];
    $table = ($campus === 'harborside') ? 'harborside_printers' : 'downcity_printers';

    try {
        $query = "SELECT * FROM $table ORDER BY install_location";
        $result = mysqli_query($conn, $query);
        
        if (!$result) {
            throw new Exception(mysqli_error($conn));
        }
        
        $printers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $printers[] = $row;
        }
        
        sendResponse([
            'success' => true,
            'campus' => $campus,
            'printers' => $printers,
            'count' => count($printers)
        ]);
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        sendResponse(['error' => 'Failed to fetch printers'], 500);
    }
}

/**
 * //
 * //
 * Get printer status from treeview.json
 */
function handleGetPrinterStatus() {
    $ip = $_GET['ip'] ?? null;
    
    if (!$ip) {
        sendResponse(['error' => 'IP address required'], 400);
        return;
    }
    
    // Validate IP format
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        sendResponse(['error' => 'Invalid IP address'], 400);
        return;
    }
    
    // Read treeview.json
    $jsonFile = __DIR__ . '/treeview.json';
    
    if (!file_exists($jsonFile)) {
        sendResponse(['error' => 'Printer status data not available'], 404);
        return;
    }
    
    try {
        $jsonData = json_decode(file_get_contents($jsonFile), true);
        
        // Find the specific printer
        $printerData = null;
        foreach ($jsonData as $printer) {
            if ($printer['RootNode'] === $ip) {
                $printerData = $printer;
                break;
            }
        }
        
        if ($printerData) {
            sendResponse([
                'success' => true,
                'printer' => $printerData
            ]);
        } else {
            sendResponse(['error' => 'Printer not found'], 404);
        }
    } catch (Exception $e) {
        error_log("Error reading printer status: " . $e->getMessage());
        sendResponse(['error' => 'Failed to read printer status'], 500);
    }
}

/**
 * Trigger a single printer check via Python backend
 */
function handleCheckSinglePrinter() {
    $ip = $_GET['ip'] ?? null;
    
    if (!$ip) {
        sendResponse(['error' => 'IP address required'], 400);
        return;
    }
    
    // Validate IP format
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        sendResponse(['error' => 'Invalid IP address'], 400);
        return;
    }
    
    // Call Python backend to check the printer
     $pythonEndpoint = 'http://10.9.5.21:8080/printer/' . urlencode($ip);
    
    try {
        // Use cURL for better control
        $ch = curl_init($pythonEndpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // Log the response for debugging
        error_log("Python response for $ip: HTTP $httpCode - " . substr($response, 0, 200));
        
        if ($curlError) {
            error_log("cURL error for $ip: $curlError");
            sendResponse(['error' => 'Failed to connect to printer service'], 500);
            return;
        }

        if ($httpCode === 200) {
            $printerData = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("JSON decode error for $ip: " . json_last_error_msg());
                sendResponse(['error' => 'Invalid response from printer service'], 500);
                return;
            }

            sendResponse([
                'success' => true,
                'ip' => $ip,
                'data' => $printerData,
            ]);
        } else {
            error_log("Python backend returned HTTP $httpCode for $ip: $response");
            sendResponse(['error' => 'Printer service returned an error'], 500);
        }
    } catch (Exception $e) {
        error_log("Exception checking printer $ip: " . $e->getMessage());
        sendResponse(['error' => 'Failed to communicate with printer service'], 500);
    }
}

/**
 * Get today's completed printer checks
 */
function handleGetCurrentDayChecks() {
    global $conn;
    
    try {
        $query = "SELECT * FROM current_day_printer_checks ORDER BY Check_Date DESC";
        $result = mysqli_query($conn, $query);
        
        if (!$result) {
            throw new Exception(mysqli_error($conn));
        }
        
        $checks = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $checks[] = $row;
        }
        
        sendResponse([
            'success' => true,
            'checks' => $checks,
            'count' => count($checks)
        ]);
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        sendResponse(['error' => 'Failed to fetch checks'], 500);
    }
}

/**
 * Get active student workers
 */
function handleGetActiveStudents() {
    global $conn;
    
    try {
        $query = "SELECT * FROM campus_student_workers WHERE active = '1' ORDER BY student_name";
        $result = mysqli_query($conn, $query);
        
        if (!$result) {
            throw new Exception(mysqli_error($conn));
        }
        
        $students = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $students[] = $row;
        }
        
        sendResponse([
            'success' => true,
            'students' => $students,
            'count' => count($students)
        ]);
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        sendResponse(['error' => 'Failed to fetch students'], 500);
    }
}

/**
 * Submit a printer check
 */
function handleSubmitCheck() {
    global $conn;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(['error' => 'POST method required'], 405);
        return;
    }
    
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['Checked_By', 'IP_Address', 'Install_Location', 'Model_Number'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            sendResponse(['error' => "Missing required field: $field"], 400);
            return;
        }
    }
    
    try {
        $query = "INSERT INTO printer_check (
            Checked_By, Check_Date, IP_Address, Install_Location, Model_Number,
            Black_Cartridge, Cyan_Cartridge, Yellow_Cartridge, Magenta_Cartridge,
            Black_Drum, Cyan_Drum, Yellow_Drum, Magenta_Drum,
            Maintenance_Kit, Transfer_Kit, Fuser_Kit, Waste_Toner,
            Tray_2, Tray_3, Tray_4, Tray_5
        ) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $query);
        
        if (!$stmt) {
            throw new Exception(mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, 'ssssssssssssssssssss',
            $data['Checked_By'],
            $data['IP_Address'],
            $data['Install_Location'],
            $data['Model_Number'],
            $data['Black_Cartridge'] ,
            $data['Cyan_Cartridge'] ,
            $data['Yellow_Cartridge'] ,
            $data['Magenta_Cartridge'] ,
            $data['Black_Drum'] ,
            $data['Cyan_Drum'] ,
            $data['Yellow_Drum'] ,
            $data['Magenta_Drum'] ,
            $data['Maintenance_Kit'] ,
            $data['Transfer_Kit'] ,
            $data['Fuser_Kit'] ,
            $data['Waste_Toner'] ,
            $data['Tray_2'] ,
            $data['Tray_3'] ,
            $data['Tray_4'] ,
            $data['Tray_5'] 
        );
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception(mysqli_stmt_error($stmt));
        }
        
        $insertId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        
        sendResponse([
            'success' => true,
            'message' => 'Printer check recorded successfully',
            'id' => $insertId
        ]);
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        sendResponse(['error' => 'Failed to record check'], 500);
    }
}

/**
 * Get last scan timestamp
 */
function handleGetLastScan() {
    global $conn;
    
    try {
        $query = "SELECT scan_run_date FROM scan_run_log ORDER BY scan_run_date DESC LIMIT 1";
        $result = mysqli_query($conn, $query);
        
        if (!$result) {
            throw new Exception(mysqli_error($conn));
        }
        
        $row = mysqli_fetch_assoc($result);
        
        if ($row) {
            $lastRun = new DateTime($row['scan_run_date']);
            $now = new DateTime();
            $diff = $now->diff($lastRun);
            
            // Format time difference
            if ($diff->y > 0) {
                $timeAgo = $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
            } elseif ($diff->m > 0) {
                $timeAgo = $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
            } elseif ($diff->d > 0) {
                $timeAgo = $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
            } elseif ($diff->h > 0) {
                $timeAgo = $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
            } elseif ($diff->i > 0) {
                $timeAgo = $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
            } else {
                $timeAgo = 'just now';
            }
            
            sendResponse([
                'success' => true,
                'timestamp' => $row['scan_run_date'],
                'time_ago' => $timeAgo
            ]);
        } else {
            sendResponse([
                'success' => true,
                'timestamp' => null,
                'time_ago' => 'Never'
            ]);
        }
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        sendResponse(['error' => 'Failed to fetch last scan'], 500);
    }
}

/**
 * Send JSON response
 */
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}