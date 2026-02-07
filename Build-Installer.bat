@echo off
echo ============================================
echo   Parish Management System - Build Installer
echo ============================================
echo   Developer: Fr. Bastin - Trichy
echo   Email: anito123789@gmail.com
echo ============================================
echo.

REM Check if Inno Setup is installed
if not exist "C:\Program Files (x86)\Inno Setup 6\ISCC.exe" (
    if not exist "C:\Program Files\Inno Setup 6\ISCC.exe" (
        echo [ERROR] Inno Setup 6 is not installed.
        echo.
        echo Please download and install Inno Setup 6 from:
        echo https://jrsoftware.org/isdl.php
        echo.
        pause
        exit /b 1
    )
    set ISCC="C:\Program Files\Inno Setup 6\ISCC.exe"
) else (
    set ISCC="C:\Program Files (x86)\Inno Setup 6\ISCC.exe"
)

echo [1/3] Creating installer directory...
if not exist "installer" mkdir installer
echo [OK] Directory created

echo.
echo [2/3] Compiling Inno Setup script...
%ISCC% "Parish-Management-Setup.iss"

if %errorLevel% equ 0 (
    echo [OK] Installer compiled successfully
    echo.
    echo [3/3] Installer created:
    echo installer\Parish-Management-System-Setup.exe
    echo.
    echo ============================================
    echo   Build Complete!
    echo ============================================
    echo.
    echo The installer is ready for distribution.
    echo File: installer\Parish-Management-System-Setup.exe
    echo.
) else (
    echo [ERROR] Failed to compile installer
    echo.
)

pause