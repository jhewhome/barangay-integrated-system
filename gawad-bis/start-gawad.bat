@echo off
REM Project Gawad Startup Script
REM This script starts the Project Gawad application

echo ========================================
echo Project Gawad - Starting Application
echo ========================================
echo.

REM Change to script directory
cd /d "%~dp0"

REM Check if MongoDB is running (optional check)
echo Checking MongoDB connection...
mongosh --eval "db.version()" mongodb://127.0.0.1:27017 >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo WARNING: Could not connect to MongoDB.
    echo Please ensure MongoDB is installed and running.
    echo.
    pause
)

REM Start the application
echo Starting Project Gawad application...
echo.
start "Project Gawad" Project.Gawad.Client.exe

REM Wait a moment for the app to start
timeout /t 3 /nobreak >nul

echo.
echo ========================================
echo Application started!
echo ========================================
echo.
echo The application should be available at:
echo   http://localhost:5001
echo.
echo Press any key to open in browser, or close this window.
pause >nul

REM Try to open browser
start http://localhost:5001


