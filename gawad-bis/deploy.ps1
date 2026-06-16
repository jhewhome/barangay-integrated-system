# Project Gawad Deployment Script
# This script publishes and prepares the application for deployment

param(
    [string]$OutputPath = ".\publish\Project.Gawad",
    [string]$Runtime = "win-x64",
    [switch]$SelfContained = $true,
    [string]$Configuration = "Release"
)

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Project Gawad Deployment Script" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if .NET SDK is installed
Write-Host "Checking .NET SDK..." -ForegroundColor Yellow
$dotnetVersion = dotnet --version
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: .NET SDK is not installed or not in PATH" -ForegroundColor Red
    Write-Host "Please install .NET 8.0 SDK from https://dotnet.microsoft.com/download" -ForegroundColor Red
    exit 1
}
Write-Host "✓ .NET SDK $dotnetVersion found" -ForegroundColor Green
Write-Host ""

# Navigate to src directory
$srcPath = Join-Path $PSScriptRoot "src"
if (-not (Test-Path $srcPath)) {
    Write-Host "ERROR: src directory not found" -ForegroundColor Red
    exit 1
}

Push-Location $srcPath

try {
    # Restore dependencies
    Write-Host "Restoring NuGet packages..." -ForegroundColor Yellow
    dotnet restore Project.Gawad.sln
    if ($LASTEXITCODE -ne 0) {
        Write-Host "ERROR: Failed to restore packages" -ForegroundColor Red
        exit 1
    }
    Write-Host "✓ Packages restored" -ForegroundColor Green
    Write-Host ""

    # Build solution
    Write-Host "Building solution..." -ForegroundColor Yellow
    dotnet build Project.Gawad.sln -c $Configuration
    if ($LASTEXITCODE -ne 0) {
        Write-Host "ERROR: Build failed" -ForegroundColor Red
        exit 1
    }
    Write-Host "✓ Build successful" -ForegroundColor Green
    Write-Host ""

    # Publish application
    Write-Host "Publishing application..." -ForegroundColor Yellow
    $publishArgs = @(
        "publish",
        "Project.Gawad.Client\Project.Gawad.Client.csproj",
        "-c", $Configuration,
        "-o", (Join-Path $PSScriptRoot $OutputPath)
    )

    if ($SelfContained) {
        $publishArgs += "-r", $Runtime
        $publishArgs += "--self-contained", "true"
        Write-Host "  Mode: Self-contained ($Runtime)" -ForegroundColor Gray
    } else {
        Write-Host "  Mode: Framework-dependent" -ForegroundColor Gray
    }

    dotnet $publishArgs
    if ($LASTEXITCODE -ne 0) {
        Write-Host "ERROR: Publish failed" -ForegroundColor Red
        exit 1
    }
    Write-Host "✓ Application published" -ForegroundColor Green
    Write-Host ""

    # Create startup script
    $startupScript = Join-Path $PSScriptRoot $OutputPath "start-gawad.bat"
    Write-Host "Creating startup script..." -ForegroundColor Yellow
    $startupContent = @"
@echo off
echo Starting Project Gawad...
cd /d "%~dp0"
start "Project Gawad" Project.Gawad.Client.exe
timeout /t 3 /nobreak >nul
echo Application started. Check http://localhost:5001
pause
"@
    Set-Content -Path $startupScript -Value $startupContent
    Write-Host "✓ Startup script created: start-gawad.bat" -ForegroundColor Green
    Write-Host ""

    # Create README for deployment
    $readmePath = Join-Path $PSScriptRoot $OutputPath "DEPLOYMENT_README.txt"
    Write-Host "Creating deployment README..." -ForegroundColor Yellow
    $readmeContent = @"
Project Gawad - Deployment Package
==================================

INSTALLATION INSTRUCTIONS:
--------------------------
1. Ensure MongoDB Community Server is installed and running
   Download from: https://www.mongodb.com/try/download/community

2. If using framework-dependent deployment, install .NET 8.0 Runtime:
   Download from: https://dotnet.microsoft.com/download/dotnet/8.0

3. Copy this entire folder to the target desktop computer
   Recommended location: C:\ProjectGawad

4. Edit appsettings.json if needed (especially MongoDB connection)

5. Run start-gawad.bat to start the application

6. Open browser and navigate to: http://localhost:5001


REQUIREMENTS:
------------
- Windows 10/11 or Windows Server 2016+
- MongoDB Community Server (running on port 27017)
- .NET 8.0 Runtime (if using framework-dependent deployment)
- Port 5001 available (or configure different port)


TROUBLESHOOTING:
---------------
- If application won't start, check if MongoDB is running
- Verify MongoDB connection in appsettings.json
- Check Windows Firewall settings
- Review application logs


For more details, see DEPLOYMENT_GUIDE.md in the project root.
"@
    Set-Content -Path $readmePath -Value $readmeContent
    Write-Host "✓ Deployment README created" -ForegroundColor Green
    Write-Host ""

    # Display summary
    $publishPath = Join-Path $PSScriptRoot $OutputPath
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host "Deployment Complete!" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Published to: $publishPath" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Cyan
    Write-Host "1. Copy the '$OutputPath' folder to the target desktop" -ForegroundColor White
    Write-Host "2. Ensure MongoDB is installed and running on the target machine" -ForegroundColor White
    if (-not $SelfContained) {
        Write-Host "3. Install .NET 8.0 Runtime on the target machine" -ForegroundColor White
    }
    Write-Host "4. Run start-gawad.bat on the target machine" -ForegroundColor White
    Write-Host "5. Access the application at http://localhost:5001" -ForegroundColor White
    Write-Host ""

} finally {
    Pop-Location
}


