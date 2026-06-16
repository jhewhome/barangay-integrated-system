# Quick Deployment Guide - Project Gawad

## Fast Track Deployment (5 Steps)

### 1. Run the Deployment Script
```powershell
.\deploy.ps1
```

This will create a `publish\Project.Gawad` folder with everything needed.

### 2. Copy to Target Desktop
Copy the entire `publish\Project.Gawad` folder to the target computer (e.g., `C:\ProjectGawad`)

### 3. Install MongoDB on Target Desktop
- Download: https://www.mongodb.com/try/download/community
- Install and ensure the service is running
- Default port: 27017

### 4. (Optional) Install .NET 8.0 Runtime
Only needed if you used framework-dependent deployment:
- Download: https://dotnet.microsoft.com/download/dotnet/8.0
- Install ASP.NET Core Runtime 8.0

### 5. Run the Application
Double-click `start-gawad.bat` or run:
```batch
cd C:\ProjectGawad
Project.Gawad.Client.exe
```

Access at: **http://localhost:5001**

---

## Deployment Options

### Self-Contained (Recommended)
Includes .NET runtime - larger size but no separate installation needed:
```powershell
.\deploy.ps1 -SelfContained
```

### Framework-Dependent
Requires .NET 8.0 Runtime on target machine - smaller size:
```powershell
.\deploy.ps1 -SelfContained:$false
```

### Custom Output Path
```powershell
.\deploy.ps1 -OutputPath ".\MyDeployment"
```

---

## What Gets Deployed?

✅ Application executable and DLLs  
✅ Configuration files (appsettings.json)  
✅ Views (Razor templates)  
✅ Static files (wwwroot folder)  
✅ Templates (Word documents)  
✅ Startup script (start-gawad.bat)  

---

## Troubleshooting

**App won't start?**
- Check MongoDB is running: `net start MongoDB`
- Verify port 5001 is available
- Check Windows Firewall

**MongoDB connection error?**
- Ensure MongoDB service is running
- Verify connection string in appsettings.json
- Test: `mongosh mongodb://127.0.0.1:27017`

**Port in use?**
- Change port in appsettings.json or use environment variable:
  ```powershell
  $env:ASPNETCORE_URLS="http://localhost:8080"
  ```

---

For detailed instructions, see `DEPLOYMENT_GUIDE.md`


