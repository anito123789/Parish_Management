@echo off
setlocal enabledelayedexpansion

:: Check for Portable PHP
set PHP_BIN=php
if exist "%~dp0php\php.exe" (
    set PHP_BIN="%~dp0php\php.exe"
)

:: Detect Local IP Address
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr "IPv4 Address"') do (
    set ip=%%a
    set ip=!ip: ^=!
)

:: Start the PHP server on all interfaces (0.0.0.0) - Hidden
if exist "%~dp0php\php.ini" (
    start /min "Parish Management Server" !PHP_BIN! -c "%~dp0php\php.ini" -S 0.0.0.0:8000
) else (
    start /min "Parish Management Server" !PHP_BIN! -S 0.0.0.0:8000
)

:: Wait a moment for the server to start
timeout /t 2 /nobreak > nul

:: Open the application in the default browser
start http://localhost:8000

:: Hide this window
exit
