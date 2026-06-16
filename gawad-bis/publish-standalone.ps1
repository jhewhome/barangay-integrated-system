# Standalone Publishing Script for Project Gawad
# This script publishes the application as a self-contained deployment

param(
    [string]$OutputPath = "publish",
    [string]$Configuration = "Release",
    [string]$Runtime = "win-x64"
)

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Project Gawad - Standalone Publishing" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Get the script directory (project root - should be project-gawad-main\project-gawad-main\project-gawad-main)
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ClientProjectPath = Join-Path $ScriptDir "src\Project.Gawad.Client"
# Publish to nested publish\publish folder structure
$PublishPath = Join-Path $ScriptDir (Join-Path $OutputPath $OutputPath)

# Ensure we're publishing to the correct location (main project root)
if (-not $ScriptDir.EndsWith("project-gawad-main\project-gawad-main\project-gawad-main")) {
    Write-Host "Warning: Script may not be in the correct project root directory." -ForegroundColor Yellow
    Write-Host "Expected path ending: project-gawad-main\project-gawad-main\project-gawad-main" -ForegroundColor Yellow
    Write-Host "Current path: $ScriptDir" -ForegroundColor Yellow
}

Write-Host "Project Root: $ScriptDir" -ForegroundColor Yellow
Write-Host "Client Project: $ClientProjectPath" -ForegroundColor Yellow
Write-Host "Publish Output: $PublishPath" -ForegroundColor Yellow
Write-Host ""

# Check if .NET SDK is installed
Write-Host "Checking .NET SDK..." -ForegroundColor Yellow
try {
    $dotnetVersion = dotnet --version
    Write-Host "✓ .NET SDK Version: $dotnetVersion" -ForegroundColor Green
} catch {
    Write-Host "✗ .NET SDK not found. Please install .NET 8.0 SDK." -ForegroundColor Red
    exit 1
}

# Check if project exists
if (-not (Test-Path $ClientProjectPath)) {
    Write-Host "✗ Client project not found at: $ClientProjectPath" -ForegroundColor Red
    exit 1
}

# Clean previous publish
if (Test-Path $PublishPath) {
    Write-Host "Cleaning previous publish folder..." -ForegroundColor Yellow
    Remove-Item -Path $PublishPath -Recurse -Force
}

Write-Host ""
Write-Host "Publishing application..." -ForegroundColor Yellow
Write-Host "Configuration: $Configuration" -ForegroundColor Cyan
Write-Host "Runtime: $Runtime" -ForegroundColor Cyan
Write-Host "Output: $PublishPath" -ForegroundColor Cyan
Write-Host ""

# Change to client project directory
Push-Location $ClientProjectPath

try {
    # Publish as self-contained
    dotnet publish `
        -c $Configuration `
        -r $Runtime `
        --self-contained true `
        -p:PublishSingleFile=false `
        -p:IncludeNativeLibrariesForSelfExtract=true `
        -p:EnableCompressionInSingleFile=false `
        -o $PublishPath

    if ($LASTEXITCODE -ne 0) {
        Write-Host "✗ Publishing failed!" -ForegroundColor Red
        Pop-Location
        exit 1
    }

    Write-Host ""
    Write-Host "✓ Publishing completed successfully!" -ForegroundColor Green
    Write-Host ""

    # Create required directories
    Write-Host "Creating required directories..." -ForegroundColor Yellow
    $uploadsPath = Join-Path $PublishPath "uploads\signatures"
    $templatesPath = Join-Path $PublishPath "templates"
    
    New-Item -ItemType Directory -Force -Path $uploadsPath | Out-Null
    New-Item -ItemType Directory -Force -Path $templatesPath | Out-Null
    
    Write-Host "✓ Created: uploads\signatures\" -ForegroundColor Green
    Write-Host "✓ Created: templates\" -ForegroundColor Green
    Write-Host ""

    # Copy template files if they exist
    $sourceTemplates = Join-Path $ClientProjectPath "templates"
    if (Test-Path $sourceTemplates) {
        Write-Host "Copying template files..." -ForegroundColor Yellow
        Copy-Item -Path "$sourceTemplates\*" -Destination $templatesPath -Recurse -Force
        Write-Host "✓ Templates copied" -ForegroundColor Green
        Write-Host ""
    }

    # Verify critical files
    Write-Host "Verifying critical files..." -ForegroundColor Yellow
    $exePath = Join-Path $PublishPath "Project.Gawad.Client.exe"
    $dllPath = Join-Path $PublishPath "libwkhtmltox.dll"
    $appsettingsPath = Join-Path $PublishPath "appsettings.json"
    
    $allGood = $true
    
    if (Test-Path $exePath) {
        Write-Host "✓ Project.Gawad.Client.exe" -ForegroundColor Green
    } else {
        Write-Host "✗ Project.Gawad.Client.exe NOT FOUND" -ForegroundColor Red
        $allGood = $false
    }
    
    if (Test-Path $dllPath) {
        Write-Host "✓ libwkhtmltox.dll (PDF library)" -ForegroundColor Green
    } else {
        Write-Host "⚠ libwkhtmltox.dll NOT FOUND (PDF generation may not work)" -ForegroundColor Yellow
    }
    
    if (Test-Path $appsettingsPath) {
        Write-Host "✓ appsettings.json" -ForegroundColor Green
    } else {
        Write-Host "✗ appsettings.json NOT FOUND" -ForegroundColor Red
        $allGood = $false
    }
    
    Write-Host ""

    if (-not $allGood) {
        Write-Host "⚠ Some critical files are missing. Please check the publish output." -ForegroundColor Yellow
    }

    # Create startup script
    Write-Host "Creating startup script..." -ForegroundColor Yellow
    $startScriptContent = @"
@echo off
cd /d "%~dp0"
echo ========================================
echo Project Gawad - Starting Application
echo ========================================
echo.
echo Make sure MongoDB is running before starting!
echo.
pause
start "Project Gawad" Project.Gawad.Client.exe
"@
    
    $startScriptPath = Join-Path $PublishPath "Start-Gawad.bat"
    Set-Content -Path $startScriptPath -Value $startScriptContent
    Write-Host "✓ Created: Start-Gawad.bat" -ForegroundColor Green
    Write-Host ""

    # Display summary
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host "Publishing Summary" -ForegroundColor Cyan
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Output Location: $PublishPath" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Next Steps:" -ForegroundColor Cyan
    Write-Host "1. Install MongoDB on the target computer" -ForegroundColor White
    Write-Host "2. Copy the entire 'publish' folder to the target computer" -ForegroundColor White
    Write-Host "3. Update appsettings.json with correct MongoDB connection" -ForegroundColor White
    Write-Host "4. Run Project.Gawad.Client.exe or Start-Gawad.bat" -ForegroundColor White
    Write-Host ""
    Write-Host "For detailed instructions, see: DEPLOY_STANDALONE.md" -ForegroundColor Cyan
    Write-Host ""

} finally {
    Pop-Location
}

Write-Host "Done!" -ForegroundColor Green

