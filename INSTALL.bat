@echo off
REM ============================================
REM Parish Management System - Windows Installer
REM Developer: Fr. Bastin - Trichy
REM Email: anito123789@gmail.com
REM ============================================

color 0B
title Parish Management System - Installer

echo.
echo ============================================
echo   Parish Management System - Installer
echo ============================================
echo   Developer: Fr. Bastin - Trichy
echo   Email: anito123789@gmail.com
echo ============================================
echo.

REM Check if running as Administrator
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [ERROR] This installer requires Administrator privileges.
    echo Please right-click and select "Run as Administrator"
    echo.
    pause
    exit /b 1
)

echo [1/6] Checking system requirements...
echo.

REM Check if PHP is installed
php -v >nul 2>&1
if %errorLevel% neq 0 (
    echo [WARNING] PHP is not installed or not in PATH
    echo.
    echo This application requires PHP 7.4 or higher.
    echo.
    echo Would you like to:
    echo 1. Continue installation (you'll need to install PHP manually)
    echo 2. Exit and install PHP first
    echo.
    choice /C 12 /N /M "Enter your choice (1 or 2): "
    if errorlevel 2 (
        echo.
        echo Please install PHP from: https://windows.php.net/download/
        echo After installation, run this installer again.
        pause
        exit /b 1
    )
) else (
    echo [OK] PHP is installed
    php -v | findstr /C:"PHP"
)

echo.
echo [2/6] Select installation directory...
echo.
echo Default: C:\ParishManagement
echo.
set /p INSTALL_DIR="Enter installation path (or press Enter for default): "

if "%INSTALL_DIR%"=="" (
    set INSTALL_DIR=C:\ParishManagement
)

echo.
echo Installation directory: %INSTALL_DIR%
echo.

REM Create installation directory
if not exist "%INSTALL_DIR%" (
    echo [3/6] Creating installation directory...
    mkdir "%INSTALL_DIR%"
    if %errorLevel% neq 0 (
        echo [ERROR] Failed to create directory: %INSTALL_DIR%
        pause
        exit /b 1
    )
    echo [OK] Directory created
) else (
    echo [3/6] Directory already exists
    echo [WARNING] Files may be overwritten
    echo.
    choice /C YN /N /M "Continue? (Y/N): "
    if errorlevel 2 exit /b 0
)

echo.
echo [4/6] Copying application files...

REM Copy all files from current directory to installation directory
xcopy /E /I /Y /Q "%~dp0*" "%INSTALL_DIR%\" >nul 2>&1
if %errorLevel% neq 0 (
    echo [ERROR] Failed to copy files
    pause
    exit /b 1
)
echo [OK] Files copied successfully

echo.
echo [5/6] Creating desktop shortcut...

REM Create desktop shortcut using PowerShell
powershell -Command "$WshShell = New-Object -ComObject WScript.Shell; $Shortcut = $WshShell.CreateShortcut('%USERPROFILE%\Desktop\Parish Management.lnk'); $Shortcut.TargetPath = '%INSTALL_DIR%\Launch Parish M.vbs'; $Shortcut.WorkingDirectory = '%INSTALL_DIR%'; $Shortcut.IconLocation = '%INSTALL_DIR%\assets\parish_icon.ico'; $Shortcut.Description = 'Parish Management System by Fr. Bastin'; $Shortcut.Save()"

if %errorLevel% equ 0 (
    echo [OK] Desktop shortcut created
) else (
    echo [WARNING] Could not create desktop shortcut
)

echo.
echo [6/6] Creating Start Menu entry...

REM Create Start Menu shortcut
set START_MENU=%APPDATA%\Microsoft\Windows\Start Menu\Programs
if not exist "%START_MENU%\Parish Management" mkdir "%START_MENU%\Parish Management"

powershell -Command "$WshShell = New-Object -ComObject WScript.Shell; $Shortcut = $WshShell.CreateShortcut('%START_MENU%\Parish Management\Parish Management.lnk'); $Shortcut.TargetPath = '%INSTALL_DIR%\Launch Parish M.vbs'; $Shortcut.WorkingDirectory = '%INSTALL_DIR%'; $Shortcut.IconLocation = '%INSTALL_DIR%\assets\parish_icon.ico'; $Shortcut.Description = 'Parish Management System'; $Shortcut.Save()"

if %errorLevel% equ 0 (
    echo [OK] Start Menu entry created
) else (
    echo [WARNING] Could not create Start Menu entry
)

echo.
echo ============================================
echo   Installation Complete!
echo ============================================
echo.
echo Installation directory: %INSTALL_DIR%
echo Desktop shortcut: Created
echo Start Menu: Created
echo.
echo To launch the application:
echo 1. Double-click "Parish Management" on your desktop
echo 2. Or run: %INSTALL_DIR%\Launch Parish M.bat
echo.
echo ============================================
echo   Developer Contact
echo ============================================
echo   Fr. Bastin - Trichy
echo   Email: anito123789@gmail.com
echo ============================================
echo.
echo Would you like to launch the application now?
choice /C YN /N /M "(Y/N): "
if errorlevel 2 goto :end

echo.
echo Launching Parish Management System...
cd /d "%INSTALL_DIR%"
start "" "%INSTALL_DIR%\Launch Parish M.vbs"

:end
echo.
echo Thank you for installing Parish Management System!
echo.
pause
