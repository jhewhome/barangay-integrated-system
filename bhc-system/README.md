# Barangay Balong Bato — Integrated Information Systems

**Gawad BIS** (Barangay Integrated System) + **BHC** (Barangay Health Center System)

Patient registry, multi-station queues, clinical workflow, and barangay resident records — integrated for Barangay Balong Bato, San Juan City.

---

## Documentation

**All guides are in the [`docs/`](docs/) folder.**

| Start here | Description |
|------------|-------------|
| **[docs/README.md](docs/README.md)** | Documentation index (full list) |
| **[docs/USER_GUIDE.md](docs/USER_GUIDE.md)** | BHC user guide — queues, clinical workflow, doctor portal |
| **[docs/INTEGRATED_USER_GUIDE.md](docs/INTEGRATED_USER_GUIDE.md)** | Gawad BIS ↔ BHC integration guide |
| **[docs/SOURCE_CODE_AND_DOCUMENTATION.md](docs/SOURCE_CODE_AND_DOCUMENTATION.md)** | Source code & technical documentation |

---

## Systems at a glance

| | Gawad BIS | BHC System |
|---|-----------|------------|
| **Stack** | ASP.NET Core 8, MongoDB | PHP 8+, MySQL |
| **Local URL** | `http://localhost:5003` | `http://localhost/bhc_system/public` |
| **Primary data** | Residents, permits, medicine inventory | Patients, queues, clinical records |

**Integration:** SSO, resident prefill, staff sync, read-only medicine catalog from Gawad.

---

## Quick start (development)

1. Start MongoDB and Gawad BIS (`dotnet run` on Client project).
2. Start XAMPP (Apache + MySQL).
3. Copy `config/gawad_integration.local.example.php` → `config/gawad_integration.local.php`.
4. Open Gawad → Residents → **Register at Health Center**.

Default BHC login: `admin` / `admin123` (change before production).

---

## Project structure (BHC)

```
public/index.php     Entry point + routes
config/              App, database, Gawad integration
core/                Router, auth, Gawad integration classes
controllers/         HTTP handlers
models/              Database access
views/               UI templates
docs/                All documentation and user guides
database_schema.sql  MySQL schema reference
```

Gawad BIS is a separate .NET solution (`Project.Gawad.sln`) — see [docs/SOURCE_CODE_AND_DOCUMENTATION.md](docs/SOURCE_CODE_AND_DOCUMENTATION.md).

---

## License / academic use

Developed as part of the Barangay Integrated System initiative for Balong Bato. Refer to course or barangay authority for distribution terms.
