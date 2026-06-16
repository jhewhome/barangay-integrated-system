# Barangay Integrated System

**Gawad BIS** (Barangay Information System) + **BHC** (Barangay Health Center System)  
Barangay Balong Bato, San Juan City

Unified GitHub repository for barangay resident records, health center patient registry, multi-station queues, and clinical workflow — with integration between both applications.

**Local path:** `C:\PUP\barangay-integrated-system-repo`

---

## Repository structure

```
barangay-integrated-system-repo/
├── README.md                 ← this file
├── .gitignore
├── start-gawad.ps1           ← start Gawad BIS (local dev)
├── bhc-system/               ← BHC (PHP 8+ / MySQL)
│   ├── public/               ← web entry point
│   ├── config/
│   ├── docs/                 ← all user guides & technical docs
│   └── database_schema.sql
└── gawad-bis/                ← Gawad BIS (ASP.NET Core 8 / MongoDB)
    ├── Project.Gawad.sln
    └── src/
        └── Project.Gawad.Client/
```

| Folder | Stack | Purpose |
|--------|-------|---------|
| **bhc-system** | PHP, MySQL | Patient registry, queues, triage, consultation, pharmacy, doctor portal |
| **gawad-bis** | .NET 8, MongoDB | Residents, permits, medicine inventory, BHC integration APIs |

---

## Documentation

All guides live in **`bhc-system/docs/`**:

| Document | Description |
|----------|-------------|
| [bhc-system/docs/README.md](bhc-system/docs/README.md) | Documentation index |
| [bhc-system/docs/USER_GUIDE.md](bhc-system/docs/USER_GUIDE.md) | BHC operations (queues, clinical workflow, doctor portal) |
| [bhc-system/docs/INTEGRATED_USER_GUIDE.md](bhc-system/docs/INTEGRATED_USER_GUIDE.md) | Gawad ↔ BHC integration (SSO, resident handoff, medicine sync) |
| [bhc-system/docs/SOURCE_CODE_AND_DOCUMENTATION.md](bhc-system/docs/SOURCE_CODE_AND_DOCUMENTATION.md) | Architecture, APIs, setup (technical) |

---

## Prerequisites

| Tool | Used by |
|------|---------|
| [XAMPP](https://www.apachefriends.org/) (Apache + MySQL + PHP 8.1+) | BHC |
| [.NET 8 SDK](https://dotnet.microsoft.com/download) | Gawad BIS |
| [MongoDB](https://www.mongodb.com/try/download/community) | Gawad BIS |
| Git | This repository |

---

## First-time setup

### 1. Clone the repository

```powershell
git clone https://github.com/YOUR_USERNAME/barangay-integrated-system.git
cd barangay-integrated-system-repo
```

### 2. BHC (Health Center)

**Option A — use existing XAMPP copy (if you develop in `htdocs`):**

Keep working in `c:\PUP\htdocs\bhc_system` and treat `bhc-system/` here as the Git source of truth. Sync changes before commit.

**Option B — run from this repo:**

1. Copy or junction `bhc-system` into XAMPP `htdocs` as `bhc_system`, **or** configure Apache to serve `bhc-system/public`.
2. Create MySQL database `brgy_health_db` (optional: import `bhc-system/database_schema.sql`).
3. Edit `bhc-system/config/database.php` if needed (default XAMPP: `root` / empty password).
4. Copy integration config:
   ```powershell
   cd C:\PUP\barangay-integrated-system-repo
   copy bhc-system\config\gawad_integration.local.example.php bhc-system\config\gawad_integration.local.php
   ```
5. Open: `http://localhost/bhc_system/public/login`

Default admin (first install): **admin** / **admin123** — change before production.

### 3. Gawad BIS

1. Ensure **MongoDB** service is running.
2. From this repo root:
   ```powershell
   cd C:\PUP\barangay-integrated-system-repo
   .\start-gawad.ps1
   ```
   Or manually:
   ```powershell
   cd gawad-bis\src\Project.Gawad.Client
   dotnet run --launch-profile http
   ```
3. Open: [http://localhost:5003](http://localhost:5003)

### 4. Enable integration (both sides)

**Gawad** — `gawad-bis/src/Project.Gawad.Client/appsettings.json`:

```json
"BhcIntegration": {
  "Enabled": true,
  "BaseUrl": "http://localhost/bhc_system/public",
  "OpenInNewTab": true,
  "ResidentSyncEnabled": true,
  "MedicineSyncEnabled": true,
  "IntegrationApiKey": "your-integration-key",
  "SsoEnabled": true,
  "SsoSecret": "your-sso-secret",
  "SsoTokenLifetimeSeconds": 300
}
```

**BHC** — `bhc-system/config/gawad_integration.local.php`:

```php
return [
    'enabled' => true,
    'api_base_url' => 'http://localhost:5003',
    'integration_api_key' => 'your-integration-key',
    'sso_enabled' => true,
    'sso_secret' => 'your-sso-secret',
    'medicine_sync_enabled' => true,
];
```

Use the **same** integration key and SSO secret on both sides. Local overrides are listed in `.gitignore` and must not be committed.

### 5. Verify integration

1. Sign in to Gawad BIS.
2. Open a resident profile → **Register at Health Center**.
3. BHC should open (SSO) with the patient form prefilled.

---

## Running locally (daily)

| Step | Action |
|------|--------|
| 1 | Start XAMPP (Apache + MySQL) |
| 2 | `cd C:\PUP\barangay-integrated-system-repo` → `.\start-gawad.ps1` (keep window open) |
| 3 | Gawad: [http://localhost:5003](http://localhost:5003) |
| 4 | BHC: `http://localhost/bhc_system/public` |

---

## Push to GitHub

From this folder:

```powershell
cd C:\PUP\barangay-integrated-system-repo
git init
git add .
git status
git commit -m "Initial commit: BHC + Gawad BIS integrated system"
git remote add origin https://github.com/YOUR_USERNAME/barangay-integrated-system.git
git branch -M main
git push -u origin main
```

Before the first commit, confirm `git status` does **not** list `gawad_integration.local.php` or Gawad `bin/`/`obj/` folders (see `.gitignore`).

---

## Integration features

| Feature | Description |
|---------|-------------|
| **Health Center menu** | Gawad sidebar links to BHC queues and patients |
| **SSO** | Sign in to BHC automatically from Gawad |
| **Register at Health Center** | Prefill BHC patient form from Gawad resident |
| **Staff import** | BHC admin imports Gawad users for matching logins |
| **Medicine sync** | BHC prescription picker reads Gawad inventory (read-only) |

Details: [bhc-system/docs/INTEGRATED_USER_GUIDE.md](bhc-system/docs/INTEGRATED_USER_GUIDE.md)

---

## What not to commit

See [`.gitignore`](.gitignore). In particular:

- `bhc-system/config/gawad_integration.local.php` (API keys)
- `bhc-system/config/database.infinityfree.php` (production DB password)
- Gawad `bin/`, `obj/`, `.vs/`
- Build and deploy output folders

Commit **example** config files only (`gawad_integration.local.example.php`, base `appsettings.json` with dev placeholders).

---

## Databases — what goes on GitHub?

You do **not** upload the live databases themselves. MySQL and MongoDB store data on your PC/server, not inside the Git repo.

| Upload to GitHub? | Item | Why |
|-------------------|------|-----|
| **Yes** | `bhc-system/database_schema.sql` | Empty table structure + default stations — reviewers need this to set up BHC |
| **Yes** | `bhc-system/config/database.php` | Connection settings (host, db name) — no patient data |
| **Yes** | Gawad `appsettings.json` | MongoDB URL template (`mongodb://127.0.0.1:27017`) — no resident records |
| **Yes** | `bhc-system/scripts/seed_*.php` | Optional dev scripts to generate sample data locally |
| **No** | MySQL data dump (`mysqldump` with patients, queues, users) | Privacy + size; contains real or test PHI |
| **No** | MongoDB dump (`mongodump`, `.bson` files) | Same — residents, medicines, user accounts |
| **No** | XAMPP `mysql/data/` folder | Raw database files on disk |
| **No** | MongoDB `data/` directory | Raw database files on disk |

### BHC (MySQL — `brgy_health_db`)

1. Clone the repo.
2. Create database: `brgy_health_db`
3. Import schema only:
   ```powershell
   mysql -u root brgy_health_db < bhc-system\database_schema.sql
   ```
4. Start the app — `config/database.php` also runs `ensureSchema()` for any missing columns.
5. First login: **admin** / **admin123** (auto-created if no users exist).

Optional sample data (local dev only):
```powershell
cd bhc-system
php scripts/seed_dummy_patients.php
php scripts/seed_clinical_data.php
```

### Gawad BIS (MongoDB — `gawad_db`)

1. Start MongoDB.
2. Run Gawad — it creates collections on first use.
3. `SeedDataHostedService` seeds initial roles/admin on first run (see Gawad deploy docs in `gawad-bis/`).

Each developer or reviewer creates their **own** local database from schema + seeds — they do not download your data from GitHub.

### For thesis / demo submission

- Document setup steps (already in this README and `bhc-system/docs/`).
- Optionally include **anonymized** sample export in a separate zip **outside** GitHub if required — never commit real barangay resident or patient names from production.

---

## License / academic use

Developed for Barangay Balong Bato, San Juan City. Refer to your course or barangay authority for distribution terms.
