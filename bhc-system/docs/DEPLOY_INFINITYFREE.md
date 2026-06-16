# Deploy BHC System on InfinityFree

**Your site:** [https://bhcs.free.nf](https://bhcs.free.nf)

InfinityFree supports **PHP + MySQL**, which matches this project. Use this guide when moving from XAMPP to your free domain or `*.infinityfreeapp.com` site.

---

## Before you start

| Item | Notes |
|------|--------|
| InfinityFree account | [infinityfree.com](https://www.infinityfree.com) |
| FTP client | FileZilla (recommended) |
| **Deploy package** | Run `php scripts/build-infinityfree-deploy.php` → upload `deploy/infinityfree/package/` |
| Local XAMPP copy | Stays unchanged at project root (`public/`, etc.) — see `deploy/infinityfree/README.md` in the repository |
| Demo login | `admin` / `admin123` (change after go-live) |

**Important:** InfinityFree is on the **public internet**. It is good for demos and remote access. A **barangay health center** that needs LAN TVs, QR on local Wi-Fi, and no internet dependency should still run **XAMPP on a local PC** for daily operations. You can use both: local for clinic, InfinityFree for portfolio/demo.

---

## 1. Create hosting account and site

1. Sign up at InfinityFree and create a **hosting account**.
2. Add a **website** (subdomain like `yourname.infinityfreeapp.com` or connect a domain).
3. In the control panel (**vPanel**), note:
   - **FTP hostname**, username, password
   - **Website URL:** `https://bhcs.free.nf`
   - **PHP version** — set to **8.1** or **8.2** if available

---

## 2. Create MySQL database

1. In vPanel, open **MySQL Databases**.
2. Create a new database. Write down:
   - **Database name** (often like `if0_XXXXXX_bhc`)
   - **Username**
   - **Password**
   - **MySQL hostname** (often **not** `localhost` — e.g. `sqlXXX.infinityfree.com`)
3. Open **phpMyAdmin** for that database.
4. **Import** the file `database_schema.sql` from this project (creates tables + default stations).
5. Optional: the app will also run `ensureSchema()` on first page load if something is missing.

---

## 3. Build deploy folder (keeps local XAMPP separate)

From your PC project root (`bhc_system`):

```bash
php scripts/build-infinityfree-deploy.php
```

This creates **`deploy/infinityfree/package/`** — upload **only that folder's contents** to InfinityFree. Your local `public/` + root folders are not modified.

1. Copy `config/database.infinityfree.example.php` → `config/database.infinityfree.php` and set InfinityFree MySQL details.
2. Run the build script.
3. Zip `deploy/infinityfree/package/` or upload via File Manager into **`htdocs`**.

## 4. Upload files (everything inside `htdocs`)

InfinityFree only lets you upload into **`htdocs`**. Put **the built package** inside `htdocs` (not only `public/` from the local tree).

The deploy `index.php` detects `config/` beside it (flat layout).

### Final layout on the server (inside `htdocs`)

```text
htdocs/
  index.php          <- from public/index.php
  .htaccess          <- from public/.htaccess
  assets/            <- from public/assets/
  config/            <- includes .htaccess (blocks web access)
    app.php
    database.php
  core/
  controllers/
  models/
  views/
```

**Do not upload:** `scripts/`, `.git`, markdown guides, root-level PNG diagrams (optional).

### On your PC — prepare one folder to upload

**Option A — ZIP (easiest with Online File Manager)**

1. Create a temporary folder, e.g. `bhc_upload`.
2. Copy into it:
   - Everything from `public/` → root of `bhc_upload` (`index.php`, `.htaccess`, `assets/`)
   - Folders: `config/`, `core/`, `controllers/`, `models/`, `views/`
3. Edit `bhc_upload/config/database.php` with InfinityFree MySQL details.
4. Zip `bhc_upload` → `bhc_upload.zip`.
5. In vPanel **Online File Manager**, open **`htdocs`**, upload the ZIP, then **Extract**.

**Option B — FileZilla (into `htdocs` only)**

Upload each item **into** remote `htdocs/`:

| Local (on your PC) | Remote (InfinityFree) |
|--------------------|------------------------|
| `public/index.php` | `htdocs/index.php` |
| `public/.htaccess` | `htdocs/.htaccess` |
| `public/assets/` | `htdocs/assets/` |
| `config/` | `htdocs/config/` |
| `core/` | `htdocs/core/` |
| `controllers/` | `htdocs/controllers/` |
| `models/` | `htdocs/models/` |
| `views/` | `htdocs/views/` |

Folders `config`, `core`, `controllers`, `models`, and `views` include a **`.htaccess`** file so visitors cannot open them in a browser.

---

## 3b. FileZilla setup (if upload fails, read this)

### Get the correct credentials (most common mistake)

Use **FTP Details** from InfinityFree, **not** your InfinityFree login email/password.

1. [InfinityFree Client Area](https://dash.infinityfree.com) → **Hosting Accounts**
2. Click **Manage** next to the account linked to **bhcs.free.nf**
3. Copy from the **FTP Details** table:
   - **FTP hostname** (often `ftpupload.net`)
   - **FTP username** (often starts with `if0_`)
   - **FTP password** (click show / reset if needed)

### FileZilla settings

| Field | Value |
|-------|--------|
| Host | `ftpupload.net` (from FTP Details) |
| Username | From FTP Details (`if0_...`) |
| Password | From FTP Details |
| Port | `21` |

**Site Manager (recommended):** File → Site Manager → New Site:

1. **General:** Host = `ftpupload.net`, Port = `21`, Protocol = **FTP**, Encryption = **Use explicit FTP over TLS if available**
2. **Transfer Settings:** Transfer mode = **Passive**
3. Connect. If TLS fails, try Encryption = **Only use plain FTP (insecure)** once to test.

### If connection fails — try another hostname

All point to the same server; use whichever works on your network:

| Host | When to use |
|------|-------------|
| `ftpupload.net` | Default (from FTP Details) |
| `ftp.epizy.com` | If `ftpupload.net` does not resolve |
| `185.27.134.11` | If DNS is blocked (school/ISP parental controls) |

### If connected but upload fails

| Problem | Fix |
|---------|-----|
| Permission denied | Upload only inside **`htdocs`**; all app folders go under `htdocs/` |
| Transfer stalls | Edit → Settings → Transfers → set **Maximum simultaneous transfers** to `1` |
| Timeout on large folders | Upload one folder at a time: `config`, then `core`, then `views`, etc. |
| Wrong place | Remote path should look like `/htdocs` and `/config`, not empty root only |
| Firewall | Allow FileZilla in Windows Firewall; try phone hotspot to test if school Wi-Fi blocks port 21 |

### Easier alternative: Online File Manager (no FileZilla)

1. vPanel → **Online File Manager** (or File Manager)
2. Open **`htdocs`** → Upload `index.php`, `.htaccess`, `assets/`
3. Upload ZIP of the full app (see section 3) into **`htdocs`**, then **Extract**

To zip on your PC: select folders → right-click → Send to → Compressed (zipped) folder. Upload smaller ZIPs (e.g. `views.zip`) if one big ZIP fails.

### What success looks like

**Inside `htdocs` (FileZilla or File Manager):**

```text
htdocs/
  index.php
  .htaccess
  assets/
  config/
  core/
  controllers/
  models/
  views/
```

---

## 5. Configure database connection

Edit **`config/database.php`** on the server (or before upload):

```php
private $host = "sqlXXX.infinityfree.com";  // from vPanel — not always localhost
private $db_name = "if0_XXXXXX_bhc";       // your database name
private $username = "if0_XXXXXX";          // MySQL username
private $password = "your_mysql_password";
```

Save the file. Wrong hostname is the most common connection error on InfinityFree.

---

## 6. Configure `base_url` (QR codes and tickets)

Edit **`config/app.php`**:

```php
'base_url' => 'https://bhcs.free.nf',
```

(This is already set in `config/app.php` in the repo.)

Rules:

- Use **`https://`** and your real site URL (no trailing slash required, but be consistent).
- If **clean URLs do not work** (404 on `/login`), use:

  ```php
  'base_url' => 'https://bhcs.free.nf/index.php',
  ```

- Do **not** use `localhost` or your LAN IP here for a public site.

---

## 7. Test the site

Open in a browser:

| Page | URL |
|------|-----|
| Home | `https://bhcs.free.nf/` |
| Login | `https://bhcs.free.nf/login` |
| If 404 | `https://bhcs.free.nf/index.php/login` |

1. Log in with `admin` / `admin123`.
2. Open **Patient Registry**, **Queue Stations**, **Queue Management**.
3. Create a test patient and ticket; open the ticket QR link on your phone (must use the **public** URL, not LAN).

---

## 8. Apache rewrite (`.htaccess`)

`htdocs/.htaccess` should contain:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [L]
```

If `/login` returns 404, use URLs with `index.php` (see section 5) or confirm in vPanel that **mod_rewrite** / `.htaccess` is allowed (usually yes on InfinityFree).

---

## 9. Security checklist (production)

- [ ] Change **admin** password (database `users` table or add a user management screen later).
- [ ] Do not commit real `database.php` passwords to a public GitHub repo.
- [ ] Remove or protect `scripts/` if you ever upload them.
- [ ] Use **HTTPS** (InfinityFree provides free SSL — enable in vPanel if needed).

---

## 10. Limits and expectations (free tier)

- Traffic and resource **limits** apply; fine for demo/small use, not heavy production.
- **No guaranteed uptime**; local XAMPP is more reliable for daily clinic queue.
- **Patient displays on LAN TV** should still use a **local server IP** unless the TV can open your public InfinityFree URL (unusual for barangay setup).

---

## 11. Troubleshooting

| Problem | Fix |
|---------|-----|
| FileZilla cannot connect | Use **FTP Details** password (not site login); host `ftpupload.net` port `21`; try `ftp.epizy.com` or `185.27.134.11`; Passive mode; allow FileZilla in firewall |
| FileZilla connects but upload fails | Upload to `htdocs` + FTP root folders; 1 transfer at a time; use Online File Manager + ZIP |
| `Connection error` | Check MySQL **hostname**, database name, user, password in `config/database.php` |
| Blank white page | Enable PHP errors in vPanel temporarily; check file paths (`config` next to `htdocs`) |
| 404 on all routes | Try `index.php/login`; verify `.htaccess` in `htdocs` |
| CSS/layout broken | Ensure `assets/` uploaded under `htdocs/assets/` |
| QR opens wrong site | Update `config/app.php` `base_url` to exact live URL |
| Login works but redirects wrong | `base_url` and site URL must match; clear browser cache |

---

## Quick checklist

1. [ ] MySQL database created and `database_schema.sql` imported  
2. [ ] Entire app inside **`htdocs`** (`index.php`, `assets`, `config`, `core`, `controllers`, `models`, `views`)  
3. [ ] `.htaccess` in `htdocs` and `config/` uploaded (blocks direct access to config)  
4. [ ] `config/database.php` updated with InfinityFree MySQL details  
5. [ ] `config/app.php` `base_url` set to `https://bhcs.free.nf`  
6. [ ] Login tested; change default admin password  

---

*For local barangay deployment (LAN, TV displays), see **[USER_GUIDE.md](USER_GUIDE.md)** sections 15–16.*
