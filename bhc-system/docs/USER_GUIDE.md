# Barangay Health Center System - User Guide

Integrated Patient Information System with Queue Management for Barangay Health Centers.

> **Gawad BIS integration:** Resident registration, SSO, staff import, and medicine sync are covered in **[INTEGRATED_USER_GUIDE.md](INTEGRATED_USER_GUIDE.md)**.

> **Exporting to PDF:** This file uses plain ASCII punctuation (hyphens, quotes) and is saved as **UTF-8 with BOM** for Windows tools. In Word: *File -> Open* -> select this `.md` file, then *File -> Save As -> PDF*. In VS Code: install *Markdown PDF* extension, then export. If characters still look wrong, ensure your converter encoding is set to **UTF-8**.

---

## **1. System overview**

The BHC System helps health center staff:

- Register and manage **patient records** (linked to Gawad BIS when integration is enabled)
- Route patients to the correct **service station** based on reason for visit
- Run **multi-station queues** (Registration -> Triage -> Consultation -> Pharmacy)
- Capture **triage vitals**, **consultation records**, and **medicine dispensing**
- Schedule **follow-up appointments**
- Issue **clinical documents** (prescription receipts, medical certificates, referrals)
- Show **"Now serving"** on a TV/display for the waiting area
- Give patients a **paperless ticket** via QR code on their phone
- Review **reports** and **audit logs** (admin)

### Default stations

| Station | ID | Purpose |
|--------|-----|---------|
| Registration | 1 | Routing desk only - creates tickets for other stations |
| Triage / Vitals | 2 | Initial assessment and vitals |
| Consultation | 3 | Doctor/nurse consultation |
| Pharmacy | 4 | Medicine dispensing |

### Who operates the queue?

The queue is **staff-operated**. Staff call patients when ready. The system can **auto-call the next patient** after Complete or Skip to reduce manual clicking during busy hours.

**Doctors** use **My patients** for their assigned consultation queue; they do not operate Registration, Triage, or Pharmacy desk pages.

---

## **2. Accessing the system**

### Staff pages (login required)

Use your health center's server address. Examples:

| Setup | Login URL |
|-------|-----------|
| XAMPP (recommended) | `http://YOUR_LAN_IP/bhc_system/public/login` |
| XAMPP (no URL rewrite) | `http://YOUR_LAN_IP/bhc_system/public/index.php/login` |
| PHP dev server | `http://YOUR_LAN_IP:8000/login` |

Replace `YOUR_LAN_IP` with the computer's IPv4 address (e.g. `192.168.1.49`).

You may also arrive signed in via **Gawad BIS** (SSO) when clicking a Health Center link — see the integration guide.

### Patient-facing pages (no login)

| Page | URL pattern | Purpose |
|------|-------------|---------|
| Station display | `/display/{stationId}` | TV screen: Now serving + Next |
| Ticket status | `/ticket/{ticketId}` | Patient's live ticket on phone |
| Fullscreen QR | `/ticket/{ticketId}/qr` | Large QR for printing/showing |

Example (XAMPP): `http://192.168.1.49/bhc_system/public/display/2` (Triage)

### Demo login (first install)

If no users exist in the database, a default admin is created automatically:

- **Username:** `admin`
- **Password:** `admin123`

Change this password before real deployment.

---

## **3. User roles**

| Role | Access |
|------|--------|
| **Admin** | All staff features + Reports + Activity Log + **Staff accounts** + **Medicine list** + Gawad staff import |
| **Staff** | Staff dashboard, Patients, Appointments, Health Center queues (Registration through Pharmacy) |
| **Doctor** | **My patients** (`/doctor`) — assigned consultation queue, patient charts, consultations, clinical documents |

### After login

| Role | Lands on | Sidebar / menu |
|------|----------|----------------|
| **Staff** | **Staff dashboard** (`/staff`) | Patients, Appointments, **Health Center** (queue submenu) |
| **Admin** | **Admin dashboard** (`/admin`) | Same as staff + **Administration** (Reports, Activity Log, Medicine list, Staff accounts) |
| **Doctor** | **My patients** (`/doctor`) | Doctor menu only |

Staff and doctors cannot open admin pages (reports, audit, staff accounts) by URL. Doctors cannot open clinic desk queue pages.

### Account menu (top-right, all roles)

| Link | Purpose |
|------|---------|
| **Change password** | Update your own login password |
| **Name on documents** | Doctors only — how your name appears on certificates and referrals |

### Create a staff account (admin)

**Administration -> Staff accounts** (or `/users`).

1. Click **Add staff account** (or **Import Gawad staff** when syncing from Gawad BIS).
2. For manual create: enter **username**, **password** (min. 8 characters), optional **display name**, and **role** (Staff, Doctor, or Admin).
3. Click **Create account**.

| Action | Use when |
|--------|----------|
| **Import Gawad staff** | Pull barangay users from Gawad BIS for SSO (see integration guide) |
| **Reset password** | Staff forgot password |
| **Deactivate** | Person left or should not sign in (cannot deactivate your own account) |
| **Activate** | Re-enable a deactivated account |

At least one **active admin** must remain; the system will not deactivate the last admin.

**Doctors** are created manually in BHC with role **Doctor** (not auto-imported from Gawad).

### Create a staff account (command line — optional)

From the project folder:

```bash
php scripts/create_staff_user.php nurse1 YourPassword staff
php scripts/create_staff_user.php dr.cruz YourPassword doctor
```

---

## **4. Navigation (after login)**

### Main sidebar

| Menu | Description |
|------|-------------|
| **Staff / Admin dashboard** | Daily workspace (`/staff` or `/admin`) |
| **My patients** | Doctor dashboard only (`/doctor`) |
| **Patients** | Patient list, registration, history |
| **Appointments** | Upcoming follow-up visits (`/appointments`) |
| **Health Center** | Expandable submenu (see below) |
| **Reports** | Report hub (admin only) |
| **Activity Log** | Audit trail (admin only) |
| **Medicine list** | Local fallback names; shows Gawad sync status (admin only) |
| **Staff accounts** | User management (admin only) |
| **Logout** | End session |

### Health Center submenu

| Link | Path | Purpose |
|------|------|---------|
| Registration Queue | `/queue/1` | Patient routing desk |
| Triage / Vitals | `/queue/2` | Triage station queue |
| Consultation | `/queue/3` | Consultation desk queue |
| Pharmacy Queue | `/queue/4` | Pharmacy station queue |
| All Stations | `/stations` | Station list overview |
| Queue Management | `/coordinator` | All stations on one screen |

Before login, only a simple header (Home + Login) is shown.

---

## **5. Daily workflow (step by step)**

### **Step 1 - Register or find a patient**

**Staff (standard path):**

1. Register the resident in **Gawad BIS**, then use **Register at Health Center** on the resident profile (prefills BHC form via SSO).
2. Or use **Emergency walk-in** on the Patients page when immediate care is required and Gawad registration is not possible first.

**Admin:**

- May use **Add patient** directly, or follow the Gawad path above.

**On the registration form:**

1. Enter **first name**, **last name**, **sex**, and **date of birth** (required).
2. Optional: middle name, suffix, contact, address, PhilHealth, emergency contact, notes.
3. **Confirm Balong Bato residency** (required for new registrations).
4. The system assigns a **BHC ID** (e.g. `BHC-000001`).
5. While typing the name, **duplicate suggestions** appear — click a match to use an existing patient instead of creating a duplicate.

See **[INTEGRATED_USER_GUIDE.md](INTEGRATED_USER_GUIDE.md)** for Gawad handoff, linking, and emergency walk-in rules.

### **Step 2 - Route at Registration (routing desk)**

1. Open **Health Center -> Registration Queue** (Station 1), or **Patient Routing** from the staff dashboard.
2. **Search** the patient by name or BHC ID.
3. Enter **Reason for visit** (free text; suggestions may appear).
4. Choose **Assign / route to station** (Triage, Consultation, or Pharmacy).
5. Click **Create ticket**.

If the patient has an **appointment today**, a banner appears on the routing form.

The patient receives a **ticket number** (e.g. `TR-001` for Triage) and can open the **ticket screen** with QR code.

**Note:** Only **one consultation ticket per patient per day** is allowed. The system warns if you try to route twice to Consultation.

### **Step 3 - Station staff serves the queue**

For each service station (Triage, Consultation, Pharmacy):

1. Open the station from **Health Center**, or use **Queue Management**.
2. **Call next** or **Call** a specific waiting ticket.
3. When a patient is **Now serving**, complete the clinical form for that station (see **Section 6**).
4. **Complete** or **Skip** when done at this station.

**Recall to queue:** If a skipped patient returns the same day at the same station, use **Recall** on that ticket (station page or Queue Management). The same ticket number returns to **waiting**.

**Auto-call:** After **Complete** or **Skip**, the system automatically calls the **next waiting** patient if the station is idle. **Recall** does not auto-call.

**Consultation — assign doctor:** At the Consultation desk, when a patient is Now serving, staff can **assign a doctor** from the dropdown. The patient then appears on that doctor's **My patients** queue.

### **Step 4 - Patient knows their turn**

Patients can be informed in three ways:

1. **TV / monitor** - **Patient display** for that station (`/display/{stationId}`). See **Section 16** for setup. Tap **Enable sound** once; the display **flashes** and plays a **chime** when Now serving changes.
2. **Phone (QR ticket)** - Patient scans QR on ticket screen. Tap **Enable sound** on the ticket page. Page refreshes every 5 seconds; when status becomes **SERVING**, the patient sees *"You are being called now"* and hears a **chime**.
3. **Waiting area** - Staff may still call names verbally; display and ticket are backups.

> **Mobile push notifications (SMS / app alerts) are not included.**

### **Step 5 - End of visit**

- Mark ticket **Complete** at the last station (usually Pharmacy).
- If the patient needs another station, create a **new routed ticket** from Registration.
- Schedule a **follow-up appointment** from the patient's history page if needed.

---

## **6. Clinical workflow at stations**

When a ticket is **Now serving**, station staff capture clinical data before completing the ticket.

### Triage / Vitals (Station 2)

Open **Health Center -> Triage / Vitals**, call the patient, then enter:

| Field | Examples |
|-------|----------|
| Blood pressure | Systolic / diastolic (e.g. 120 / 80) |
| Temperature | e.g. 36.5 C |
| Pulse rate | e.g. 72 |
| Weight / Height | kg and cm |
| Triage notes | Chief complaint, observations |

Click **Save triage record**, then **Complete** when the patient may proceed to the next station.

### Consultation (Station 3 — desk)

Open **Health Center -> Consultation**. When Now serving:

1. Optionally **assign a doctor** (patient appears on doctor's **My patients**).
2. Enter **diagnosis** (required) and **clinical notes**.
3. Optionally add **prescribed medicines** (picker shows Gawad inventory stock when sync is enabled).
4. Click **Save consultation record**, then **Complete**.

Doctors may instead work from **My patients** (Section 7) for the same consultation forms and documents.

If the patient had an **appointment today**, saving the consultation can mark that appointment completed.

### Pharmacy (Station 4)

Open **Health Center -> Pharmacy Queue**. When Now serving:

1. Review medicines from the consultation record (or enter dispensed items).
2. Record quantities and source (**Clinic stock**, **Request from LGU**, or **Buy externally**).
3. Click **Record medicines dispensed**, then **Complete**.

Stock hints come from **Gawad BIS** when medicine sync is active. Update barangay inventory in Gawad separately after dispensing.

---

## **7. Doctor portal**

Doctors sign in and land on **My patients** (`/doctor`).

### Assigned patient queue

The dashboard shows patients **assigned to you** at Consultation today (waiting and serving combined):

| Action | When |
|--------|------|
| **Call next** | Call the oldest waiting patient assigned to you |
| **Call** | Call a specific waiting patient |
| **Complete** | Consultation finished |
| **Skip** | Patient absent |

The page **auto-refreshes every 5 seconds** when your queue changes. Tap **Enable sound** for a chime when new patients are waiting. If you are editing a form, a banner offers **Refresh now** instead of interrupting you.

### Patient clinical record

Click a patient to open their chart (`/doctor/patients/{id}`):

- Demographics and registry notes
- Today's vitals and past triage
- Consultation history
- Add **diagnosis**, **clinical notes**, and **medicines**
- Issue **medical certificate**, **referral**, or **recommendation**
- View issued **clinical documents**

When the patient is **Now serving**, save the consultation record, then **Complete consultation**.

### Name on documents

Account menu (top-right) -> **Name on documents** — set how your name prints on certificates and referrals (e.g. *Dr. Maria S. Cruz, M.D.*).

---

## **8. Queue Management (busy days)**

**Queue Management** (`/coordinator`) is a single dashboard for all service stations.

Use it when:

- One person monitors multiple stations
- You want fewer clicks than opening each station separately

For each station you see:

- Waiting / Serving counts
- **Now serving** with Complete / Skip
- **Call next** when idle
- **Next waiting** list with individual **Call** buttons
- **Skipped today** list with **Recall**

The page **auto-refreshes every 5 seconds**. Auto-call rules are the same as on individual station pages.

---

## **9. Patients module**

| Action | How |
|--------|-----|
| View all patients | **Patients** -> browse list (paginated, searchable) |
| Register (staff) | **Register via Gawad BIS** or **Emergency walk-in** |
| Register (admin) | **Add patient** or Gawad path |
| Edit patient | **Patients** -> select patient -> **Edit** |
| Patient history | **Patients** -> select patient -> **History** (visits, triage, consultations, medicines, documents) |
| Schedule appointment | From patient **History** page |
| Search while routing | Registration desk search field |
| Archive / restore | Admin actions on patient record |

---

## **10. Appointments**

**Appointments** (`/appointments`) lists scheduled follow-up visits across all patients.

| Feature | Description |
|---------|-------------|
| Date filter | Show appointments from/to selected dates |
| Status | Scheduled, completed, cancelled, no-show |
| Patient link | Click patient name to open history |
| At Registration | Banner when routing a patient with an appointment today |
| At Consultation | Saving consultation can complete today's linked appointment |

Create appointments from a patient's **History** page when scheduling a return visit.

---

## **11. Reports (admin)**

Open **Reports** (`/reports`) for the report hub. Each report supports date filtering and **CSV export**.

| Report | Path | Content |
|--------|------|---------|
| **Daily operations** | `/reports/daily` | Visits, queue tickets by station, triage counts, reasons, hourly arrivals |
| **Queue report** | `/reports/monthly` | Tickets per station, completed/skipped, average wait and service times |
| **Clinical summary** | `/reports/clinical` | Consultations, diagnoses, medicines prescribed/dispensed, receipts |
| **Appointments** | `/reports/appointments` | Scheduled, completed, cancelled, no-show by period |

Queue reports use ticket **creation date** in the selected period. Completed tickets remain in the database for history.

---

## **12. Activity Log (admin)**

**Activity Log** (`/audit`) shows who did what and when, including:

| Action | When logged |
|--------|-------------|
| `login` / `logout` / `login_failed` | Sign in, sign out, wrong password |
| `patient_create` / `patient_update` | Patient registered or edited |
| `ticket_create`, `ticket_call`, `ticket_call_next`, `ticket_call_next_auto` | Queue actions |
| `ticket_complete`, `ticket_skip`, `ticket_recall` | Ticket finished, skipped, or recalled |
| `user_create`, `user_deactivate`, `user_activate`, `user_password_reset` | Staff account changes |
| `user_password_change` | Self-service password change |
| `gawad_staff_sync`, `gawad_link` | Integration events (when used) |

Useful for accountability and incident review.

---

## **13. Medicine list (admin)**

**Medicine list** (`/medicines`) manages the **local fallback** catalog (medicine names for the prescription picker).

When **Gawad medicine sync** is enabled:

- Consultation and pharmacy pickers use the **live Gawad BIS inventory** (names + stock levels).
- This page shows sync status and links to **Open Gawad BIS Medicines** for stock management.

When sync is unavailable, pickers fall back to the local list on this page.

See **[INTEGRATED_USER_GUIDE.md](INTEGRATED_USER_GUIDE.md)** Section 9 for medicine sync details.

---

## **14. Ticket statuses**

| Status | Meaning |
|--------|---------|
| **waiting** | In queue, not yet called |
| **serving** | Currently being served (Now serving) |
| **done** | Completed successfully |
| **skipped** | Patient absent or could not be served (can **Recall to queue** same day at same station) |

Queues are **daily** (based on ticket creation date). Old tickets stay in the database for reports. **Recall** only works for **today's** skipped tickets at that station.

---

## **15. QR codes and LAN setup**

For patients to scan QR codes on their phones, phones must be on the **same Wi-Fi/LAN** as the server.

Edit `config/app.local.php` (or `config/app.php`):

```php
'base_url' => 'http://YOUR_LAN_IP/bhc_system/public',
```

Examples:

- XAMPP: `http://192.168.1.49/bhc_system/public`
- With index.php fallback: `http://192.168.1.49/bhc_system/public/index.php`

Do **not** use `localhost` in `base_url` if patients use phones — they cannot reach your PC's localhost.

---

## **16. Waiting area display setup (TV / monitor / tablet)**

The **Patient display** page shows **Now serving**, the **Next** waiting tickets, a **flash** when the number changes, and a **chime** when staff call the next patient. Tap **Enable sound** once per browser (required by Chrome/tablets). It refreshes automatically every **3 seconds**. **No login** is required on the TV or tablet.

**Patient ticket page** (`/ticket/{id}`): same **Enable sound** + chime when that ticket becomes **SERVING** (for patients watching their phone).

### What you need

| Item | Notes |
|------|--------|
| **Server PC** | XAMPP running; fixed **LAN IP** (e.g. `192.168.1.49`) |
| **Display device** | TV + mini PC, wall tablet, or second monitor |
| **Network** | Display device on the **same Wi-Fi/LAN** as the server |
| **Browser** | Chrome or Edge (recommended) |

### Display URLs (bookmark these)

Replace `YOUR_LAN_IP` with your server's IPv4 address.

| Waiting area | Station ID | Bookmark URL (XAMPP) |
|--------------|------------|----------------------|
| Triage / Vitals | 2 | `http://YOUR_LAN_IP/bhc_system/public/display/2` |
| Consultation | 3 | `http://YOUR_LAN_IP/bhc_system/public/display/3` |
| Pharmacy | 4 | `http://YOUR_LAN_IP/bhc_system/public/display/4` |

If clean URLs do not work, use:

`http://YOUR_LAN_IP/bhc_system/public/index.php/display/2` (change `2` to `3` or `4` as needed)

> **Do not use Station 1 (Registration)** for a waiting-area screen. Registration is the routing desk only; patient queues run at Triage, Consultation, and Pharmacy.

**Open from the system:** **Health Center** -> choose a station -> **Patient display**, or use display links on the dashboard.

### Hardware options

| Option | Best for |
|--------|----------|
| **TV + mini PC / old laptop** (HDMI) | Main waiting room; large numbers easy to read |
| **Wall tablet** (Android / iPad + mount) | Small room; use ticket numbers only (see privacy below) |
| **Second monitor** at the station | Low cost; staff see queue + display side by side |

### Setup steps (first time)

1. **Server:** Start XAMPP (Apache + MySQL). Note the PC's LAN IP (e.g. `192.168.1.49`).
2. **Test:** On a phone or the display device, open `http://YOUR_LAN_IP/bhc_system/public/display/2`. The page must load (not `localhost`).
3. **Display device:** Open the correct URL for that waiting area (Triage = `2`, Consult = `3`, Pharmacy = `4`).
4. Click **Fullscreen** on the page (or press **F11**).
5. **Sound:** Tap **Enable sound** on the display (or ticket page on a phone); turn up TV/tablet/phone volume. You should hear a short test chime.
6. **Privacy:** Leave **Show names** off unless your center approves names on a public screen. Default shows **ticket numbers only** (e.g. `TR-003`).
7. **Power:** Set the display device so the screen does **not** sleep while plugged in.
8. **Leave the tab open** all day; it auto-refreshes - do not close it during clinic hours.

### Windows kiosk mode (optional - auto-open on startup)

Create a desktop shortcut to Chrome (edit the IP and station ID):

```text
"C:\Program Files\Google\Chrome\Application\chrome.exe" --kiosk --noerrdialogs "http://192.168.1.49/bhc_system/public/display/2"
```

Put the shortcut in the **Startup** folder so the display opens after reboot.

### How many displays?

| Layout | Suggestion |
|--------|------------|
| One shared waiting room (Triage only) | 1 TV -> `/display/2` |
| Separate consult waiting | Add 1 TV -> `/display/3` |
| Pharmacy counter area | Add 1 TV -> `/display/4` |
| Very small center | Start with **one** display at the busiest station |

Staff still **Call next** / **Complete** from **Health Center** or **Queue Management**; the TV updates within a few seconds.

### Daily routine

| When | Action |
|------|--------|
| **Morning** | Start server (XAMPP) -> open display URL -> **Enable sound** -> Fullscreen -> test chime |
| **During clinic** | Keep display tab open; staff operate queues as usual |
| **Evening** | Close browser optional; server can stay on for reports |

### Display checklist

- [ ] Server has static LAN IP  
- [ ] Display opens correct station URL (`/display/2`, `3`, or `4`)  
- [ ] Same Wi-Fi/LAN as server  
- [ ] Fullscreen or kiosk mode enabled  
- [ ] Chime tested (one click on page first)  
- [ ] Staff trained: Call next / Complete updates the screen  

---

## **17. Quick reference - URLs**

| Page | Path |
|------|------|
| Public home | `/` |
| Staff dashboard | `/staff` |
| Admin dashboard | `/admin` |
| Doctor — My patients | `/doctor` |
| Login | `/login` |
| Patients | `/patients` |
| Add patient | `/patients/create` |
| Patient history | `/patients/{id}/history` |
| Edit patient | `/patients/{id}/edit` |
| Appointments | `/appointments` |
| Change your password | `/account/password` |
| Name on documents (doctor) | `/account/document-name` |
| All stations | `/stations` |
| Registration routing | `/queue/1` |
| Triage queue | `/queue/2` |
| Consultation queue | `/queue/3` |
| Pharmacy queue | `/queue/4` |
| Queue Management | `/coordinator` |
| Reports hub | `/reports` |
| Daily report | `/reports/daily` |
| Queue report | `/reports/monthly` |
| Clinical report | `/reports/clinical` |
| Appointments report | `/reports/appointments` |
| Activity Log | `/audit` |
| Medicine list | `/medicines` |
| Staff accounts | `/users` |
| Patient display | `/display/{stationId}` |
| Patient ticket | `/ticket/{ticketId}` |

Station IDs (default): 1 = Registration, 2 = Triage, 3 = Consultation, 4 = Pharmacy.

---

## **18. Troubleshooting**

| Problem | What to check |
|---------|----------------|
| Login redirects to wrong URL | Use full path: `/bhc_system/public/login` not `/login/login.php` |
| QR does not open on phone | Set `base_url` to LAN IP; same Wi-Fi; allow firewall |
| Search patient not working | Hard refresh (Ctrl+F5); check browser console |
| Sidebar missing | Sidebar appears only **after login** |
| Auto-call did not happen | No more waiting patients; or station still has someone serving |
| 404 on clean URLs | Use `.../public/index.php/login` or enable Apache `mod_rewrite` |
| Display page blank or cannot connect | Use LAN IP, not `localhost`; check Apache and Windows Firewall |
| Display never changes | Staff must **Call next**; page auto-refreshes every 3 seconds |
| No chime on TV | Click **Fullscreen** or the page once; check volume; use Chrome/Edge |
| Wrong ticket numbers on screen | Wrong bookmark (e.g. Pharmacy TV must use `/display/4`) |
| Display asks for login | Use `/display/2` not `/queue/2` (staff queue page) |
| Staff cannot open Add patient | By design — use Gawad or Emergency walk-in (integration guide) |
| Doctor blocked from queue desk | Doctors use **My patients**, not Consultation queue page |
| Second consultation ticket rejected | One consultation ticket per patient per day |
| Medicines missing in picker | Gawad sync off or unavailable — see Medicine list (admin) |
| SSO / Gawad registration issues | See **[INTEGRATED_USER_GUIDE.md](INTEGRATED_USER_GUIDE.md)** |

---

## **19. Recommended setup at the health center**

1. **Registration PC** — routing desk; Gawad BIS for resident enrollment  
2. **One PC per station** (or shared via Queue Management) — Triage, Consult, Pharmacy  
3. **Doctor PC** — **My patients** and patient charts  
4. **TV/monitor per waiting area** — see **Section 16** for display URLs  
5. **One server PC** — XAMPP + Gawad BIS + MongoDB on LAN; set static IP  
6. **Admin account** — staff logins, reports, Gawad staff import  

---

## **20. Workflow diagram**

**Interactive Mermaid diagrams** (recommended for editing and high-quality export): see **[WORKFLOW_DIAGRAM.md](WORKFLOW_DIAGRAM.md)** — copy into [Mermaid Live Editor](https://mermaid.live) to preview and export PNG/SVG.

**Hand-drawn / exported workflow diagram:** see **[WORKFLOW_DIAGRAM.md](WORKFLOW_DIAGRAM.md)** to generate PNG or SVG from the Mermaid sources (`workflow-diagram.mmd`, `ticket-lifecycle.mmd`).

---

## **21. Related documents**

| Document | Content |
|----------|---------|
| [README.md](README.md) | Documentation index |
| [INTEGRATED_USER_GUIDE.md](INTEGRATED_USER_GUIDE.md) | Gawad BIS integration (SSO, resident handoff, staff import, medicine sync) |
| [SOURCE_CODE_AND_DOCUMENTATION.md](SOURCE_CODE_AND_DOCUMENTATION.md) | Technical architecture and APIs |
| [WORKFLOW_SUMMARY.md](WORKFLOW_SUMMARY.md) | Process overview and ticket lifecycle |
| [WORKFLOW_DIAGRAM.md](WORKFLOW_DIAGRAM.md) | Visual workflow diagrams |
| [DEPLOY_INFINITYFREE.md](DEPLOY_INFINITYFREE.md) | Cloud deployment guide |

---

*Document version: BHC System with staff/admin/doctor roles, Health Center queue menu, triage/consultation/pharmacy clinical workflow, doctor portal, appointments, reports hub, Gawad medicine sync, waiting-area displays, Queue Management, auto-call, and Activity Log.*
