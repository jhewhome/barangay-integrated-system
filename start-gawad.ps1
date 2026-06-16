# Start Project Gawad BIS (Barangay Information System)
$ErrorActionPreference = "Stop"

$gawadClient = Join-Path $PSScriptRoot "gawad-bis\src\Project.Gawad.Client"
if (-not (Test-Path $gawadClient)) {
    Write-Host "Gawad project not found at: $gawadClient" -ForegroundColor Red
    exit 1
}

$mongo = Get-Service -Name "MongoDB" -ErrorAction SilentlyContinue
if ($mongo -and $mongo.Status -ne "Running") {
    Write-Host "Starting MongoDB service..." -ForegroundColor Yellow
    Start-Service MongoDB
}

Write-Host ""
Write-Host "Starting Gawad BIS..." -ForegroundColor Cyan
Write-Host "  URL: http://localhost:5003" -ForegroundColor Green
Write-Host "  Keep this window open while using the app." -ForegroundColor Yellow
Write-Host "  Press Ctrl+C to stop the server." -ForegroundColor Yellow
Write-Host ""

Set-Location $gawadClient
dotnet run --launch-profile http
