# Barangay Health Center System - Workflow & Process Summary

This document summarizes how the BHC System works end to end: who does what, how patients move through the health center, and how data flows through the application. For step-by-step instructions and URLs, see [USER_GUIDE.md](USER_GUIDE.md).

---

## **1. Purpose**

The system combines a **patient registry** with a **multi-station queue** so barangay health centers can:

- Keep one record per patient (BHC ID)
- Route each visit to the right service (Triage, Consultation, Pharmacy)
- Serve patients in fair order with visible "Now serving" numbers
- Alert waiting patients via **display screen** and **phone ticket** (flash + optional chime)
- Reduce paper tickets using QR codes on patient phones
- Manage **staff logins** (admin) and track activity / monthly statistics for supervisors

---

## **2. Main actors**

| Actor | Role in the process |
|-------|---------------------|
| **Registration staff** | Register or find patients; create routed queue tickets |
| **Station staff** (Triage, Consult, Pharmacy) | Call, serve, complete, skip, or **recall** patients at their station |
| **Coordinator / supervisor** | Monitor all stations from **Queue Management**; recall skipped patients when they return |
| **Admin** | Full operations plus **Monthly Reports**, **Activity Log**, and **Staff accounts** |
| **Patient** | Waits with ticket number; watches display or phone ticket; no login required |

---

## **3. System access (login and roles)**

Staff and administrators use the same **login** page but land on **different dashboards** and menus.

| Role | After sign-in | What they see |
|------|---------------|---------------|
| **Staff** | **Staff dashboard** (`/staff`) | Patient Registry, Queue Stations, Queue Management |
| **Admin** | **Admin dashboard** (`/admin`) | Everything staff has, plus **Administration**: Monthly Reports, Activity Log, Staff accounts |

**Wrong-role access:** If staff open `/admin` (or other admin URLs), they are **redirected** to the staff dashboard with an error message. If an admin opens `/staff`, they are redirected to the admin dashboard with an info message. This avoids a blank "Forbidden" page.

**Staff accounts (admin only)** — `/users`:

- Add users with role `staff` or `admin`; reset passwords; activate or deactivate
- Cannot deactivate your own account or the **last active admin**
- Logged as `user_create`, `user_deactivate`, `user_activate`, `user_password_reset`

Staff cannot open admin pages by typing the URL directly.

---

## **4. Stations and responsibilities**

| ID | Station | Role in workflow |
|----|---------|------------------|
| 1 | **Registration** (Patient Routing) | Search patient, enter reason for visit, assign target station. **Does not hold a service queue** — only creates tickets for other stations. |
| 2 | **Triage / Vitals** | First clinical stop when routed here |
| 3 | **Consultation** | Doctor/nurse consultation when routed here |
| 4 | **Pharmacy** | Medicine dispensing; often the last stop |

A single visit may use **one or more** service stations. Each new destination normally gets a **new ticket** from Registration. Exception: a **skipped** ticket at a service station can be **recalled** to waiting (same ticket number) if the patient returns the same day.

---

## **5. End-to-end process (typical visit)**

### Phase A — Arrival and registration

1. Patient arrives at the health center.
2. Staff opens **Patient Registry** and either:
   - **Adds** a new patient (name, sex, date of birth → system assigns **BHC ID**), or
   - **Finds** an existing patient (search / autocomplete).
3. Patient record is stored for future visits.

### Phase B — Routing (Registration desk)

1. Staff opens **Patient Routing** (Registration, Station 1).
2. Staff searches patient by name or BHC ID.
3. Staff records **reason for visit** and selects **target station** (Triage, Consultation, or Pharmacy).
4. System creates a **queue ticket** for that station:
   - Status: `waiting`
   - Ticket number: station prefix + daily sequence (e.g. `TR-001`, `CN-002`, `PH-003`)
5. Staff shows the patient the **ticket screen** (QR). Patient may scan to follow status on their phone.

### Phase C — Waiting and patient alerts

1. Patient waits in the area for the assigned station.
2. **Waiting area display** (`/display/{stationId}`) — for TV/tablet in the waiting room:
   - Shows **Now serving** and **Next** waiting tickets
   - Refreshes every **3 seconds**
   - **Flashes** when Now serving changes
   - **Chime** when Now serving changes, after staff tap **Enable sound** once (browser requirement)
3. **Patient ticket page** (`/ticket/{ticketId}`) — on the patient's phone:
   - Refreshes every **5 seconds**
   - When status becomes **SERVING**: message *"You are being called now"*
   - **Chime** on that transition, after patient taps **Enable sound** once
4. Staff may still **call names verbally**; display and ticket are digital backups.

> The system does **not** send SMS or mobile push notifications. Patients rely on the TV display, the QR ticket page, and staff voice.

### Phase D — Service at station

1. Station staff opens that station's queue (**Queue Stations**) or uses **Queue Management** for all stations.
2. Staff actions:

| Action | Effect |
|--------|--------|
| **Call next** | Oldest `waiting` ticket → `serving` (Now serving) |
| **Call** (specific ticket) | That ticket → `serving` |
| **Complete** | Ticket → `done`; step finished at this station |
| **Skip** | Ticket → `skipped` (e.g. patient absent, did not hear call) |
| **Recall to queue** | Today's `skipped` ticket at this station → `waiting` again (**same ticket number**) |

3. **Auto-call:** After **Complete** or **Skip**, if no one is `serving` and more patients are `waiting`, the system **automatically calls the next** patient (`ticket_call_next_auto`).

4. **Recall does not auto-call** — staff use **Call** or **Call next** when the returned patient should be served.

5. Only **one** patient can be `serving` per station at a time.

**When to recall vs new ticket**

| Situation | What to do |
|-----------|------------|
| Skipped by mistake; patient still on site, same day, same station | **Recall to queue** on station page or Queue Management |
| Patient left and came back later, or different station needed | **New ticket** from Registration (routing) |
| Recall not allowed (wrong day or not skipped) | **New ticket** from Registration |

### Phase E — Further stations or end of visit

- If the patient needs **another service**, staff returns to **Registration** and creates a **new ticket** for the next station.
- When finished (usually after Pharmacy), staff marks **Complete** at the final station.
- Tickets remain in the database for **monthly reports** and history (skip and recall events stay in the **Activity Log**).

---

## **6. Ticket lifecycle**

See **[WORKFLOW_DIAGRAM.md](WORKFLOW_DIAGRAM.md)** (ticket state diagram) and **[ticket-lifecycle.mmd](ticket-lifecycle.mmd)** for a visual of ticket statuses.

| Status | Meaning |
|--------|---------|
| `waiting` | In line, not yet called |
| `serving` | Currently being served (Now serving) |
| `done` | Successfully completed at this station |
| `skipped` | Not served (absent, etc.); may **recall** to `waiting` same day at same station |

Queues are **per station, per calendar day** (`DATE(created_at) = today`).

---

## **7. Staff operating modes**

### Per-station mode

**Queue Stations** → open station → **Call** / **Complete** / **Skip** / **Recall** (on skipped rows in **Today's tickets**). Best when one staff member owns one desk.

### Queue Management mode (busy days)

One screen (`/coordinator`) shows **all service stations** (except Registration):

- Waiting and serving counts per station
- **Now serving** with Complete / Skip
- **Call next** and per-ticket **Call** for the next waiting list
- **Skipped today** with **Recall** (same rules as station page)
- Auto-refresh every **5 seconds**
- Same **auto-call** rules after Complete / Skip

Use when one person oversees multiple lines or during peak hours.

---

## **8. Patient notification flow**

| Channel | How it works |
|---------|----------------|
| **TV / monitor** | Fullscreen `/display/{stationId}`; **Enable sound** once; flash + chime when Now serving changes; refresh every 3 s |
| **Phone (QR ticket)** | `/ticket/{ticketId}`; **Enable sound** once; chime when status becomes **SERVING**; refresh every 5 s |
| **Staff queue actions** | **Call** / **Call next** update the database; they do **not** play sound on the staff PC — alerts run on display/ticket only |
| **Verbal** | Staff call names; digital channels supplement |

**LAN note:** For QR codes on phones, `base_url` in `config/app.php` must use the server's **LAN IP**, not `localhost`.

**Browser note:** Chrome and tablets block audio until the user taps **Enable sound** (or interacts with the page). Open the display each morning and enable sound once.

---

## **9. Administration and oversight**

### Staff accounts (admin)

- Managed at `/users` (sidebar: **Staff accounts**)
- Onboard registration and station staff without database tools
- Default `admin` / `admin123` on first install if no users exist — change password immediately

### Monthly Reports (admin)

- Filter by month (YYYY-MM)
- Totals: tickets created, completed, **skipped**
- Per-station breakdown and average wait/service times
- Based on ticket **creation date** in that month (recall does not remove a prior skip from counts)

### Activity Log (admin)

Records accountability events, including:

- `login` / `logout` / `login_failed`
- `patient_create`, `patient_update`
- `ticket_create`, `ticket_call`, `ticket_call_next`, `ticket_call_next_auto`
- `ticket_complete`, `ticket_skip`, **`ticket_recall`**
- `user_create`, `user_deactivate`, `user_activate`, `user_password_reset`, `user_password_change` (self-service)

---

## **10. Planned enhancements (coming soon)**

These are **not** in the current build; they are listed on the **admin dashboard** for transparency:

| Feature | Status |
|---------|--------|
| Station setup UI (add/rename stations) | To follow |
| SMS / push reminders | To follow |
| Patient self check-in kiosk | To follow |

---

## **11. System boundaries (what the system does not do)**

- **No automatic routing between stations** — staff create a new ticket at Registration for each new destination (unless **recalling** a same-day skipped ticket at the **same** station).
- **No patient self-check-in** — all tickets are staff-created.
- **No SMS/push** — patients use display, ticket page, and staff voice.
- **No voice announcement (TTS)** — chime only; staff call names.
- **No auto-skip** for no-shows — staff must press Skip.
- **No un-skip across days** — recall only works for **today's** skipped tickets at that station.
- Registration (Station 1) does not use Call / Complete / Skip / Recall for a service queue.
- **No self-registration for staff** — only an admin creates logins under Staff accounts.

---

## **12. Physical setup (recommended)**

| Location | System use |
|----------|------------|
| Registration desk | Patient Registry + Patient Routing |
| Each service room | Station queue page |
| Optional central desk | Queue Management |
| Waiting areas | Display URL per station; **Enable sound**; fullscreen |
| Patient phones | QR ticket page; **Enable sound** if using chime |
| Server PC | XAMPP or hosted PHP, MySQL, static LAN IP (or InfinityFree for cloud) |

---

## **13. Workflow diagram**

End-to-end process overview (patient registry, routing, waiting-area alerts, station queue, recall, and administration):

See **[WORKFLOW_DIAGRAM.md](WORKFLOW_DIAGRAM.md)** and **[workflow-diagram.mmd](workflow-diagram.mmd)** to preview or export the full workflow diagram.


## **14. Quick comparison: documents in this project**

| Document | Audience | Content |
|----------|----------|---------|
| **[README.md](README.md)** | All | Documentation index |
| **[WORKFLOW_SUMMARY.md](WORKFLOW_SUMMARY.md)** (this file) | Supervisors, trainers | Process overview, roles, lifecycle, recall, alerts |
| **[USER_GUIDE.md](USER_GUIDE.md)** | Daily staff, doctors, admins | How-to, URLs, displays, clinical workflow, troubleshooting |
| **[INTEGRATED_USER_GUIDE.md](INTEGRATED_USER_GUIDE.md)** | Barangay staff | Gawad BIS ↔ BHC integration |
| **[WORKFLOW_DIAGRAM.md](WORKFLOW_DIAGRAM.md)** | All | Mermaid diagrams |
| **[SOURCE_CODE_AND_DOCUMENTATION.md](SOURCE_CODE_AND_DOCUMENTATION.md)** | Developers | Technical reference |

---

*Summary version: BHC System with staff/admin dashboards, Staff accounts, role redirects, Patient Routing, multi-station queues, QR tickets, display and ticket sound alerts, Queue Management, recall to queue, auto-call on Complete/Skip, Monthly Reports, and Activity Log.*
