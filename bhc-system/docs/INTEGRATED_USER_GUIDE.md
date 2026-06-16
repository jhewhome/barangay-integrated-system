# BHC Integration — User Guide

**How the Barangay Health Center (BHC) connects to Gawad BIS**  
Barangay Balong Bato, San Juan City

> This guide covers **only the integration** added to link Gawad BIS with the BHC System. For day-to-day queue operations, displays, and clinical workflow inside BHC, see [USER_GUIDE.md](USER_GUIDE.md).

> **Exporting to PDF:** Open in Microsoft Word or use a Markdown-to-PDF extension. Save as UTF-8.

---

## 1. What this integration adds

Gawad BIS already manages barangay residents, permits, transactions, and **medicine inventory**. The BHC System handles clinic patients, queues, and clinical records.

The **BHC integration** connects the two so staff do not duplicate work:

| Integration feature | Where you see it | What it does |
|---------------------|------------------|--------------|
| **Health Center menu** | Gawad BIS sidebar | Jump to BHC pages (patients, queues, appointments) |
| **Single sign-on (SSO)** | Every Health Center link | Arrive in BHC already logged in |
| **Register at Health Center** | Gawad resident profile | Prefill BHC patient form from resident data |
| **Staff import** | BHC → Staff accounts | Create matching BHC logins from Gawad users |
| **Medicine catalog sync** | BHC consultation & pharmacy forms | Show live Gawad stock when prescribing |

**What stays separate (by design):**

| Data | Master system |
|------|---------------|
| Resident enrollment, barangay address records | **Gawad BIS** |
| Medicine stock, batches, receiving | **Gawad BIS** |
| BHC patient ID, visits, queues, clinical notes | **BHC** |

---

## 2. Before you start

### URLs (local development)

| System | URL |
|--------|-----|
| Gawad BIS | `http://localhost:5003` |
| BHC | `http://localhost/bhc_system/public` |

In production, use your barangay server addresses. Both systems must be running, and integration must be **enabled** on both sides (see Section 11 if links fail).

### Who needs accounts where

| Person | Gawad BIS | BHC |
|--------|-----------|-----|
| Barangay secretary / staff | Yes | Yes (after **Import Gawad staff**) |
| Health center doctor | Optional | Yes (role **Doctor**, created manually in BHC) |
| BHC admin | Yes (as Administrator) | Yes (role **Admin**) |

SSO matches accounts by **username**. Your Gawad username and BHC username must be the same.

---

## 3. Opening BHC from Gawad BIS

### Health Center sidebar

After integration is enabled, signed-in Gawad users see a **Health Center** section in the sidebar with links such as:

- Registration queue
- Triage, Consultation, Pharmacy queues
- Patients and appointments

Clicking a link opens BHC in a new tab (if configured) and signs you in automatically.

### How SSO works (what staff experience)

1. You are logged in to **Gawad BIS**.
2. You click a **Health Center** link or **Register at Health Center** on a resident.
3. Gawad sends you to BHC with a short-lived secure token.
4. BHC verifies the token and logs you in as the matching user.
5. You land on the intended BHC page (queue, patient form, etc.).

You do **not** need to type your BHC password when SSO succeeds.

### If SSO does not log you in

| Symptom | What to do |
|---------|------------|
| BHC shows the login page | Your username may not exist in BHC yet — ask an admin to run **Import Gawad staff** (Section 8) |
| Error about invalid token | Sign in to Gawad again and retry; token may have expired |
| Integration disabled | Contact IT — `BhcIntegration` / `gawad_integration` must be enabled |

You can always sign in to BHC manually with the same username once your account exists.

---

## 4. Registering a patient — standard path (resident sync)

This is the **required path for health center staff** (non-admin). Residents must exist in Gawad BIS first.

### Step 1 — Enroll or open the resident in Gawad BIS

1. Sign in to **Gawad BIS**.
2. Go to **Residents**.
3. Enroll a new resident **or** open an existing **resident profile**.

Resident master data (legal name, address, barangay) lives in Gawad. Do not skip this step for routine clinic registration.

### Step 2 — Register at Health Center

1. On the resident profile, click **Register at Health Center**.
2. Your browser opens BHC (via SSO) on **Register New Patient**.
3. A green banner shows: *Importing from Gawad BIS* with the resident name.

### Step 3 — Review prefilled data

BHC pulls resident data from Gawad and prefills:

- First, middle, and last name; suffix
- Sex and date of birth
- Address and barangay
- Civil status (when available)

Hidden fields store the **Gawad resident ID** so the records stay linked after save.

### Step 4 — Confirm and save

1. **Confirm Balong Bato residency** (required checkbox on the form).
2. Add optional clinic fields (contact, PhilHealth, emergency contact, notes).
3. Watch for **possible duplicate** suggestions as you type (Section 5).
4. Click **Save patient record**.

The system assigns a **BHC ID** (e.g. `BHC-000042`) and saves `registration_source = gawad_bis`.

### Step 5 — Continue in BHC

After save, route the patient at the **Registration queue** (Health Center → Registration). See [USER_GUIDE.md](USER_GUIDE.md) for queue steps.

### Flow diagram

```
Gawad BIS                    BHC System
─────────                    ──────────
Residents
  └─ Profile
       └─ [Register at Health Center]
              │
              ├─ SSO login ──────────► Register New Patient (prefilled)
              │                              │
              │                              ├─ Confirm residency
              │                              └─ Save → BHC ID + Gawad link
              │
              └─ (resident data API) ◄────── fetch resident details
```

### If the resident is already registered in BHC

When you use **Register at Health Center** for a resident already linked, BHC redirects you to the **existing patient record** instead of creating a duplicate.

---

## 5. Duplicate detection while registering

While typing first and last name on **Register New Patient**, BHC searches existing patients and shows **possible duplicates** under the name fields.

| Situation | Action |
|-----------|--------|
| Suggestion matches the person | Click it to **use the existing record** and go to Registration routing |
| Same person, walk-in created earlier | Use **Link Gawad resident to this record** (Section 6) instead of saving a new patient |
| Different person with similar name | Continue registration; verify identity carefully |

Press **Escape** or click outside the list to close suggestions.

---

## 6. Linking Gawad to an existing BHC patient

Use this when a patient was registered earlier (e.g. **emergency walk-in**) and you later enroll them in Gawad—or when **Register at Health Center** finds a name match.

### When the link prompt appears

1. Start **Register at Health Center** from the Gawad resident profile (SSO opens BHC).
2. BHC detects an existing patient with matching identity.
3. A warning card shows the existing **BHC ID** and name.

### Actions on the warning card

| Button | Use when |
|--------|----------|
| **Link Gawad resident to this record** | Same person; walk-in was not linked yet |
| **Open existing record** | Review history before deciding |
| **Route at Registration** | Patient is already in BHC; send to a queue |

After linking, the patient file stores the Gawad resident ID. Future imports for that resident go directly to the same record.

---

## 7. Emergency walk-in (exception only)

Staff **cannot** open a blank **Add patient** form. The integration enforces: normal registration goes through Gawad.

**Emergency walk-in** is the approved exception when the patient needs **immediate** care and cannot be registered in Gawad first.

### How to start

1. BHC → **Patients**.
2. Click **Emergency walk-in** (not *Register via Gawad BIS*).
3. Enter a **required reason** (at least 10 characters), e.g. unconscious patient, no ID, no time for BIS enrollment.
4. Complete the form and confirm residency.
5. Save.

The reason is stored on the patient file (`registration_source = emergency_walk_in`).

### After the emergency

When the person is enrolled in Gawad, open their resident profile → **Register at Health Center**. If BHC finds the walk-in record, use **Link Gawad resident to this record** (Section 6).

### Admin direct registration

BHC **admins** may also use **Add patient** without a Gawad link. This is for exceptional cases; routine residents should still go through Gawad.

---

## 8. Staff accounts and Import from Gawad BIS

SSO requires the same **username** in both systems. New barangay staff are created in **Gawad first**, then imported into BHC.

### One-time setup (BHC admin)

1. Sign in to BHC as **admin**.
2. Go to **Staff accounts** (`/users`).
3. In **Import from Gawad BIS**, enter a **temporary password** (min. 8 characters) and confirm.
4. Click **Import Gawad staff**.

### What import does

| Gawad user | BHC result |
|------------|------------|
| New username (not in BHC) | New account created |
| **Administrator** role | BHC role **admin** |
| Secretary, Kagawad, Staff, etc. | BHC role **staff** |
| Username already in BHC | Skipped (not overwritten) |

Tell new staff to sign in with their **Gawad username** and the temporary password, then change it under **Account → Password**.

### Doctors

**Doctor** accounts are **not** imported from Gawad. An admin must create them manually in BHC with role **Doctor**. SSO still works if the doctor's username matches a Gawad account.

### Ongoing maintenance

| Task | Where |
|------|-------|
| Add person in Gawad only | Gawad → User management |
| Pull new Gawad users into BHC | BHC → Staff accounts → **Import Gawad staff** |
| Reset BHC password | BHC → Staff accounts → **Reset password** |
| Remove access | **Deactivate** in BHC (Gawad account unchanged) |

---

## 9. Medicine catalog sync (Gawad inventory → BHC pickers)

Medicine **inventory** is managed entirely in **Gawad BIS → Medicines** (add items, receive stock, set minimum levels, dispense in barangay context).

BHC reads that catalog **live** when staff prescribe or dispense at the clinic.

### Where staff see Gawad medicines in BHC

| Screen | Behavior |
|--------|----------|
| Consultation (queue or doctor chart) | Medicine picker lists Gawad items with stock hints |
| Pharmacy dispensing | Same catalog; low-stock and out-of-stock warnings |
| Admin → **Medicine list** | Shows whether pickers use Gawad or local fallback |

When sync is active, the picker shows:

- Medicine name and unit from Gawad
- **Current stock** (read-only)
- **Low stock** / **Out of stock** warnings
- Confirmation if quantity exceeds available stock

Staff choose **Clinic stock** when dispensing from barangay inventory recorded in Gawad.

### What staff do in Gawad vs BHC

| Task | System |
|------|--------|
| Add medicine, receive stock, adjust inventory | **Gawad BIS → Medicines** |
| Check barangay-wide stock reports | **Gawad BIS → Medicines** |
| Prescribe for a clinic visit | **BHC** (picker shows Gawad stock) |
| Record what was given to the patient | **BHC** (pharmacy queue / consultation) |
| Update stock after clinic dispensing | **Gawad BIS** (manual — BHC does not reduce Gawad stock automatically) |

### If the Gawad catalog does not appear in BHC

BHC falls back to the local **Medicine list** (admin-maintained names only, no live stock). An admin message may show *Gawad sync unavailable*. Check Section 11.

### Open Gawad inventory from BHC

On **Medicine list** (admin), use the link to **Open Gawad BIS Medicines** to manage stock in the master system.

---

## 10. Registration rules summary

| User | How to register a new clinic patient |
|------|--------------------------------------|
| **Staff** | Gawad resident → **Register at Health Center**, **or** **Emergency walk-in** |
| **Admin** | Same as staff, **or** **Add patient** directly (discouraged for residents already in Gawad) |

| Staff action on Patients page | Purpose |
|-------------------------------|---------|
| **Register via Gawad BIS** | Opens Gawad Residents (start standard path) |
| **Emergency walk-in** | Exception path with documented reason |

Staff who open `/patients/create` without a Gawad link or emergency flag are redirected with instructions to use Gawad.

---

## 11. Integration troubleshooting

| Problem | Likely cause | Fix |
|---------|--------------|-----|
| No **Health Center** menu in Gawad | Integration disabled | Enable `BhcIntegration:Enabled` in Gawad `appsettings.json` |
| No **Register at Health Center** button | Resident sync off | Enable `ResidentSyncEnabled` in Gawad |
| Button opens BHC but form is empty | API key mismatch or Gawad API down | Match `IntegrationApiKey` (Gawad) and `integration_api_key` (BHC); ensure Gawad is running |
| SSO lands on login page | No BHC account for that username | Admin runs **Import Gawad staff** |
| SSO error / invalid token | Expired token or wrong `SsoSecret` | Secrets must match on both sides; retry from Gawad |
| Staff blocked from Add patient | By design | Use **Register via Gawad BIS** or **Emergency walk-in** |
| Medicines missing in picker | Medicine sync off or API error | Enable `MedicineSyncEnabled` (Gawad) and `medicine_sync_enabled` (BHC) |
| Stock always shows zero | No stock in Gawad | Add/receive stock in **Gawad → Medicines** |
| Duplicate patients | Ignored name suggestions | Use suggestions; link Gawad to existing record |

For BHC-only issues (queues, displays, QR codes), see [USER_GUIDE.md](USER_GUIDE.md).

---

## 12. Quick reference — integration tasks

| I want to… | Do this |
|------------|---------|
| Register a resident at the clinic | Gawad → Resident profile → **Register at Health Center** |
| Open BHC queues from Gawad | Gawad sidebar → **Health Center** |
| Add new barangay staff to BHC | BHC admin → Staff accounts → **Import Gawad staff** |
| Register urgent patient without Gawad | BHC → Patients → **Emergency walk-in** |
| Link walk-in to Gawad resident | Gawad → **Register at Health Center** → **Link Gawad resident to this record** |
| Manage medicine stock | **Gawad BIS → Medicines** |
| Prescribe with stock awareness | **BHC** consultation/pharmacy (Gawad catalog) |
| Avoid duplicate patients | Use name suggestions on registration form |

---

## 13. Related documents

| Document | Content |
|----------|---------|
| [README.md](README.md) | Documentation index |
| [SOURCE_CODE_AND_DOCUMENTATION.md](SOURCE_CODE_AND_DOCUMENTATION.md) | APIs, configuration, architecture (technical) |
| [USER_GUIDE.md](USER_GUIDE.md) | BHC queues, displays, reports, daily operations |
| [WORKFLOW_SUMMARY.md](WORKFLOW_SUMMARY.md) | End-to-end clinic process overview |
| [WORKFLOW_DIAGRAM.md](WORKFLOW_DIAGRAM.md) | Visual workflow diagrams |
| [DEPLOY_INFINITYFREE.md](DEPLOY_INFINITYFREE.md) | Cloud deployment guide |

---

*BHC Integration User Guide — Gawad BIS ↔ BHC: SSO, resident registration handoff, staff import, medicine catalog sync. Barangay Balong Bato.*
