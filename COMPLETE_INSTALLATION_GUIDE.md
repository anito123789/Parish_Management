# 📦 PARISH MANAGEMENT SYSTEM
## Complete Installation Guide for New PC

**Developer:** Fr. Bastin - Trichy  
**Email:** anito123789@gmail.com  
**Version:** 1.0  
**Last Updated:** January 2026

---

## 🎯 WHAT YOU'LL NEED

Before starting installation, ensure you have:

- ✅ **Windows PC** (Windows 10 or later)
- ✅ **Administrator access** to the computer
- ✅ **Internet connection** (for downloading PHP if needed)
- ✅ **500 MB free disk space**
- ✅ **Installation package** (ZIP file or USB drive)

---

## 📥 INSTALLATION METHODS

Choose the method that works best for you:

### **METHOD 1: Using the Automated Installer (RECOMMENDED)**
### **METHOD 2: Using Inno Setup Installer (Professional)**
### **METHOD 3: Manual Installation**

---

# METHOD 1: AUTOMATED INSTALLER (EASIEST)

## Step 1: Get the Installation Files

**Option A: From ZIP File**
1. Locate the `ParishManagement_v1.0_Setup.zip` file
2. Right-click on the ZIP file
3. Select **"Extract All..."**
4. Choose a location (e.g., Desktop)
5. Click **"Extract"**

**Option B: From USB Drive**
1. Insert the USB drive
2. Open the USB drive folder
3. Copy the entire `Parish Management` folder to your Desktop

## Step 2: Run the Installer

1. **Navigate** to the extracted folder
2. **Find** the file named `INSTALL.bat`
3. **Right-click** on `INSTALL.bat`
4. Select **"Run as Administrator"**
   
   ⚠️ **IMPORTANT:** You MUST run as Administrator!

5. If Windows shows a security warning:
   - Click **"More info"**
   - Click **"Run anyway"**

## Step 3: Follow Installation Wizard

The installer will now guide you through these steps:

### Screen 1: System Check
```
[1/6] Checking system requirements...
```
- The installer checks if PHP is installed
- If PHP is missing, you'll be asked if you want to continue
- **Recommendation:** Install PHP first for best experience

### Screen 2: Choose Installation Location
```
[2/6] Select installation directory...
Default: C:\ParishManagement
```
- Press **Enter** to use default location
- Or type a custom path (e.g., `D:\Parish`)
- Click **Enter**

### Screen 3: Confirm Installation
```
Installation directory: C:\ParishManagement
Continue? (Y/N):
```
- Type **Y** and press **Enter**

### Screen 4: File Copying
```
[4/6] Copying application files...
```
- Wait while files are copied (takes 30-60 seconds)

### Screen 5: Creating Shortcuts
```
[5/6] Creating desktop shortcut...
[6/6] Creating Start Menu entry...
```
- Shortcuts are created automatically

### Screen 6: Installation Complete!
```
============================================
  Installation Complete!
============================================
Would you like to launch the application now?
(Y/N):
```
- Type **Y** to launch immediately
- Or type **N** to launch later

## Step 4: First Launch

When you launch for the first time:

1. **Browser opens automatically** at `http://localhost:8000`
2. **Wait 5-10 seconds** for the server to start
3. You'll see the **Parish Management Dashboard**

🎉 **Congratulations! Installation complete!**

---

# METHOD 2: INNO SETUP INSTALLER (PROFESSIONAL)

## Prerequisites

First, you need to create the installer:

### On the Source PC (One-Time Setup):

1. **Download Inno Setup**
   - Visit: https://jrsoftware.org/isdl.php
   - Download "Inno Setup 6.x"
   - Install it

2. **Create the Installer**
   - Open Inno Setup Compiler
   - File → Open → Select `ParishManagement_Setup.iss`
   - Build → Compile
   - Wait for compilation
   - Find `ParishManagement_v1.0_Setup.exe` in the folder

### On the Target PC (Installation):

1. **Copy the Installer**
   - Copy `ParishManagement_v1.0_Setup.exe` to the new PC
   - Via USB drive, email, or network

2. **Run the Installer**
   - Double-click `ParishManagement_v1.0_Setup.exe`
   - Click **"Yes"** on UAC prompt

3. **Follow the Wizard**

   **Welcome Screen:**
   - Click **"Next"**

   **License Agreement:**
   - Read the license
   - Click **"I accept the agreement"**
   - Click **"Next"**

   **Installation Information:**
   - Read the installation guide
   - Click **"Next"**

   **Select Destination:**
   - Default: `C:\Program Files\ParishManagement`
   - Click **"Next"**

   **Select Start Menu Folder:**
   - Default: "Parish Management System"
   - Click **"Next"**

   **Select Additional Tasks:**
   - ✅ Check "Create a desktop icon"
   - Click **"Next"**

   **Ready to Install:**
   - Review settings
   - Click **"Install"**

   **Installing:**
   - Wait for installation (1-2 minutes)

   **Completing Setup:**
   - ✅ Check "Launch Parish Management System"
   - Click **"Finish"**

4. **First Launch**
   - Application opens in browser
   - Complete initial setup
   - Start using!

---

# METHOD 3: MANUAL INSTALLATION

For advanced users or troubleshooting:

## Step 1: Install PHP (If Not Already Installed)

1. **Download PHP**
   - Go to: https://windows.php.net/download/
   - Download **PHP 8.x Thread Safe** (ZIP)
   - Example: `php-8.2.x-Win32-vs16-x64.zip`

2. **Extract PHP**
   - Extract to `C:\php`
   - Folder structure: `C:\php\php.exe`

3. **Add PHP to PATH**
   - Right-click **"This PC"** → **Properties**
   - Click **"Advanced system settings"**
   - Click **"Environment Variables"**
   - Under "System variables", find **"Path"**
   - Click **"Edit"**
   - Click **"New"**
   - Add: `C:\php`
   - Click **"OK"** on all windows

4. **Verify PHP Installation**
   - Open **Command Prompt**
   - Type: `php -v`
   - You should see PHP version info

## Step 2: Copy Application Files

1. **Create Installation Folder**
   ```
   C:\ParishManagement
   ```

2. **Copy All Files**
   - Copy entire Parish Management folder contents
   - To: `C:\ParishManagement`

## Step 3: Create Shortcuts

**Desktop Shortcut:**
1. Right-click on Desktop → **New** → **Shortcut**
2. Location: `C:\ParishManagement\Launch Parish M.bat`
3. Name: `Parish Management`
4. Click **"Finish"**
5. Right-click shortcut → **Properties**
6. Click **"Change Icon"**
7. Browse to: `C:\ParishManagement\assets\parish_icon.ico`
8. Click **"OK"**

**Start Menu:**
1. Press **Win + R**
2. Type: `shell:programs`
3. Create folder: `Parish Management`
4. Create shortcut inside (same as above)

## Step 4: Initialize Database

1. **Open Command Prompt as Administrator**
2. Navigate to installation folder:
   ```
   cd C:\ParishManagement
   ```
3. Run initialization (if needed):
   ```
   php setup_staging.php
   ```

## Step 5: Launch Application

1. Double-click **"Parish Management"** on Desktop
2. Or run: `C:\ParishManagement\Launch Parish M.bat`
3. Browser opens at `http://localhost:8000`

---

## 🔧 POST-INSTALLATION SETUP

### First-Time Configuration

1. **Parish Profile Setup**
   - Click **"Profile"** in menu
   - Fill in:
     - Church Name
     - Location
     - Contact Details
   - Upload church logo
   - Click **"Save"**

2. **Database Backup Configuration**
   - Go to **"DB Mgmt"**
   - Set backup frequency
   - Choose backup location
   - Enable auto-backup

3. **Test the System**
   - Add a test family
   - Add test members
   - Generate a certificate
   - Create a backup
   - Delete test data

---

## ✅ VERIFICATION CHECKLIST

After installation, verify these work:

- [ ] Application launches from desktop shortcut
- [ ] Dashboard loads in browser
- [ ] Can add new family
- [ ] Can add parishioner
- [ ] Can generate certificate
- [ ] Can create backup
- [ ] All menu items accessible
- [ ] Reports generate correctly

---

## 🆘 TROUBLESHOOTING

### Problem: "PHP is not recognized"

**Solution:**
1. Install PHP (see Method 3, Step 1)
2. Add PHP to PATH
3. Restart computer
4. Try again

### Problem: "Port 8000 already in use"

**Solution:**
1. Close other applications using port 8000
2. Or edit `Launch Parish M.bat`:
   - Right-click → Edit
   - Change `8000` to `8001`
   - Save and try again

### Problem: "Permission denied" or "Access denied"

**Solution:**
1. Right-click application
2. Select **"Run as Administrator"**
3. Or change folder permissions:
   - Right-click installation folder
   - Properties → Security
   - Give "Full Control" to your user

### Problem: "Database error" or "Cannot create database"

**Solution:**
1. Check folder permissions
2. Run as Administrator
3. Manually create `database` folder
4. Restore from backup if available

### Problem: "Blank page" or "White screen"

**Solution:**
1. Clear browser cache (Ctrl + Shift + Delete)
2. Try different browser
3. Check PHP error logs
4. Verify all files copied correctly

### Problem: Application won't start

**Solution:**
1. Check if PHP is installed: `php -v`
2. Check if port is free: `netstat -ano | findstr :8000`
3. Run as Administrator
4. Check Windows Firewall settings

---

## 📞 GETTING HELP

### Self-Help Resources

1. **In-App Help**
   - Click **"Help"** in menu
   - Browse user manual
   - Check FAQ section

2. **Documentation**
   - `README.md` - Project overview
   - `QUICK_REFERENCE.md` - Quick tips
   - This file - Installation guide

### Contact Support

**Developer:** Fr. Bastin  
**Location:** Trichy, Tamil Nadu, India  
**Email:** anito123789@gmail.com

**When contacting support, include:**
- Description of the problem
- Screenshots of error messages
- Your Windows version
- PHP version (`php -v`)
- Steps you've already tried

**Response Time:** 24-48 hours

---

## 🔄 UPDATING TO NEW VERSION

When a new version is released:

1. **Backup Current Data**
   - DB Mgmt → Create Backup
   - Save to external drive

2. **Download New Version**
   - Get new installation package
   - Extract to temporary folder

3. **Run Installer**
   - Use same installation directory
   - Files will be updated

4. **Verify Data**
   - Check if data is intact
   - Restore from backup if needed

5. **Test New Features**
   - Review changelog
   - Test new functionality

---

## 🗑️ UNINSTALLATION

### If Installed with Inno Setup:

1. **Control Panel** → **Programs and Features**
2. Find **"Parish Management System"**
3. Click **"Uninstall"**
4. Follow wizard

### If Installed Manually:

1. **Backup Data** (if you want to keep it)
   - Copy `database` folder
   - Copy `backups` folder

2. **Delete Installation Folder**
   - Delete `C:\ParishManagement`

3. **Remove Shortcuts**
   - Delete desktop shortcut
   - Delete Start Menu folder

4. **Remove from PATH** (if added)
   - Environment Variables → Path
   - Remove PHP entry if not needed

---

## 💾 DATA MIGRATION

### Moving to Another PC:

1. **On Old PC:**
   - DB Mgmt → Create Backup
   - Save backup file
   - Copy to USB drive

2. **On New PC:**
   - Install Parish Management
   - DB Mgmt → Restore Database
   - Select backup file
   - Verify data

### Backup Best Practices:

- ✅ Daily automatic backups
- ✅ Weekly manual backups to external drive
- ✅ Monthly backups to cloud storage
- ✅ Before any major changes
- ✅ Before updates

---

## 📊 SYSTEM REQUIREMENTS DETAILS

### Minimum Requirements:
- **OS:** Windows 10 (64-bit)
- **Processor:** Intel Core i3 or equivalent
- **RAM:** 2 GB
- **Storage:** 500 MB free space
- **Display:** 1366x768 resolution
- **PHP:** 7.4 or higher

### Recommended Requirements:
- **OS:** Windows 11 (64-bit)
- **Processor:** Intel Core i5 or better
- **RAM:** 4 GB or more
- **Storage:** 2 GB free space (for data growth)
- **Display:** 1920x1080 resolution
- **PHP:** 8.x (latest stable)

---

## 🎓 TRAINING RESOURCES

### Getting Started:

1. **Watch Tutorial Videos** (Coming Soon)
2. **Read User Manual** (In-app Help section)
3. **Practice with Test Data**
4. **Contact for Training Session**

### Training Topics:

- Basic navigation
- Adding families and parishioners
- Managing sacraments
- Generating certificates
- Creating reports
- Backup and restore
- Advanced features

**For training inquiries:** anito123789@gmail.com

---

## 📜 CHANGELOG

### Version 1.0 (January 2026)
- Initial release
- Complete parish management features
- Certificate generation
- Report system
- Backup and restore

---

**Installation Guide Version:** 2.0  
**Last Updated:** January 30, 2026  
**Developer:** Fr. Bastin - Trichy  
**Email:** anito123789@gmail.com

---

## 🙏 THANK YOU!

Thank you for choosing Parish Management System.  
May this tool help you serve your parish community better.

**For any questions or support:**  
📧 anito123789@gmail.com

**God Bless!**  
Fr. Bastin - Trichy

---

*Keep this guide for future reference!*
