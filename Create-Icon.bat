@echo off
echo Creating valid ICO file from PNG...

powershell -Command "Add-Type -AssemblyName System.Drawing; $img = [System.Drawing.Image]::FromFile('assets\app_logo.png'); $ico = [System.Drawing.Icon]::FromHandle($img.GetHicon()); $fs = [System.IO.FileStream]::new('assets\setup_icon.ico', [System.IO.FileMode]::Create); $ico.Save($fs); $fs.Close(); $img.Dispose()"

if exist "assets\setup_icon.ico" (
    echo ICO file created successfully: assets\setup_icon.ico
) else (
    echo Failed to create ICO file
)

pause