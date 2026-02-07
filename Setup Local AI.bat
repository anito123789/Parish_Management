@echo off
echo ========================================
echo   Ollama Local AI - Quick Setup
echo ========================================
echo.

REM Check if Ollama is already installed
where ollama >nul 2>nul
if %ERRORLEVEL% EQU 0 (
    echo [OK] Ollama is already installed!
    echo.
    goto :check_running
) else (
    echo [!] Ollama is not installed.
    echo.
    echo Please download and install Ollama from:
    echo https://ollama.ai/download
    echo.
    echo After installation, run this script again.
    pause
    exit /b
)

:check_running
echo Checking if Ollama is running...
curl -s http://localhost:11434 >nul 2>nul
if %ERRORLEVEL% EQU 0 (
    echo [OK] Ollama is running!
    echo.
) else (
    echo [!] Ollama is not running.
    echo Starting Ollama...
    start "" ollama serve
    timeout /t 3 >nul
    echo.
)

:check_models
echo Checking installed models...
ollama list
echo.

REM Check if llama2 is installed
ollama list | findstr /C:"llama2" >nul
if %ERRORLEVEL% EQU 0 (
    echo [OK] Llama2 model is installed!
    echo.
    goto :done
) else (
    echo [!] Llama2 model not found.
    echo.
    echo Would you like to download Llama2 now? (Recommended)
    echo This will download approximately 3.8GB
    echo.
    set /p download="Download Llama2? (Y/N): "
    
    if /i "%download%"=="Y" (
        echo.
        echo Downloading Llama2... This may take several minutes.
        echo Please wait...
        ollama pull llama2
        echo.
        echo [OK] Llama2 downloaded successfully!
    ) else (
        echo.
        echo Skipping download. You can download later with:
        echo   ollama pull llama2
    )
)

:done
echo.
echo ========================================
echo   Setup Complete!
echo ========================================
echo.
echo You can now use Local AI in your Parish Management System!
echo.
echo Access it via:
echo   - Navigation Menu: Click "💻 Local AI"
echo   - Direct URL: http://localhost:8000/ai_assistant_local.php
echo.
echo Available commands:
echo   ollama list          - List installed models
echo   ollama pull [model]  - Download a new model
echo   ollama rm [model]    - Remove a model
echo   ollama serve         - Start Ollama server
echo.
echo For more models, visit: https://ollama.ai/library
echo.
pause
