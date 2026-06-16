# Standalone Deployment Guide for Project Gawad

This guide will help you deploy the Project Gawad application as a standalone, self-contained program that can run on any Windows computer without requiring .NET SDK installation.

## Prerequisites

### On the Development Machine (Current Computer)
- .NET 8.0 SDK installed
- Visual Studio or VS Code (optional, for building)
- PowerShell 5.1 or later

### On the Target Computer (Where you'll deploy)
- Windows 10/11 or Windows Server 2016+
- MongoDB Community Server (required for database)
- No .NET SDK required (application will be self-contained)

---

## Step 1: Publish the Application as Self-Contained

### Option A: Using PowerShell Script (Recommended)

Run the provided deployment script:

```powershell
cd "C:\Users\Jerome de Guzman\Documents\MSIT\Subjects\IT Leadership\project-gawad-main\project-gawad-main\project-gawad-main"
.\publish-standalone.ps1
```

This will create a `publish` folder with all necessary files.

### Option B: Manual Publishing

1. Open PowerShell in the project root directory
2. Navigate to the Client project:
   ```powershell
   cd src\Project.Gawad.Client
   ```

3. Publish as self-contained for Windows x64:
   ```powershell
   dotnet publish -c Release -r win-x64 --self-contained true -p:PublishSingleFile=false -p:IncludeNativeLibrariesForSelfExtract=true -o ..\..\publish
   ```

4. This creates a `publish` folder in the project root with all files needed.

---

## Step 2: Prepare Deployment Package

The `publish` folder contains:
- `Project.Gawad.Client.exe` - Main executable
- All `.dll` files (dependencies)
- `wwwroot\` - Static files (CSS, JS, images)
- `appsettings.json` - Configuration file
- Native libraries (libwkhtmltox.dll for PDF generation)

### Additional Files to Include:

1. **MongoDB Configuration** - Ensure MongoDB is installed on target machine
2. **appsettings.json** - Update connection strings if needed
3. **Required Directories** - Create these folders in the publish directory:
   - `uploads\signatures\` - For signature images
   - `templates\` - For Word document templates (if using document generation)

---

## Step 3: Install MongoDB on Target Computer

### Download and Install MongoDB Community Server

1. Download from: https://www.mongodb.com/try/download/community
2. Install MongoDB Community Server
3. During installation, choose:
   - Install as Windows Service
   - Install MongoDB Compass (optional, for database management)

### Verify MongoDB Installation

1. Open PowerShell as Administrator
2. Check if MongoDB service is running:
   ```powershell
   Get-Service MongoDB
   ```

3. If not running, start it:
   ```powershell
   Start-Service MongoDB
   ```

4. Test connection:
   ```powershell
   mongosh mongodb://127.0.0.1:27017
   ```

---

## Step 4: Copy Files to Target Computer

### Method 1: USB Drive / Network Share

1. Copy the entire `publish` folder to the target computer
2. Place it in a permanent location (e.g., `C:\Program Files\ProjectGawad` or `C:\ProjectGawad`)

### Method 2: ZIP Archive

1. Compress the `publish` folder into a ZIP file
2. Transfer to target computer
3. Extract to desired location

---

## Step 5: Configure on Target Computer

### 1. Update appsettings.json

Edit `appsettings.json` in the publish folder:

```json
{
  "MongoDB": {
    "ConnectionString": "mongodb://127.0.0.1:27017",
    "GawadDatabaseName": "gawad_db",
    "IdentityDatabaseName": "gawad_identity"
  },
  "Kestrel": {
    "Endpoints": {
      "Http": {
        "Url": "http://localhost:5002"
      }
    }
  }
}
```

**Important Settings:**
- `ConnectionString`: Update if MongoDB is on a different machine or port
- `Url`: Change port if 5002 is already in use

### 2. Create Required Directories

Create these folders in the publish directory:
```powershell
mkdir uploads\signatures
mkdir templates
```

### 3. Copy Template Files (if using document generation)

If you have Word document templates, copy them to the `templates\` folder.

---

## Step 6: Run the Application

### Method 1: Double-Click Executable

Simply double-click `Project.Gawad.Client.exe` in the publish folder.

### Method 2: Create a Startup Script

Create a file `Start-Gawad.bat` in the publish folder:

```batch
@echo off
cd /d "%~dp0"
echo Starting Project Gawad...
start "Project Gawad" Project.Gawad.Client.exe
pause
```

Double-click `Start-Gawad.bat` to run the application.

### Method 3: Command Line

Open PowerShell in the publish folder:
```powershell
.\Project.Gawad.Client.exe
```

---

## Step 7: Access the Application

1. Open a web browser
2. Navigate to: `http://localhost:5002` (or the port you configured)
3. The application should load

**First Run:**
- The application will automatically seed initial data
- You may need to create an admin user (check SeedDataHostedService.cs for default credentials)

---

## Step 8: Install as Windows Service (Optional - For Production)

For production use, you may want to run it as a Windows Service.

### Using NSSM (Non-Sucking Service Manager)

1. Download NSSM: https://nssm.cc/download
2. Extract and run `nssm.exe install ProjectGawad`
3. Configure:
   - **Path**: `C:\ProjectGawad\Project.Gawad.Client.exe`
   - **Startup directory**: `C:\ProjectGawad`
   - **Service name**: `ProjectGawad`
4. Start the service:
   ```powershell
   nssm start ProjectGawad
   ```

---

## Troubleshooting

### Application Won't Start

1. **Check MongoDB is running:**
   ```powershell
   Get-Service MongoDB
   Start-Service MongoDB
   ```

2. **Check port availability:**
   ```powershell
   netstat -ano | findstr :5002
   ```
   If port is in use, change it in `appsettings.json`

3. **Check firewall:**
   - Allow the application through Windows Firewall
   - Or disable firewall temporarily to test

4. **Check logs:**
   - Look for error messages in the console window
   - Check Windows Event Viewer for application errors

### MongoDB Connection Issues

1. **Verify MongoDB service:**
   ```powershell
   Get-Service MongoDB
   ```

2. **Test connection:**
   ```powershell
   mongosh mongodb://127.0.0.1:27017
   ```

3. **Check connection string in appsettings.json:**
   - Ensure it matches your MongoDB installation
   - If MongoDB is on another machine, use: `mongodb://IP_ADDRESS:27017`

### PDF Generation Not Working

1. **Check if libwkhtmltox.dll exists:**
   - Should be in the publish folder root
   - If missing, rebuild with the publish script

2. **Check Visual C++ Redistributables:**
   - Install Microsoft Visual C++ Redistributable (latest version)
   - Download from: https://aka.ms/vs/17/release/vc_redist.x64.exe

### Port Already in Use

1. **Find what's using the port:**
   ```powershell
   netstat -ano | findstr :5002
   ```

2. **Kill the process or change port:**
   - Update `appsettings.json` with a different port
   - Or stop the process using the port

---

## Deployment Checklist

- [ ] Application published as self-contained
- [ ] MongoDB installed and running on target computer
- [ ] All files copied to target computer
- [ ] appsettings.json configured correctly
- [ ] Required directories created (uploads/signatures, templates)
- [ ] Port configured and available
- [ ] Firewall rules configured (if accessing from network)
- [ ] Application tested and running
- [ ] Windows Service installed (if needed for production)

---

## Network Access (Optional)

To access the application from other computers on the network:

1. **Update appsettings.json:**
   ```json
   "Kestrel": {
     "Endpoints": {
       "Http": {
         "Url": "http://0.0.0.0:5002"
       }
     }
   }
   ```

2. **Configure Windows Firewall:**
   - Allow port 5002 through firewall
   - Or allow the executable through firewall

3. **Access from other computers:**
   - Use: `http://TARGET_COMPUTER_IP:5002`
   - Example: `http://192.168.1.100:5002`

---

## Updating the Application

1. Publish new version on development machine
2. Stop the application on target computer
3. Backup the current `publish` folder
4. Copy new files (overwrite existing)
5. Keep `appsettings.json` with your custom settings
6. Restart the application

---

## Notes

- The application is self-contained and doesn't require .NET SDK on target machine
- MongoDB must be installed and running separately
- First run will seed initial data automatically
- Default port is 5002 (configurable in appsettings.json)
- All data is stored in MongoDB databases
- Logs appear in the console window when running

---

## Support

For issues or questions:
1. Check the application logs
2. Verify MongoDB connection
3. Check Windows Event Viewer
4. Review this deployment guide







