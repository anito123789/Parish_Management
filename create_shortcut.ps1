$WshShell = New-Object -ComObject WScript.Shell
$ShortcutPath = "$([Environment]::GetFolderPath('Desktop'))\Parish M.lnk"
$Shortcut = $WshShell.CreateShortcut($ShortcutPath)
$Shortcut.TargetPath = "wscript.exe"
$Shortcut.Arguments = "`"$PSScriptRoot\run_hidden.vbs`""
$Shortcut.WorkingDirectory = "$PSScriptRoot"
$Shortcut.Description = "Launch Parish Management System"
# Using your premium app logo as the icon (ICO version)
$Shortcut.IconLocation = "$PSScriptRoot\assets\app_logo.ico" 
$Shortcut.Save()

Write-Host "Shortcut created on Desktop!" -ForegroundColor Green
