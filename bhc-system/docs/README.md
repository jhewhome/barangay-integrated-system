# Documentation — Barangay Integrated System

**Gawad BIS** + **Barangay Health Center (BHC) System**  
Barangay Balong Bato, San Juan City

All guides and documentation for this project are in this **`docs/`** folder.

---

## User guides

| Document | Audience | Description |
|----------|----------|-------------|
| **[USER_GUIDE.md](USER_GUIDE.md)** | BHC staff, doctors, admins | Full BHC operations: queues, clinical workflow, doctor portal, appointments, reports, displays |
| **[INTEGRATED_USER_GUIDE.md](INTEGRATED_USER_GUIDE.md)** | Barangay staff, health workers | **Gawad BIS ↔ BHC integration:** SSO, Register at Health Center, staff import, medicine sync |

---

## Process & diagrams

| Document | Audience | Description |
|----------|----------|-------------|
| **[WORKFLOW_SUMMARY.md](WORKFLOW_SUMMARY.md)** | Supervisors, trainers | End-to-end process overview and ticket lifecycle |
| **[WORKFLOW_DIAGRAM.md](WORKFLOW_DIAGRAM.md)** | All | Mermaid workflow diagrams and export instructions |
| [workflow-diagram.mmd](workflow-diagram.mmd) | — | Mermaid source: full visit workflow |
| [ticket-lifecycle.mmd](ticket-lifecycle.mmd) | — | Mermaid source: ticket state machine |

---

## Technical & deployment

| Document | Audience | Description |
|----------|----------|-------------|
| **[SOURCE_CODE_AND_DOCUMENTATION.md](SOURCE_CODE_AND_DOCUMENTATION.md)** | Developers, IT, thesis reviewers | Architecture, source tree, APIs, database, configuration |
| **[DEPLOY_INFINITYFREE.md](DEPLOY_INFINITYFREE.md)** | IT / deploy | BHC hosting on InfinityFree |
| **MySQL schema (`database_schema.sql`)** | Developers | At project root (not in `docs/`) — described in [SOURCE_CODE_AND_DOCUMENTATION.md](SOURCE_CODE_AND_DOCUMENTATION.md) |

---

## Quick start (local development)

| System | URL |
|--------|-----|
| Gawad BIS | `http://localhost:5003` |
| BHC | `http://localhost/bhc_system/public` |

1. Start Gawad BIS and XAMPP (Apache + MySQL).
2. Copy `config/gawad_integration.local.example.php` → `config/gawad_integration.local.php`.
3. Match integration keys in Gawad `appsettings.json` and BHC config.
4. Gawad → Residents → **Register at Health Center**.

Default BHC admin (first install): `admin` / `admin123` — change before production.

---

## Export to PDF

Open any `.md` file in Microsoft Word (*File → Open* → Save as PDF) or use a Markdown PDF extension in VS Code. Use UTF-8 encoding.

---

*Documentation package — June 2026*
