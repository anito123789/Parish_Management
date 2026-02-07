# 🚀 QUICK START INSTALLATION GUIDE
## Parish Management System - Visual Guide

**Developer:** Fr. Bastin - Trichy | **Email:** anito123789@gmail.com

---

## 📋 PRE-INSTALLATION CHECKLIST

```
Before you begin, make sure you have:

□ Windows 10 or later
□ Administrator access
□ 500 MB free disk space
□ Installation package (ZIP file or USB)
□ 10-15 minutes of time
```

---

## 🎯 INSTALLATION FLOWCHART

```
START
  │
  ├─→ Do you have the installation files?
  │   ├─ YES → Continue to Step 1
  │   └─ NO  → Get files from USB/Email/Download
  │
  ├─→ STEP 1: Extract Files
  │   └─→ Right-click ZIP → Extract All
  │
  ├─→ STEP 2: Run Installer
  │   └─→ Right-click INSTALL.bat → Run as Administrator
  │
  ├─→ STEP 3: Follow Wizard
  │   ├─→ System Check (Auto)
  │   ├─→ Choose Location (Default: C:\ParishManagement)
  │   ├─→ Confirm Installation (Press Y)
  │   ├─→ Wait for Files to Copy (30-60 sec)
  │   └─→ Shortcuts Created (Auto)
  │
  ├─→ STEP 4: Launch Application
  │   └─→ Press Y to launch OR Double-click desktop icon
  │
  ├─→ STEP 5: Browser Opens
  │   └─→ Wait 5-10 seconds for server to start
  │
  └─→ SUCCESS! Dashboard appears
      └─→ Start using Parish Management System
```

---

## 🖼️ VISUAL STEP-BY-STEP

### STEP 1: EXTRACT FILES

```
┌─────────────────────────────────────┐
│  ParishManagement_v1.0_Setup.zip    │
│                                     │
│  [Right-click]                      │
│    ↓                                │
│  Extract All...                     │
│    ↓                                │
│  Choose: Desktop                    │
│    ↓                                │
│  [Extract]                          │
└─────────────────────────────────────┘
```

### STEP 2: RUN INSTALLER

```
┌─────────────────────────────────────┐
│  📁 Parish Management               │
│    ├─ 📄 INSTALL.bat  ← THIS ONE!  │
│    ├─ 📄 README.md                 │
│    ├─ 📄 LICENSE.txt               │
│    └─ 📂 [other files]             │
│                                     │
│  [Right-click INSTALL.bat]          │
│    ↓                                │
│  Run as Administrator               │
│    ↓                                │
│  [Yes] (UAC Prompt)                 │
└─────────────────────────────────────┘
```

### STEP 3: INSTALLATION WIZARD

```
┌─────────────────────────────────────┐
│ Parish Management System - Installer│
│ Developer: Fr. Bastin - Trichy      │
│ Email: anito123789@gmail.com        │
├─────────────────────────────────────┤
│                                     │
│ [1/6] Checking system requirements  │
│ [OK] PHP is installed               │
│                                     │
│ [2/6] Select installation directory │
│ Default: C:\ParishManagement        │
│ → Press ENTER                       │
│                                     │
│ [3/6] Directory already exists?     │
│ Continue? (Y/N): Y                  │
│                                     │
│ [4/6] Copying files... ████████ 100%│
│ [OK] Files copied successfully      │
│                                     │
│ [5/6] Creating desktop shortcut     │
│ [OK] Desktop shortcut created       │
│                                     │
│ [6/6] Creating Start Menu entry     │
│ [OK] Start Menu entry created       │
│                                     │
│ ✅ Installation Complete!           │
│                                     │
│ Launch now? (Y/N): Y                │
└─────────────────────────────────────┘
```

### STEP 4: APPLICATION LAUNCHES

```
┌─────────────────────────────────────┐
│  🌐 Browser Opens Automatically     │
│                                     │
│  URL: http://localhost:8000         │
│                                     │
│  ┌───────────────────────────────┐ │
│  │ 🏛️ Parish Management System   │ │
│  │                               │ │
│  │ 📊 Dashboard                  │ │
│  │                               │ │
│  │ ┌─────────┐ ┌─────────┐      │ │
│  │ │Families │ │Parishio-│      │ │
│  │ │   150   │ │ ners    │      │ │
│  │ └─────────┘ └─────────┘      │ │
│  │                               │ │
│  │ Welcome to Parish Management! │ │
│  └───────────────────────────────┘ │
└─────────────────────────────────────┘
```

---

## ⚡ QUICK REFERENCE

### Installation Time
```
Extract Files:     1-2 minutes
Run Installer:     2-3 minutes
First Launch:      1 minute
Total Time:        5-10 minutes
```

### Installation Locations
```
Default Install:   C:\ParishManagement
Desktop Shortcut:  Desktop\Parish Management
Start Menu:        Start → Parish Management
Database:          C:\ParishManagement\database
Backups:           C:\ParishManagement\backups
```

### Important Files
```
📄 INSTALL.bat                  → Main installer
📄 Launch Parish M.bat          → Application launcher
📄 COMPLETE_INSTALLATION_GUIDE  → Full guide
📄 QUICK_REFERENCE.md           → Quick tips
📄 README.md                    → Project info
🖼️ assets/parish_icon.ico       → Application icon
```

---

## 🔧 COMMON ISSUES & QUICK FIXES

### Issue 1: "PHP not found"
```
Problem: PHP is not installed
Solution:
  1. Download PHP from: windows.php.net/download
  2. Extract to C:\php
  3. Add C:\php to System PATH
  4. Restart installer
```

### Issue 2: "Permission denied"
```
Problem: Not running as Administrator
Solution:
  1. Right-click INSTALL.bat
  2. Select "Run as Administrator"
  3. Click "Yes" on UAC prompt
```

### Issue 3: "Port 8000 in use"
```
Problem: Another app using port 8000
Solution:
  1. Close other applications
  2. Or edit Launch Parish M.bat
  3. Change 8000 to 8001
  4. Save and relaunch
```

### Issue 4: "Blank page"
```
Problem: Browser cache or PHP error
Solution:
  1. Clear browser cache (Ctrl+Shift+Del)
  2. Refresh page (F5)
  3. Try different browser
  4. Check if PHP is running
```

---

## 📱 AFTER INSTALLATION

### First Steps:
```
1. ⛪ Set up Parish Profile
   → Menu → Profile → Fill details → Save

2. 🖼️ Upload Church Logo
   → Profile → Upload Image → Save

3. 💾 Configure Backups
   → DB Mgmt → Set frequency → Enable auto-backup

4. 👥 Add First Family
   → Families → Add New → Fill form → Save

5. 📜 Generate Test Certificate
   → Certificates → Select type → Generate
```

---

## 🎯 KEYBOARD SHORTCUTS

```
Ctrl + P     →  Print current page
Ctrl + F     →  Search
Ctrl + S     →  Save (in forms)
Esc          →  Close modal/popup
F5           →  Refresh page
F11          →  Fullscreen mode
```

---

## 📞 NEED HELP?

### Self-Help:
```
1. Check COMPLETE_INSTALLATION_GUIDE.md
2. Read QUICK_REFERENCE.md
3. Browse in-app Help section
4. Check troubleshooting section
```

### Contact Support:
```
Developer:  Fr. Bastin
Location:   Trichy, Tamil Nadu, India
Email:      anito123789@gmail.com
Response:   24-48 hours

Include in email:
  - Screenshot of error
  - Windows version
  - PHP version (run: php -v)
  - Steps you tried
```

---

## ✅ POST-INSTALLATION CHECKLIST

```
After installation, verify:

□ Desktop shortcut works
□ Application launches in browser
□ Dashboard loads correctly
□ Can add new family
□ Can add parishioner
□ Can generate certificate
□ Can create backup
□ All menu items accessible
□ Reports work properly
□ Print function works

If all checked: ✅ Installation successful!
```

---

## 🎓 LEARNING RESOURCES

### Getting Started:
```
1. Dashboard Tour
   → Click "Help" → "Getting Started"

2. Video Tutorials (Coming Soon)
   → Check email for updates

3. User Manual
   → Menu → Help → User Guide

4. Practice Mode
   → Add test data
   → Generate sample reports
   → Delete test data when done
```

---

## 🔄 UPDATING

### When New Version Available:
```
1. Backup Data
   → DB Mgmt → Create Backup → Save

2. Download New Version
   → Check email for link

3. Run Installer
   → Same steps as initial install
   → Use same directory

4. Verify Data
   → Check if all data intact
   → Restore from backup if needed
```

---

## 📊 SYSTEM STATUS

### Check Health:
```
Menu → Statistics → System Info

Shows:
  - Database size
  - Number of records
  - Backup status
  - PHP version
  - Disk space
  - Last backup date
```

---

## 🌟 PRO TIPS

```
💡 Tip 1: Regular Backups
   → Set daily auto-backup
   → Manual backup before major changes

💡 Tip 2: Use Filters
   → Quick search in all lists
   → Filter by date, status, etc.

💡 Tip 3: Keyboard Shortcuts
   → Learn common shortcuts
   → Faster navigation

💡 Tip 4: Customize Templates
   → Edit certificate templates
   → Add parish branding

💡 Tip 5: Export Data
   → Regular exports to Excel
   → Keep offline copies
```

---

## 📋 INSTALLATION SUMMARY

```
┌─────────────────────────────────────┐
│  INSTALLATION COMPLETE!             │
├─────────────────────────────────────┤
│                                     │
│  ✅ Files installed                 │
│  ✅ Shortcuts created               │
│  ✅ Database initialized            │
│  ✅ Application running             │
│                                     │
│  📍 Location:                       │
│     C:\ParishManagement             │
│                                     │
│  🚀 Launch:                         │
│     Desktop → Parish Management     │
│                                     │
│  🌐 URL:                            │
│     http://localhost:8000           │
│                                     │
│  📞 Support:                        │
│     anito123789@gmail.com           │
│                                     │
│  👨‍💼 Developer:                      │
│     Fr. Bastin - Trichy             │
│                                     │
└─────────────────────────────────────┘
```

---

**Quick Start Guide Version:** 1.0  
**Developer:** Fr. Bastin - Trichy  
**Email:** anito123789@gmail.com  
**© 2026 All Rights Reserved**

---

**🙏 Thank you for using Parish Management System!**

*For detailed instructions, see: COMPLETE_INSTALLATION_GUIDE.md*
