# Parish Management System - Installation Guide

## 📦 Installation Package

**Developer:** Fr. Bastin - Trichy  
**Email:** anito123789@gmail.com  
**Version:** 1.0

---

## 🎯 System Requirements

### Minimum Requirements:
- **Operating System:** Windows 10 or later
- **PHP:** Version 7.4 or higher
- **RAM:** 2 GB minimum (4 GB recommended)
- **Storage:** 500 MB free space
- **Browser:** Chrome, Firefox, or Edge (latest version)

### Required Software:
1. **PHP** (if not already installed)
   - Download from: https://windows.php.net/download/
   - Choose "Thread Safe" version
   - Extract to `C:\php`
   - Add to System PATH

---

## 📥 Installation Methods

### Method 1: Automated Installation (Recommended)

1. **Extract the ZIP file** to a temporary location
2. **Right-click** on `INSTALL.bat`
3. Select **"Run as Administrator"**
4. Follow the on-screen instructions
5. Choose installation directory (default: `C:\ParishManagement`)
6. Wait for installation to complete
7. Launch from Desktop shortcut

### Method 2: Manual Installation

1. **Extract** all files to your desired location (e.g., `C:\ParishManagement`)
2. **Open Command Prompt** as Administrator
3. Navigate to the installation folder:
   ```
   cd C:\ParishManagement
   ```
4. Run the launcher:
   ```
   "Launch Parish M.bat"
   ```

---

## 🚀 First-Time Setup

### 1. Launch the Application
- Double-click the **"Parish Management"** icon on your desktop
- Or use Start Menu → Parish Management

### 2. Initial Configuration
- The application will open in your default browser
- Default URL: `http://localhost:8000`
- First-time setup wizard will guide you through:
  - Database initialization
  - Parish profile setup
  - Admin account creation

### 3. Database Setup
- The SQLite database will be created automatically
- Location: `database/parish.db`
- No additional database server required

---

## 🔧 Configuration

### Parish Profile
1. Navigate to **Profile** in the menu
2. Fill in your parish details:
   - Church Name
   - Location
   - Contact Information
   - Upload Church Logo

### Backup Settings
1. Go to **DB Mgmt** (Database Management)
2. Configure automatic backups
3. Set backup frequency
4. Choose backup location

---

## 📁 Folder Structure

```
ParishManagement/
├── assets/              # Images, CSS, icons
├── database/            # SQLite database files
├── includes/            # PHP includes (header, footer)
├── php/                 # PHP runtime (if bundled)
├── uploads/             # User uploaded files
├── backups/             # Database backups
├── qrcodes/             # Generated QR codes
├── reports/             # Generated reports
├── index.php            # Main dashboard
├── Launch Parish M.bat  # Application launcher
└── INSTALL.bat          # Installer script
```

---

## 🎨 Features

### Core Features:
- ✅ Family Management
- ✅ Parishioner Records
- ✅ Sacrament Tracking (Baptism, Communion, Confirmation, Marriage)
- ✅ Subscription Management
- ✅ Receipt & Expense Vouchers
- ✅ Certificate Generation
- ✅ Report Generation
- ✅ PDF Form Templates
- ✅ QR Code Generation
- ✅ Database Backup & Restore

### Advanced Features:
- 📊 Statistical Dashboard
- 📅 Event Planner
- 📧 Email Integration
- 🖨️ Print-Ready Documents
- 💾 Auto-Backup System
- 🔍 Advanced Search & Filters

---

## 🆘 Troubleshooting

### Application Won't Start
**Problem:** Double-clicking the shortcut does nothing  
**Solution:**
1. Check if PHP is installed: Open CMD and type `php -v`
2. If not found, install PHP and add to PATH
3. Right-click launcher and "Run as Administrator"

### Port Already in Use
**Problem:** Error message "Port 8000 is already in use"  
**Solution:**
1. Close any other applications using port 8000
2. Or edit `Launch Parish M.bat` and change port number:
   ```
   php -S localhost:8001
   ```

### Database Error
**Problem:** "Database file not found" or "Cannot write to database"  
**Solution:**
1. Check if `database` folder exists
2. Ensure write permissions for the folder
3. Run application as Administrator
4. Restore from backup if available

### Blank Page or White Screen
**Problem:** Application shows blank page  
**Solution:**
1. Check PHP error logs
2. Enable error display in `config.php`
3. Verify all files were copied correctly
4. Clear browser cache

---

## 🔄 Updating the Application

### To Update to a New Version:
1. **Backup your database** (DB Mgmt → Create Backup)
2. **Download** the new version
3. **Extract** to a temporary folder
4. **Copy** your `database` folder from old installation
5. **Run** `INSTALL.bat` and choose the same installation directory
6. **Restore** your database if needed

---

## 📤 Uninstallation

### To Remove the Application:
1. **Backup your data** (if you want to keep it)
2. **Delete** the installation folder (e.g., `C:\ParishManagement`)
3. **Remove** desktop shortcut
4. **Remove** Start Menu entry:
   - `%APPDATA%\Microsoft\Windows\Start Menu\Programs\Parish Management`

---

## 💾 Backup & Data Safety

### Automatic Backups:
- Configured in **DB Mgmt** section
- Default: Daily backups
- Location: `backups` folder
- Retention: Last 30 backups

### Manual Backup:
1. Go to **DB Mgmt**
2. Click **"Create Backup Now"**
3. Save backup file to external drive/cloud storage

### Restore from Backup:
1. Go to **DB Mgmt**
2. Click **"Restore Database"**
3. Select backup file
4. Confirm restoration

---

## 📞 Support & Contact

### Developer Information:
**Name:** Fr. Bastin  
**Location:** Trichy  
**Email:** anito123789@gmail.com

### For Technical Support:
- Email your issue with screenshots
- Include error messages if any
- Mention your Windows version and PHP version

### For Feature Requests:
- Send detailed description via email
- Explain the use case
- Provide examples if possible

---

## 📜 License & Credits

**Parish Management System**  
© 2026 Fr. Bastin - Trichy  
All rights reserved.

This software is provided for parish administration purposes.  
Unauthorized distribution or modification is prohibited.

---

## 🙏 Acknowledgments

Thank you for using Parish Management System!  
May this tool help you serve your parish community better.

---

## 📋 Quick Reference

### Default Credentials (First Login):
- **Username:** admin
- **Password:** (set during first-time setup)

### Important URLs:
- **Dashboard:** http://localhost:8000
- **Help:** http://localhost:8000/help.php
- **Statistics:** http://localhost:8000/statistics.php

### Keyboard Shortcuts:
- `Ctrl + P` - Print current page
- `Ctrl + F` - Search
- `Esc` - Close modals

---

**Installation Guide Version:** 1.0  
**Last Updated:** January 2026  
**Developer:** Fr. Bastin - Trichy (anito123789@gmail.com)
