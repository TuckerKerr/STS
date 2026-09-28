# API Documentation

## Overview
The STS system uses both REST APIs and external integrations to manage printer monitoring and lab statistics. This document covers the primary APIs used throughout the system.
 
---

## Printer Check API

### Base Endpoint
```
/sts/PrinterCheck/printer-api.php
```

### Authentication
All requests should include:
```
Content-Type: application/json
Access-Control-Allow-Origin: *
```

### Available Actions

#### 1. Get Printers
**Endpoint:** `?action=get_printers&campus={campus}`  
**Method:** GET  
**Parameters:**
- `campus` (required): Either `harborside` or `downcity`

**Response:**
```json
{
  "success": true,
  "campus": "downcity",
  "printers": [
    {
      "IP_Address": "10.9.x.x",
      "install_location": "Building-Room",
      "model": "HP LaserJet M428fdw"
    }
  ],
  "count": 25
}
```

**Database Tables:**
- `harborside_printers`
- `downcity_printers`

---

#### 2. Get Printer Status
**Endpoint:** `?action=get_printer_status&ip={ip_address}`  
**Method:** GET  
**Parameters:**
- `ip` (required): Valid IP address of the printer

**Response:**
```json
{
  "success": true,
  "printer": {
    "RootNode": "10.9.x.x",
    "Status": "online",
    "Children": [
      {
        "Text": "HP LaserJet M428fdw"
      },
      {
        "Text": "Black Cartridge: 75%"
      }
    ]
  }
}
```

**Data Source:** Reads from `treeview.json` file

---

#### 3. Check Single Printer
**Endpoint:** `?action=check_single_printer&ip={ip_address}`  
**Method:** GET  
**Parameters:**
- `ip` (required): Valid IP address

**Backend Integration:**
Makes a request to Python backend at `http://10.9.5.21:8080/printer/{ip}`

**Response:**
```json
{
  "success": true,
  "ip": "10.9.x.x",
  "data": {
    "modelNumber": "M428fdw",
    "cartridges": {
      "black": "75%",
      "cyan": "60%",
      "yellow": "80%",
      "magenta": "65%"
    },
    "drums": {},
    "kits": {},
    "trays": {}
  }
}
```

**Error Handling:**
- Returns HTTP 500 if Python backend is unreachable
- Validates IP format before making request
- Logs all errors to PHP error log

---

#### 4. Get Current Day Checks
**Endpoint:** `?action=get_current_day_checks`  
**Method:** GET  

**Response:**
```json
{
  "success": true,
  "checks": [
    {
      "Checked_By": "John Doe",
      "Check_Date": "2025-01-29 14:30:00",
      "IP_Address": "10.9.x.x",
      "Install_Location": "CSI-101",
      "Black_Cartridge": "75%"
    }
  ],
  "count": 12
}
```

**Database Table:** `current_day_printer_checks`

---

#### 5. Get Active Students
**Endpoint:** `?action=get_active_students`  
**Method:** GET  

**Response:**
```json
{
  "success": true,
  "students": [
    {
      "student_name": "John Doe",
      "active": "1"
    }
  ],
  "count": 8
}
```

**Database Table:** `campus_student_workers`

---

#### 6. Submit Check
**Endpoint:** `?action=submit_check`  
**Method:** POST  
**Content-Type:** application/json

**Request Body:**
```json
{
  "Checked_By": "John Doe",
  "IP_Address": "10.9.x.x",
  "Install_Location": "CSI-101",
  "Model_Number": "M428fdw",
  "Black_Cartridge": "75%",
  "Cyan_Cartridge": "60%",
  "Yellow_Cartridge": "80%",
  "Magenta_Cartridge": "65%",
  "Black_Drum": "90%",
  "Cyan_Drum": "85%",
  "Yellow_Drum": "88%",
  "Magenta_Drum": "87%",
  "Maintenance_Kit": "Good",
  "Transfer_Kit": "Good",
  "Fuser_Kit": "Good",
  "Waste_Toner": "50%",
  "Tray_2": "Full",
  "Tray_3": "Full",
  "Tray_4": "Empty",
  "Tray_5": "Empty"
}
```

**Required Fields:**
- Checked_By
- IP_Address
- Install_Location
- Model_Number

**Database Table:** `printer_check`

---

#### 7. Get Last Scan
**Endpoint:** `?action=get_last_scan`  
**Method:** GET  

**Response:**
```json
{
  "success": true,
  "timestamp": "2025-01-29 14:30:00",
  "time_ago": "2 hours ago"
}
```

**Database Table:** `scan_run_log`

---

#### 8. Trigger Full Scan
**Endpoint:** `http://10.9.5.21:8080/full_send`  
**Method:** POST  

Triggers a full network scan of all printers. Records timestamp via `timestamp.php`.

---

## Lab Stats API

### Base Endpoint
```
http://10.9.5.21:3000/groups/{group_id}
```

### Available Endpoints

#### 1. Get Child Groups
**Endpoint:** `/groups/{parent_id}/groups`  
**Method:** GET  

**Example:**
```javascript
// Fetch child groups from two parent groups
const urls = [
  'http://10.9.5.21:3000/groups/1101/groups',
  'http://10.9.5.21:3000/groups/1116/groups'
];
```

**Response:**
```json
[
  {
    "id": 1101,
    "name": "Del Sesto Computer Labs"
  },
  {
    "id": 1116,
    "name": "Harborside Labs"
  }
]
```

---

#### 2. Get Offline Stations
**Endpoint:** `/groups/{group_id}/stations?status=offline`  
**Method:** GET  

**Response:**
```json
{
  "results": [
    {
      "name": "CSI-101-01",
      "ip_addresses": ["10.9.x.x"],
      "mac_addresses": ["00:11:22:33:44:55"],
      "operating_systems": [
        {
          "name": "Windows",
          "version": "10"
        }
      ],
      "host_name": "CSI-101-01",
      "manufacturer": "Dell",
      "model": "OptiPlex 7090",
      "form_factor": "Desktop",
      "remote_access_address": "",
      "client_version": "6.5.0",
      "allow_student_routing": false
    }
  ]
}
```

**Key Fields:**
- `name`: Station identifier
- `ip_addresses`: Array of IP addresses
- `mac_addresses`: Array of MAC addresses
- `operating_systems`: OS information
- `model`: Computer model
- `form_factor`: Desktop, Laptop, etc.

---

## Client-Side Integration

### Printer API Client (printer-client.js)

The `PrinterAPI` module provides a JavaScript interface to the printer API:

```javascript
// Get all printers for a campus
const printers = await PrinterAPI.getPrinters('downcity');

// Check a single printer
const status = await PrinterAPI.checkSinglePrinter('10.9.x.x');

// Parse printer data from treeview format
const parsed = PrinterAPI.parsePrinterData(printerData);

// Submit a check
await PrinterAPI.submitCheck(checkData);

// Trigger full scan
await PrinterAPI.triggerFullScan();
```

**Key Functions:**
- `getPrinters(campus)` - Fetch all printers for a campus
- `getPrinterStatus(ip)` - Get status from treeview.json
- `checkSinglePrinter(ip)` - Trigger live check via Python backend
- `getCurrentDayChecks()` - Get today's completed checks
- `getActiveStudents()` - Get active student workers
- `submitCheck(checkData)` - Submit a printer check
- `getLastScan()` - Get last scan timestamp
- `triggerFullScan()` - Trigger full network scan
- `parsePrinterData(data)` - Parse treeview format to structured data

---

## Error Handling

### Common Error Responses

**Invalid IP Address:**
```json
{
  "error": "Invalid IP address"
}
```

**Missing Required Field:**
```json
{
  "error": "Missing required field: Checked_By"
}
```

**Database Error:**
```json
{
  "error": "Failed to fetch printers"
}
```

**Backend Unavailable:**
```json
{
  "error": "Failed to connect to printer service: Connection refused",
  "http_code": 500
}
```

---

## Database Schema

### Tables Used

**printer_check**
- Checked_By (VARCHAR)
- Check_Date (DATETIME)
- IP_Address (VARCHAR)
- Install_Location (VARCHAR)
- Model_Number (VARCHAR)
- Black_Cartridge (VARCHAR)
- Cyan_Cartridge (VARCHAR)
- Yellow_Cartridge (VARCHAR)
- Magenta_Cartridge (VARCHAR)
- Black_Drum (VARCHAR)
- Cyan_Drum (VARCHAR)
- Yellow_Drum (VARCHAR)
- Magenta_Drum (VARCHAR)
- Maintenance_Kit (VARCHAR)
- Transfer_Kit (VARCHAR)
- Fuser_Kit (VARCHAR)
- Waste_Toner (VARCHAR)
- Tray_2 (VARCHAR)
- Tray_3 (VARCHAR)
- Tray_4 (VARCHAR)
- Tray_5 (VARCHAR)

**current_day_printer_checks**
- View or table containing today's checks only

**campus_student_workers**
- student_name (VARCHAR)
- active (TINYINT)

**scan_run_log**
- scan_run_date (DATETIME)

**harborside_printers / downcity_printers**
- IP_Address (VARCHAR)
- install_location (VARCHAR)
- model (VARCHAR)

---

## Backend Dependencies

### Python Backend
- **Host:** `10.9.5.21:8080`
- **Endpoints:**
  - `/printer/{ip}` - Check single printer
  - `/full_send` - Trigger full scan

### Database Connection
- Connection details in `../db_connection.php`
- Uses PDO for database operations
- MySQL database

### File Dependencies
- `treeview.json` - Cached printer status data
- `timestamp.php` - Records scan timestamps

---

## Rate Limiting & Performance

**Best Practices:**
- Cache `treeview.json` responses when possible
- Use `get_printer_status` for quick checks
- Use `check_single_printer` only when live data is needed
- Batch requests when checking multiple printers
- Full scans should be scheduled, not triggered frequently

**Timeouts:**
- API requests: 30 seconds
- Python backend: 30 seconds
- Database queries: Default PHP timeout
