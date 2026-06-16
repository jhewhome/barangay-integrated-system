# Project Gawad - Desktop Deployment Guide

This guide will help you deploy the Project Gawad application to a desktop computer.

## Prerequisites

### 1. .NET 8.0 Runtime (if using framework-dependent deployment)
- Download from: https://dotnet.microsoft.com/download/dotnet/8.0
- Install the **ASP.NET Core Runtime 8.0** (not just the runtime)
- Choose the appropriate version (x64 for most modern desktops)

### 2. MongoDB Community Server
- Download from: https://www.mongodb.com/try/download/community
- Install MongoDB Community Server
- Ensure MongoDB service is running (it should start automatically after installation)
- Default port: 27017

## Deployment Steps

### Step 1: Publish the Application

#### Option A: Self-Contained Deployment (Recommended for Desktop)
This includes the .NET runtime, so no separate .NET installation is needed.

```powershell
cd src
dotnet publish Project.Gawad.Client/Project.Gawad.Client.csproj -c Release -r win-x64 --self-contained true -o ..\publish\Project.Gawad
```

#### Option B: Framework-Dependent Deployment
Requires .NET 8.0 Runtime to be installed on the target machine.

```powershell
cd src
dotnet publish Project.Gawad.Client/Project.Gawad.Client.csproj -c Release -o ..\publish\Project.Gawad
```

### Step 2: Copy Files to Target Desktop

1. Copy the entire `publish\Project.Gawad` folder to the target desktop computer
2. Recommended location: `C:\Program Files\ProjectGawad` or `C:\ProjectGawad`

### Step 3: Verify Required Files

Ensure these files/folders are present in the deployment:
- `Project.Gawad.Client.exe` (or `dotnet.exe` if framework-dependent)
- `appsettings.json`
- `wwwroot\` folder (contains static files, templates, and uploads)
  - `wwwroot\templates\` - Word document templates (barangayclearance.docx, businesspermit.docx, etc.)
  - `wwwroot\uploads\` - User uploaded files (signatures, etc.)
- `Views\` folder (contains Razor views)
- All `.dll` files from the publish output

### Step 4: Configure MongoDB Connection

1. Edit `appsettings.json` on the target machine
2. Verify MongoDB connection string:
   ```json
   "MongoDB": {
     "ConnectionString": "mongodb://127.0.0.1:27017",
     "GawadDatabaseName": "gawad_db",
     "IdentityDatabaseName": "gawad_identity"
   }
   ```
3. If MongoDB is on a different machine, update the connection string accordingly

### Step 5: Create Required Directories

The application may need these directories (create if they don't exist):
- `uploads\signatures\` - for signature images
- `templates\` - for Word document templates

### Step 6: Run the Application

#### Method 1: Command Line
```powershell
cd "C:\ProjectGawad"
.\Project.Gawad.Client.exe
```

#### Method 2: Create a Startup Script
Create a file `start-gawad.bat`:
```batch
@echo off
cd /d "%~dp0"
start "Project Gawad" Project.Gawad.Client.exe
```

#### Method 3: Windows Service (Advanced)
For production use, consider installing as a Windows Service using tools like:
- NSSM (Non-Sucking Service Manager)
- Windows Service Wrapper

### Step 7: Access the Application

- Open a web browser
- Navigate to: `http://localhost:5001`
- Or check `appsettings.json` for the configured port

## Configuration Options

### Change the Port

Edit `appsettings.json` or use environment variables:
```json
"Kestrel": {
  "Endpoints": {
    "Http": {
      "Url": "http://localhost:8080"
    }
  }
}
```

Or set environment variable:
```powershell
$env:ASPNETCORE_URLS="http://localhost:8080"
```

### Production Environment

Set the environment to Production:
```powershell
$env:ASPNETCORE_ENVIRONMENT="Production"
```

Or create `appsettings.Production.json` with production-specific settings.

## Troubleshooting

### Application Won't Start
1. Check if MongoDB is running: `net start MongoDB` or check Services
2. Verify .NET runtime is installed (if using framework-dependent)
3. Check firewall settings for the port
4. Review application logs

### MongoDB Connection Issues
1. Verify MongoDB service is running
2. Check MongoDB connection string in `appsettings.json`
3. Test connection: `mongosh mongodb://127.0.0.1:27017`

### Port Already in Use
1. Change the port in configuration
2. Or stop the application using the port: `netstat -ano | findstr :5001`

## Quick Deployment Script

See `deploy.ps1` for an automated deployment script.

## Notes

- The application uses MongoDB for data storage
- First run will seed initial data (via `SeedDataHostedService`)
- Default port is 5001 (HTTP) or 7140 (HTTPS)
- Ensure Windows Firewall allows the application port if accessing from other machines

