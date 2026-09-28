# System Setup & Maintenance Guide

## Project Structure
 
### Directory Layout
```
/sts/
├── Docs/                    # Documentation (Markdown files)
├── INDEX-HTML/              # Main HTML pages
│   └── main.html           # Dashboard/home page
├── INDEX-JS/                # Shared JavaScript modules
│   ├── Wrapper.js          # Sidebar and navigation
│   ├── testindexsearch.js  # Search functionality
│   └── ...
├── FORM-PHP/                # Form processing scripts
│   ├── DiSubmit.php        # Delivery Intake submission
│   ├── EqSubmit.php        # Equipment Drop submission
│   ├── EwSubmit.php        # E-Waste submission
│   ├── FWSubmit.php        # Field Work submission
│   ├── RcSubmit.php        # Room Check submission
│   └── roomsChecked.php    # Room check history
├── PrinterCheck/            # Printer monitoring system
│   ├── Printer.html        # Printer check interface
│   ├── printer-api.php     # Printer API endpoint
│   ├── printer-client.js   # Client-side API wrapper
│   ├── printer-usage.js    # UI logic
│   ├── treeview.json       # Cached printer status
│   └── timestamp.php       # Scan timestamp logging
├── Lab-Stats/               # Lab statistics system
│   ├── Lab-Stats.html      # Lab stats interface
│   ├── labstats.js         # Lab stats logic
│   └── LSstyle.css         # Lab stats styling
├── ASSETS/                  # Images, icons, logos
├── Styles.css               # Global stylesheet
├── global.js                # Global JavaScript utilities
└── db_connection.php        # Database configuration
```

---

## Installation & Configuration

### Prerequisites

**Server Requirements:**
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache or Nginx web server
- Python 3.8+ (for printer backend)

**Network Requirements:**
- Access to internal network `10.9.5.x`
- Port 3000 open (Lab Stats API)
- Port 8080 open (Python backend)

### Step 1: Clone/Deploy Files

```bash
# Deploy to web directory
cp -r /sts /var/services/web/

# Set proper permissions
chown -R www-data:www-data /var/services/web/sts
chmod -R 755 /var/services/web/sts
```

### Step 2: Database Setup

**Create Database:**
```sql
CREATE DATABASE sts_system;
USE sts_system;
```

**Import Schema:**
```sql
-- Printer tables
CREATE TABLE harborside_printers (
    IP_Address VARCHAR(15) PRIMARY KEY,
    install_location VARCHAR(100),
    model VARCHAR(100)
);

CREATE TABLE downcity_printers (
    IP_Address VARCHAR(15) PRIMARY KEY,
    install_location VARCHAR(100),
    model VARCHAR(100)
);

-- Printer checks
CREATE TABLE printer_check (
    id INT AUTO_INCREMENT PRIMARY KEY,
    Checked_By VARCHAR(100),
    Check_Date DATETIME,
    IP_Address VARCHAR(15),
    Install_Location VARCHAR(100),
    Model_Number VARCHAR(100),
    Black_Cartridge VARCHAR(50),
    Cyan_Cartridge VARCHAR(50),
    Yellow_Cartridge VARCHAR(50),
    Magenta_Cartridge VARCHAR(50),
    Black_Drum VARCHAR(50),
    Cyan_Drum VARCHAR(50),
    Yellow_Drum VARCHAR(50),
    Magenta_Drum VARCHAR(50),
    Maintenance_Kit VARCHAR(50),
    Transfer_Kit VARCHAR(50),
    Fuser_Kit VARCHAR(50),
    Waste_Toner VARCHAR(50),
    Tray_2 VARCHAR(50),
    Tray_3 VARCHAR(50),
    Tray_4 VARCHAR(50),
    Tray_5 VARCHAR(50),
    INDEX idx_date (Check_Date),
    INDEX idx_ip (IP_Address)
);

-- Current day checks view
CREATE VIEW current_day_printer_checks AS
SELECT * FROM printer_check
WHERE DATE(Check_Date) = CURDATE();

-- Student workers
CREATE TABLE campus_student_workers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(100),
    active TINYINT(1) DEFAULT 1,
    INDEX idx_active (active)
);

-- Scan log
CREATE TABLE scan_run_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scan_run_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date (scan_run_date)
);

-- Additional tables for forms (examples)
CREATE TABLE delivery_intake (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date_received DATE,
    tracking_number VARCHAR(100),
    delivery_company VARCHAR(100),
    type_of_delivery VARCHAR(50),
    -- Add additional fields based on your forms
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE equipment_dropoff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    deliverer VARCHAR(255),
    asset_tag VARCHAR(6),
    staff_member_assigned VARCHAR(100),
    additional_info TEXT,
    form_open_time DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE ewaste_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campus VARCHAR(50),
    device_type VARCHAR(50),
    asset_tag VARCHAR(6),
    model_number VARCHAR(255),
    serial_number VARCHAR(255),
    ssd_serial VARCHAR(255),
    form_open_time DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE field_work (
    id INT AUTO_INCREMENT PRIMARY KEY,
    work_description TEXT,
    ticket_number VARCHAR(50),
    difficulty_rating INT,
    submitted_by VARCHAR(100),
    form_submit_time DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE room_checks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date_checked DATE,
    building VARCHAR(50),
    room_number VARCHAR(50),
    podium_monitor_io VARCHAR(20),
    projector_tv VARCHAR(20),
    control_panel VARCHAR(20),
    room_notes TEXT,
    room_image BLOB,
    checked_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_building (building),
    INDEX idx_date (date_checked)
);
```

### Step 3: Configure Database Connection

Edit `/sts/db_connection.php`:
```php
<?php
$servername = "localhost";  // or your database server
$username = "sts_user";
$password = "your_secure_password";
$dbname = "sts_system";
?>
```

This file only holds credentials. Scripts that need a `mysqli` connection (e.g. `login.php`) build it themselves after including this file. Scripts that need PDO instead include `/sts/pdo_connect.php`, which itself includes `db_connection.php` and builds the shared `$conn` PDO instance:
```php
<?php
// /sts/pdo_connect.php
include(__DIR__ . '/db_connection.php');
$conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
```

So a PDO-based script one directory below the root (`INDEX-PHP/`, `FORM-PHP/`, `PrinterCheck/`) just does `include('../pdo_connect.php');` inside its `try` block instead of repeating the connection setup.

**Security:** Never commit `db_connection.php` to version control. Use environment variables in production.

### Step 4: Python Backend Setup

**Install Dependencies:**
```bash
pip3 install flask requests pysnmp
```

**Create Printer Service** (`/opt/printer-service/app.py`):
```python
from flask import Flask, jsonify
from pysnmp.hlapi import *
import sys

app = Flask(__name__)

@app.route('/printer/<ip>', methods=['GET'])
def check_printer(ip):
    # SNMP OIDs for printer info
    # This is a simplified example
    try:
        # Query printer via SNMP
        result = get_printer_status(ip)
        return jsonify(result), 200
    except Exception as e:
        return jsonify({"error": str(e)}), 500

@app.route('/full_send', methods=['POST'])
def full_scan():
    # Trigger full network scan
    # Implementation depends on your network setup
    return jsonify({"status": "Scan initiated"}), 200

def get_printer_status(ip):
    # Implement SNMP queries here
    pass

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=8080)
```

**Create Systemd Service** (`/etc/systemd/system/printer-service.service`):
```ini
[Unit]
Description=Printer Monitoring Service
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/opt/printer-service
ExecStart=/usr/bin/python3 /opt/printer-service/app.py
Restart=always

[Install]
WantedBy=multi-user.target
```

**Enable and Start:**
```bash
sudo systemctl enable printer-service
sudo systemctl start printer-service
sudo systemctl status printer-service
```

### Step 5: Lab Stats API

The Lab Stats API at `10.9.5.21:3000` should already be running. Verify connectivity:
```bash
curl http://10.9.5.21:3000/groups/1101/groups
```

If this fails, contact your network administrator to ensure:
- Service is running
- Firewall allows port 3000
- API authentication is configured

---

## Configuration Files

### Styles.css
Global stylesheet for the entire application. Includes:
- Dark mode variables
- Sidebar styling
- Form styling
- Responsive design

### global.js
Shared JavaScript functions:
```javascript
// Navigation setup for sidebar buttons
document.getElementById('tab1').onclick = () => window.location.href = '/sts/EQ-Drop.html';
document.getElementById('tab2').onclick = () => window.location.href = '/sts/Delivery-Intake.html';
// etc...
```

### Wrapper.js
Handles:
- Sidebar toggle functionality
- Mobile overlay
- User profile dropdown
- Theme switching (dark/light mode)
- Username display from sessionStorage

---

## Authentication Setup

### Session Management

Currently uses `sessionStorage` for client-side session tracking:
```javascript
// Check on page load
if (sessionStorage.getItem('userName') === null || 
    sessionStorage.getItem('isLoggedIn') !== 'true') {
    window.location.href = '../login.html';
}
```

**To implement secure authentication:**
1. Create login.html with form
2. Implement server-side session in PHP
3. Use `$_SESSION` instead of `sessionStorage`
4. Add CSRF protection
5. Implement logout functionality

### Adding Users

```sql
-- Create users table (if not exists)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password_hash VARCHAR(255),
    role VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add user
INSERT INTO users (username, password_hash, role)
VALUES ('johndoe', PASSWORD('secure_password'), 'student_worker');
```

**Use proper password hashing:**
```php
$password_hash = password_hash($password, PASSWORD_BCRYPT);
```

---

## Maintenance Tasks

### Daily
- Monitor error logs: `/var/log/apache2/error.log`
- Check Python backend status: `systemctl status printer-service`
- Verify database connections

### Weekly
- Review printer check submissions
- Check for failed form submissions
- Verify Lab Stats API connectivity

### Monthly
- Database backups
- Clean up old logs
- Update dependencies
- Review user access

### Database Backup
```bash
# Backup
mysqldump -u root -p sts_system > sts_backup_$(date +%Y%m%d).sql

# Restore
mysql -u root -p sts_system < sts_backup_20250129.sql
```

---

## Troubleshooting

### Forms Not Submitting

**Check PHP error log:**
```bash
tail -f /var/log/apache2/error.log
```

**Common issues:**
- Missing database connection
- Form field validation failing
- File permissions on upload directory

### Printer API Not Working

**Check Python backend:**
```bash
sudo systemctl status printer-service
sudo journalctl -u printer-service -n 50
```

**Test endpoint directly:**
```bash
curl http://10.9.5.21:8080/printer/10.9.1.100
```

### Lab Stats Not Loading

**Verify API:**
```bash
curl http://10.9.5.21:3000/groups/1101/groups
```

**Check browser console for CORS errors**

**Add CORS headers if needed** (in API configuration):
```javascript
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST
```

---

## Development Guidelines

### Adding New Forms

1. Create HTML file in `/sts/` (e.g., `New-Form.html`)
2. Include form tracking:
```html
<input type="hidden" id="form_open_time" name="form_open_time">
<input type="hidden" id="form_submit_time" name="form_submit_time">
```

3. Add JavaScript timing:
```javascript
function recordFormOpenTime() {
    document.getElementById('form_open_time').value = new Date().toISOString();
}
function recordFormSubmitTime() {
    document.getElementById('form_submit_time').value = new Date().toISOString();
}
```

4. Create PHP processor in `/FORM-PHP/`
5. Add sidebar button in `global.js`
6. Update database schema if needed

### Code Style

**JavaScript:**
- Use async/await for asynchronous operations
- Add error handling with try/catch
- Include console.log for debugging
- Comment complex logic

**PHP:**
- Use prepared statements (prevent SQL injection)
- Validate all inputs
- Return JSON for APIs
- Log errors appropriately

**HTML:**
- Use semantic tags
- Include ARIA labels for accessibility
- Validate forms client-side
- Mobile-responsive design

### Version Control

```bash
# Initialize Git (if not already)
cd /var/services/web/sts
git init
git add .
git commit -m "Initial commit"

# Add remote (as per GitDoc.MD)
git remote add nas ssh://username@10.9.5.21/var/services/web/sts
git push -u nas main
```

**See GitDoc.MD for full Git workflow**

---

## Documentation Standards

All documentation must follow these rules:

1. **Location**: Save in `/Docs` folder
2. **Format**: Markdown (`.md`) files
3. **Naming**: Descriptive, lowercase with hyphens (e.g., `printer-api.md`)
4. **Structure**: Use proper headings, code blocks, and lists
5. **Examples**: Include code examples where applicable
6. **Updates**: Update docs when code changes

---

## Performance Optimization

### Caching Strategy
- `treeview.json` caches printer status (updated by scheduled scans)
- Lab Stats data refreshed on demand
- Consider implementing Redis for session management

### Database Optimization
- Add indexes on frequently queried columns
- Archive old data annually
- Optimize slow queries

### Frontend Optimization
- Minify CSS/JS for production
- Use CDN for libraries (Font Awesome, etc.)
- Lazy load images
- Enable browser caching

---

## Security Considerations

### Critical Security Measures

1. **Never commit** `db_connection.php` to Git
2. **Use HTTPS** in production (required for camera access)
3. **Validate all inputs** server-side
4. **Use prepared statements** for SQL queries
5. **Implement CSRF protection** for forms
6. **Sanitize file uploads** (Room Check images)
7. **Limit API access** to internal network only
8. **Regular security updates** for dependencies

### File Upload Security (Room Check)

```php
// Example validation
$allowed_types = ['image/jpeg', 'image/png'];
$max_size = 5 * 1024 * 1024; // 5MB

if (!in_array($_FILES['room_image']['type'], $allowed_types)) {
    die("Invalid file type");
}
if ($_FILES['room_image']['size'] > $max_size) {
    die("File too large");
}
```

---

## Deployment Checklist

Before deploying to production:

- [ ] Database credentials secured
- [ ] PHP error display turned off (`display_errors = Off`)
- [ ] All forms tested
- [ ] APIs returning expected data
- [ ] Python backend running and monitored
- [ ] Backup strategy in place
- [ ] Error logging configured
- [ ] HTTPS enabled
- [ ] File permissions set correctly (755 for directories, 644 for files)
- [ ] Git configured (see GitDoc.MD)
- [ ] Documentation complete

---

## Support & Resources

**Internal Resources:**
- Network: `10.9.5.x`
- Database Server: Contact IT Admin
- Python Backend: `10.9.5.21:8080`
- Lab Stats API: `10.9.5.21:3000`

**External Dependencies:**
- Font Awesome CDN: `https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/`
- Marked.js: `https://cdn.jsdelivr.net/npm/marked/marked.min.js`
- js-confetti: `https://cdn.jsdelivr.net/npm/js-confetti@latest/`
- Highlight.js: `https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/`

**Documentation Files:**
- GitDoc.MD - Git workflow
- GitGPG.MD - GPG signing
- api.md - API reference
- faq.md - User FAQ
- intro.md - System overview
