# PowerShell Script to Convert PNG to ICO
# This script converts the parish_icon.png to parish_icon.ico

$sourcePath = "C:\Users\Bastin\.gemini\antigravity\brain\d4b2556a-578a-462a-9fb9-4e6f65f7772e\parish_icon_1769759020636.png"
$destPath = "c:\Users\Bastin\Desktop\Parish M\assets\parish_icon.ico"

# Check if source exists
if (-not (Test-Path $sourcePath)) {
    Write-Host "Error: Source PNG file not found at $sourcePath" -ForegroundColor Red
    exit 1
}

# Create assets directory if it doesn't exist
$assetsDir = "c:\Users\Bastin\Desktop\Parish M\assets"
if (-not (Test-Path $assetsDir)) {
    New-Item -ItemType Directory -Path $assetsDir -Force | Out-Null
}

Write-Host "Converting PNG to ICO format..." -ForegroundColor Cyan

# Load the image
Add-Type -AssemblyName System.Drawing

try {
    $img = [System.Drawing.Image]::FromFile($sourcePath)
    
    # Create icon sizes (16x16, 32x32, 48x48, 256x256)
    $sizes = @(16, 32, 48, 256)
    
    # For simplicity, we'll copy the PNG to the assets folder
    # and also create a simple ICO file
    
    # Copy PNG to assets
    $pngDest = "c:\Users\Bastin\Desktop\Parish M\assets\parish_icon.png"
    Copy-Item -Path $sourcePath -Destination $pngDest -Force
    
    Write-Host "Icon copied to: $pngDest" -ForegroundColor Green
    
    # Note: Creating a proper multi-resolution ICO file requires additional libraries
    # For now, we'll use the PNG file and create a basic ICO
    # Windows will use the PNG for display purposes
    
    # Create a basic ICO file using the image
    $icon = [System.Drawing.Icon]::FromHandle($img.GetHicon())
    $fileStream = [System.IO.FileStream]::new($destPath, [System.IO.FileMode]::Create)
    $icon.Save($fileStream)
    $fileStream.Close()
    
    Write-Host "ICO file created: $destPath" -ForegroundColor Green
    
    $img.Dispose()
    $icon.Dispose()
    
    Write-Host "`nIcon conversion completed successfully!" -ForegroundColor Green
    
}
catch {
    Write-Host "Error during conversion: $_" -ForegroundColor Red
    
    # Fallback: Just copy the PNG
    $pngDest = "c:\Users\Bastin\Desktop\Parish M\assets\parish_icon.png"
    Copy-Item -Path $sourcePath -Destination $pngDest -Force
    Write-Host "PNG icon copied to: $pngDest" -ForegroundColor Yellow
}

Write-Host "`nPress any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
