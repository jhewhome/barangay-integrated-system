# InfinityFree deploy package (separate from local XAMPP)

Your **local** project stays as-is:

- `public/` + `config/`, `core/`, etc. at project root (XAMPP)
- `config/app.php` → uses **`app.local.php`** (LAN IP)

This folder holds a **copy** built for **https://bhcs.free.nf** only.

---

## Build the upload package

1. **Edit MySQL credentials** (once):
   - Copy `config/database.infinityfree.example.php` → `config/database.infinityfree.php`
   - Fill in InfinityFree hostname, database name, username, password from vPanel
   - (Optional: add `config/database.infinityfree.php` to `.gitignore` if you use Git)

2. **Run the build script** from the project root:

```bash
php scripts/build-infinityfree-deploy.php
```

3. Output folder: **`deploy/infinityfree/package/`**

4. **Upload** everything inside `package/` to InfinityFree **`htdocs`** (ZIP + Extract or FileZilla).

---

## What gets copied

| Source (local) | Deploy package |
|----------------|----------------|
| `public/*` | `package/index.php`, `.htaccess`, `assets/` |
| `config/`, `core/`, `controllers/`, `models/`, `views/` | Same names under `package/` |
| `config/app.infinityfree.php` | `package/config/app.php` |
| `config/database.infinityfree.php` or `.example` | `package/config/database.php` |

Local `config/database.php` (root/password) is **never** copied.

---

## After code changes locally

Run the build script again, then re-upload `package/` to InfinityFree.

---

See also: **[docs/DEPLOY_INFINITYFREE.md](../../docs/DEPLOY_INFINITYFREE.md)**
