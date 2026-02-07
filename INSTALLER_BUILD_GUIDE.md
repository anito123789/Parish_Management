# 🏗️ Building Parish Management System Installer

## Prerequisites

### 1. Install Inno Setup 6
- Download from: https://jrsoftware.org/isdl.php
- Install with default settings
- Ensure it's installed in `C:\Program Files (x86)\Inno Setup 6\` or `C:\Program Files\Inno Setup 6\`

## Building the Installer

### Method 1: Automated Build (Recommended)
1. Double-click `Build-Installer.bat`
2. The script will automatically:
   - Check for Inno Setup installation
   - Create installer directory
   - Compile the setup script
   - Generate `Parish-Management-System-Setup.exe`

### Method 2: Manual Build
1. Open Inno Setup Compiler
2. Open `Parish-Management-Setup.iss`
3. Click **Build** → **Compile**
4. Installer will be created in `installer\` folder

## Installer Features

### ✅ Professional Windows Installer
- Modern wizard interface
- Proper Windows integration
- Start Menu and Desktop shortcuts
- Uninstaller included
- Administrator privileges handling

### ✅ Smart PHP Detection
- Checks if PHP is installed
- Warns user if PHP is missing
- Provides download link for PHP
- Allows continuation with manual PHP install

### ✅ Automatic Setup
- Creates application directory
- Sets up database folder
- Configures shortcuts with proper icons
- Launches application after installation

## Output

**Generated File:** `installer\Parish-Management-System-Setup.exe`

### Installer Properties:
- **Size:** ~5-10 MB (compressed)
- **Target:** Windows 10/11 (64-bit)
- **Privileges:** Administrator required
- **Compression:** LZMA (high compression)

## Distribution

The generated `Parish-Management-System-Setup.exe` is ready for:
- ✅ Email distribution
- ✅ USB/CD distribution  
- ✅ Network sharing
- ✅ Website download

## Customization

### To modify installer:
1. Edit `Parish-Management-Setup.iss`
2. Rebuild using `Build-Installer.bat`

### Common modifications:
- Change app version in `[Setup]` section
- Modify welcome messages in `[Messages]` section
- Add/remove files in `[Files]` section
- Customize shortcuts in `[Icons]` section

---

**Developer:** Fr. Bastin - Trichy  
**Email:** anito123789@gmail.com  
**Version:** 1.0