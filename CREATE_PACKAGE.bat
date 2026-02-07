@echo off
REM ============================================
REM Parish Management System - Package Creator
REM Creates a distribution-ready ZIP file
REM Developer: Fr. Bastin - Trichy
REM ============================================

color 0E
title Parish Management System - Package Creator

echo.
echo ============================================
echo   Parish Management System
echo   Package Creator
echo ============================================
echo   Developer: Fr. Bastin - Trichy
echo   Email: anito123789@gmail.com
echo ============================================
echo.

set SOURCE_DIR=%~dp0
set PACKAGE_NAME=ParishManagement_v1.0_Setup
set DESKTOP=%USERPROFILE%\Desktop

echo [1/4] Preparing package...
echo.
echo Source Directory: %SOURCE_DIR%
echo Package Name: %PACKAGE_NAME%.zip
echo.

REM Check if 7-Zip or PowerShell is available for compression
where powershell >nul 2>&1
if %errorLevel% neq 0 (
    echo [ERROR] PowerShell not found. Cannot create package.
    pause
    exit /b 1
)

echo [2/4] Creating distribution folder...

REM Create temporary distribution folder
set DIST_DIR=%TEMP%\%PACKAGE_NAME%
if exist "%DIST_DIR%" rmdir /s /q "%DIST_DIR%"
mkdir "%DIST_DIR%"

echo [3/4] Copying files...

REM Copy all necessary files
xcopy /E /I /Y /Q "%SOURCE_DIR%*" "%DIST_DIR%\" /EXCLUDE:%SOURCE_DIR%exclude_list.txt >nul 2>&1

REM Exclude certain files/folders from package
echo .git > "%TEMP%\exclude.txt"
echo .gemini >> "%TEMP%\exclude.txt"
echo node_modules >> "%TEMP%\exclude.txt"
echo *.log >> "%TEMP%\exclude.txt"
echo qr_errors.log >> "%TEMP%\exclude.txt"

REM Copy essential files
copy /Y "%SOURCE_DIR%INSTALL.bat" "%DIST_DIR%\" >nul
copy /Y "%SOURCE_DIR%README.md" "%DIST_DIR%\" >nul
copy /Y "%SOURCE_DIR%INSTALLATION_GUIDE.md" "%DIST_DIR%\" >nul
copy /Y "%SOURCE_DIR%Launch Parish M.bat" "%DIST_DIR%\" >nul

REM Create a START_HERE.txt file
echo ============================================ > "%DIST_DIR%\START_HERE.txt"
echo   Parish Management System v1.0 >> "%DIST_DIR%\START_HERE.txt"
echo ============================================ >> "%DIST_DIR%\START_HERE.txt"
echo. >> "%DIST_DIR%\START_HERE.txt"
echo QUICK START: >> "%DIST_DIR%\START_HERE.txt"
echo. >> "%DIST_DIR%\START_HERE.txt"
echo 1. Right-click on INSTALL.bat >> "%DIST_DIR%\START_HERE.txt"
echo 2. Select "Run as Administrator" >> "%DIST_DIR%\START_HERE.txt"
echo 3. Follow the installation wizard >> "%DIST_DIR%\START_HERE.txt"
echo 4. Launch from Desktop shortcut >> "%DIST_DIR%\START_HERE.txt"
echo. >> "%DIST_DIR%\START_HERE.txt"
echo For detailed instructions, see: >> "%DIST_DIR%\START_HERE.txt"
echo - INSTALLATION_GUIDE.md >> "%DIST_DIR%\START_HERE.txt"
echo - README.md >> "%DIST_DIR%\START_HERE.txt"
echo. >> "%DIST_DIR%\START_HERE.txt"
echo ============================================ >> "%DIST_DIR%\START_HERE.txt"
echo Developer: Fr. Bastin - Trichy >> "%DIST_DIR%\START_HERE.txt"
echo Email: anito123789@gmail.com >> "%DIST_DIR%\START_HERE.txt"
echo ============================================ >> "%DIST_DIR%\START_HERE.txt"

echo [4/4] Creating ZIP package...

REM Create ZIP using PowerShell
powershell -Command "Compress-Archive -Path '%DIST_DIR%\*' -DestinationPath '%DESKTOP%\%PACKAGE_NAME%.zip' -Force"

if %errorLevel% equ 0 (
    echo.
    echo ============================================
    echo   Package Created Successfully!
    echo ============================================
    echo.
    echo Package Location:
    echo %DESKTOP%\%PACKAGE_NAME%.zip
    echo.
    echo Package Size:
    for %%A in ("%DESKTOP%\%PACKAGE_NAME%.zip") do echo %%~zA bytes
    echo.
    echo This package is ready for distribution!
    echo.
    echo ============================================
    echo   Next Steps:
    echo ============================================
    echo 1. Test the package on another PC
    echo 2. Share with other parishes
    echo 3. Upload to distribution platform
    echo.
    echo ============================================
) else (
    echo [ERROR] Failed to create ZIP package
    pause
    exit /b 1
)

REM Cleanup
rmdir /s /q "%DIST_DIR%"
del "%TEMP%\exclude.txt" >nul 2>&1

echo Would you like to open the package location?
choice /C YN /N /M "(Y/N): "
if errorlevel 2 goto :end

explorer /select,"%DESKTOP%\%PACKAGE_NAME%.zip"

:end
echo.
echo Thank you for using Parish Management System!
echo.
pause
