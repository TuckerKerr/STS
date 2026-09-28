# Frequently Asked Questions (FAQ)
 
## General Questions

### What is the STS system?
The Student Ticketing System (STS) is a comprehensive platform for managing IT support requests, equipment tracking, delivery intake, printer monitoring, and lab statistics at JWU. It streamlines communication between students, staff, and administrators.

### Who can access the STS system?
The system is accessible to authorized student workers and IT staff. Access requires login credentials stored in `sessionStorage`.

### What browsers are supported?
The system works best on modern browsers including Chrome, Firefox, Edge, and Safari. Some features (like camera access for Room Check) may have limited support on older browsers.

---

## Login & Access

### How do I log in?
Navigate to the login page and enter your credentials. The system uses `sessionStorage` to maintain your login state.

### I'm being redirected to the login page. Why?
This happens when:
- Your session has expired
- You haven't logged in yet
- Your `userName` is not set in `sessionStorage`

To fix: Log in again through the main login page.

### Can I stay logged in?
Your session persists until you close the browser tab or click "Sign Out". The system checks `sessionStorage.getItem('isLoggedIn')` on each page load.

---

## Printer Check

### How do I check a printer?
1. Navigate to Printer Check from the sidebar
2. Select your campus (Downcity or Harborside)
3. Click on a printer to view its status
4. Click "Check" to run a live diagnostic
5. Submit the check form with your findings

### What does "Check Single Printer" do?
It triggers a real-time diagnostic of the printer through the Python backend at `10.9.5.21:8080`. This provides current toner levels, drum status, and tray conditions.

### How often should printers be checked?
This depends on your lab's policy. The system tracks all checks in the database with timestamps for reference.

### What if a printer check fails?
Common causes:
- **Printer is offline**: Check network connection
- **Python backend unavailable**: Contact IT staff
- **Invalid IP address**: Verify the printer's IP in the database
- **Timeout error**: Printer may be slow to respond, try again

### Where is printer data stored?
- **Database Tables**: `harborside_printers`, `downcity_printers`, `printer_check`
- **Cached Data**: `treeview.json` (updated by network scans)
- **Live Data**: Retrieved from Python backend on demand

---

## Lab Stats

### What are Lab Stats?
Lab Stats shows real-time information about computer lab stations, including which computers are offline, their hardware specifications, and network details.

### How do I refresh Lab Stats?
Click the "Refresh Groups" button. This fetches the latest data from the Lab Stats API at `10.9.5.21:3000`.

### What does "offline" mean?
A station is marked offline when it hasn't communicated with the network management system recently. This could indicate:
- Computer is powered off
- Network connection issue
- System crash or freeze

### Can I see online stations too?
Currently, the system filters for `status=offline` stations only. To see all stations, the API endpoint would need to be modified.

### Which labs are monitored?
- **Del Sesto Computer Labs** (Group ID: 1101)
- **Harborside Labs** (Group ID: 1116)

---

## Forms

### What forms are available?
1. **Equipment Return** (EQ-Drop.html) - Track equipment drop-offs
2. **Delivery Intake** (Delivery-Intake.html) - Log incoming deliveries
3. **E-Waste** (E-Waste.html) - Document e-waste disposal
4. **Room Check** (Room-Check.html) - Inspect classroom technology
5. **Field Work** (Field-Work.html) - Log completed tasks
6. **Printer Check** (Printer.html) - Monitor printer status

### Why do forms track open and submit times?
Forms include hidden fields `form_open_time` and `form_submit_time` to measure how long it takes to complete each form. This helps identify forms that may be too complex or time-consuming.

### Can I save a form and finish it later?
No, forms must be completed in one session. Data is only saved when you click Submit.

### What happens when I submit a form?
Form data is sent to PHP scripts in `/FORM-PHP/` which:
1. Validate the data
2. Insert it into the appropriate database table
3. Redirect you to the main page or show a success message

---

## Delivery Intake

### What delivery types are supported?
- **Bulk**: Multiple items on pallets
- **Individual**: Single packages addressed to specific people
- **Toner**: Printer supplies
- **Other**: Miscellaneous items

### Do I need to contact the recipient?
For "Individual" deliveries, yes. The form requires you to specify:
- If person was contacted (Yes/No)
- Contact method (Email/Teams/Phone)
- Package location

### Where should I store packages?
Options include:
- Del Sesto Pick up bin
- Behind the student desk
- On leaders desk (high value items)
- Del Sesto Cage

---

## Room Check

### What do I inspect during a room check?
- Podium, Monitor, and I/O connections
- Projector/TV and lamp hours
- Control panel functionality (touch/buttons)

### How do I use the room check form?
1. Select a building
2. Enter the room number
3. The form shows which rooms have already been checked today
4. Check boxes for working equipment
5. Add notes in the text area
6. Submit

### Can I attach photos?
The system has camera capture functionality built in (`startCamera()`, `captureImage()`), though implementation may vary. Check with your supervisor about photo requirements.

---

## Troubleshooting

### The sidebar won't open on mobile
Click the hamburger menu (☰) icon. If it still doesn't work, try refreshing the page.

### Search isn't working
The search feature uses `testindexsearch.js`. Make sure:
- You've typed at least 3 characters
- The search index has loaded
- JavaScript is enabled

### Theme toggle isn't saving
The theme preference is saved to `localStorage`. If it's not persisting:
- Check browser settings allow localStorage
- Try clearing your cache and logging in again

### Forms aren't submitting
Check:
- All required fields are filled
- You're logged in (check `sessionStorage`)
- Network connection is stable
- Browser console for JavaScript errors

### I can't see the confetti animation
The confetti uses `js-confetti` library. Ensure:
- CDN is accessible: `cdn.jsdelivr.net`
- JavaScript is enabled
- No ad blockers interfering

---

## Database Questions

### Where is data stored?
The system uses a MySQL database with tables for:
- Student workers
- Printers (by campus)
- Printer checks
- Delivery intake
- E-waste logs
- Room checks
- Field work logs

### Can I export data?
Contact your supervisor or IT admin. They can run database queries or use the "Raw Data" section (if implemented).

### How long is data retained?
Retention policies vary by data type. Check with IT administration for specific policies.

---

## Technical Issues

### Python backend is unavailable
Error: "Failed to connect to printer service"

**Solution**: The Python backend at `10.9.5.21:8080` may be down. Contact IT staff to restart the service.

### Database connection failed
Error: "Connection failed" or "Failed to fetch"

**Solution**: 
- Check network connectivity
- Verify database server is running
- Contact IT if problem persists

### Camera access denied
Error: "Your browser does not support camera access"

**Solution**:
- Grant camera permissions in browser settings
- Use HTTPS connection (required for camera access)
- Try a different browser

---

## Best Practices

### When should I log field work?
Log any significant task completion, including:
- Ticket resolutions
- Proactive maintenance
- Special projects

### How detailed should my notes be?
Be specific but concise:
- **Good**: "Replaced toner cartridge in CSI-101. Black at 5%."
- **Poor**: "Fixed printer"

### What if I make a mistake on a form?
Contact your supervisor immediately. Database records may need to be corrected manually.

---

## Contact & Support

### Who do I contact for help?
Staff members available:
- Adam
- Tom
- Jerson
- Shea
- Tony
- Jorge
- Sarah
- Stefan

### How do I report a bug?
Document the issue including:
- What you were trying to do
- What happened instead
- Any error messages
- Browser and OS version

Then contact IT staff or your supervisor.

### Can I request new features?
Yes! Feature requests should be directed to your supervisor or the development team.
