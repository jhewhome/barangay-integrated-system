# BHC System - Mermaid Workflow Diagrams

Use these diagrams in **[Mermaid Live Editor](https://mermaid.live)** for a clean, interactive view and to **export PNG or SVG** (higher quality than a hand-drawn image).

---

## **How to generate a diagram (recommended)**

1. Open **[https://mermaid.live](https://mermaid.live)**
2. Copy the contents of one of the source files:
   - **Full visit workflow:** [`workflow-diagram.mmd`](workflow-diagram.mmd)
   - **Ticket statuses only:** [`ticket-lifecycle.mmd`](ticket-lifecycle.mmd)
3. Paste into the editor (left panel). The preview updates on the right.
4. Use **Actions -> PNG** or **SVG** to download.
5. Optional: **Actions -> Copy PNG** for slides or documents.

**In VS Code / Cursor:** Install the *Mermaid* extension, open a `.mmd` file, then use **Preview** or export from the preview.

**On GitHub:** Paste a ` ```mermaid ` block (see below) in any `.md` file; GitHub renders it automatically.

---

## **1. End-to-end visit workflow (main diagram)**

Best for training, proposals, and posters. Shows registry -> routing -> waiting -> station service -> optional return to routing.

```mermaid
---
title: Barangay Health Center System - End-to-End Workflow
config:
  theme: base
  themeVariables:
    fontSize: 15px
    fontFamily: Segoe UI, system-ui, sans-serif
    primaryColor: "#dbeafe"
    primaryBorderColor: "#2563eb"
    primaryTextColor: "#0f172a"
    secondaryColor: "#dcfce7"
    secondaryBorderColor: "#16a34a"
    secondaryTextColor: "#14532d"
    tertiaryColor: "#fef3c7"
    tertiaryBorderColor: "#d97706"
    tertiaryTextColor: "#78350f"
    lineColor: "#64748b"
    clusterBkg: "#f8fafc"
    clusterBorder: "#94a3b8"
    edgeLabelBackground: "#ffffff"
  flowchart:
    curve: basis
    padding: 16
    htmlLabels: true
    nodeSpacing: 45
    rankSpacing: 55
---

flowchart TB
    START([Patient arrives at health center]) --> REGCHK{Patient in registry?}

    subgraph REG["Phase 1 - Patient Registry"]
        direction TB
        REGCHK -->|No| ADD["Staff: Add patient<br/>Name, sex, DOB -> BHC ID"]
        REGCHK -->|Yes| FIND["Staff: Find patient<br/>Search / autocomplete"]
        ADD --> READY[(Patient record ready)]
        FIND --> READY
    end

    READY --> ROUTE

    subgraph ROUTE["Phase 2 - Patient Routing · Station 1"]
        direction TB
        R1["Staff: Open Patient Routing"]
        R2["Enter reason for visit"]
        R3["Assign station:<br/>Triage · Consultation · Pharmacy"]
        R4["Create ticket<br/>Status: waiting · e.g. TR-001"]
        R1 --> R2 --> R3 --> R4
    end

    R4 --> QR["Show ticket + QR to patient"]
    R4 --> WAIT

    subgraph WAIT["Phase 3 - Waiting"]
        direction LR
        W1["TV display<br/>Enable sound · chime on call"]
        W2["Phone ticket<br/>Enable sound · 5s refresh"]
        W3["Waiting area"]
    end

    QR --> WAIT
    WAIT --> SERVE

    subgraph SERVE["Phase 4 - Service Station · Staff-operated queue"]
        direction TB
        S0["Staff: Queue Stations or Queue Management"]
        S1{"Action"}
        S2["Call next / Call ticket"]
        S3["Now serving · status: serving"]
        S4["Complete -> done"]
        S5["Skip -> skipped"]
        S5b["Recall -> waiting<br/>same ticket #"]
        S6["Auto-call next waiting<br/>if station idle"]
        S0 --> S1
        S1 --> S2 --> S3
        S3 --> S4
        S3 --> S5
        S5 --> S5b
        S5b --> S2
        S4 --> S6
        S5 --> S6
        S6 -.->|more waiting| S2
    end

    S4 --> MORE{Another station needed?}
    S5 --> MORE
    MORE -->|Yes| ROUTE
    MORE -->|No| END([Visit complete])

    subgraph COORD["Optional - Queue Management"]
        direction TB
        C1["One dashboard: all service stations"]
        C2["Call · Complete · Skip · Recall · 5s refresh"]
        C1 --- C2
    end

    COORD -.->|same actions| SERVE

    subgraph ADMIN["Admin - oversight"]
        direction LR
        A1["Monthly Reports"]
        A2["Activity Log"]
    end

    classDef startEnd fill:#e0e7ff,stroke:#4f46e5,stroke-width:2px,color:#1e1b4b
    classDef reg fill:#dbeafe,stroke:#2563eb,color:#0f172a
    classDef route fill:#fef3c7,stroke:#d97706,color:#78350f
    classDef wait fill:#f3e8ff,stroke:#9333ea,color:#581c87
    classDef serve fill:#dcfce7,stroke:#16a34a,color:#14532d
    classDef coord fill:#f1f5f9,stroke:#64748b,color:#334155,stroke-dasharray:5 5

    class START,END startEnd
    class ADD,FIND,READY reg
    class R1,R2,R3,R4 route
    class W1,W2,W3 wait
    class S0,S1,S2,S3,S4,S5,S6 serve
    class C1,C2 coord
```

---

## **2. Ticket lifecycle (compact)**

Best for explaining queue statuses to staff.

```mermaid
---
title: BHC System - Queue Ticket Lifecycle
config:
  theme: base
  themeVariables:
    fontSize: 16px
    fontFamily: Segoe UI, system-ui, sans-serif
---

stateDiagram-v2
    direction LR

    [*] --> waiting: Registration creates ticket

    waiting --> serving: Call next / Call
    serving --> done: Complete
    serving --> skipped: Skip

    skipped --> waiting: Recall to queue

    done --> serving: Auto-call next waiting
    skipped --> serving: Auto-call next waiting

    done --> [*]
    skipped --> [*]

    note right of waiting
        Daily queue per station
        TR · CN · PH prefixes
    end note

    note right of serving
        Only one serving
        per station at a time
    end note
```

---

## **3. Swimlane view (staff vs patient)**

Simpler diagram for quick presentations.

```mermaid
%%{init: {'theme': 'base', 'themeVariables': {'fontSize': '15px', 'primaryColor': '#dbeafe', 'secondaryColor': '#dcfce7', 'tertiaryColor': '#fef3c7'}}}%%

flowchart LR
    subgraph STAFF["Staff (login required)"]
        direction TB
        A1[Register / find patient]
        A2[Route at Registration desk]
        A3[Call · Complete · Skip]
        A4[Queue Management optional]
        A1 --> A2 --> A3
        A4 -.-> A3
    end

    subgraph SYSTEM["BHC System"]
        direction TB
        B1[(Patient registry)]
        B2[(Queue tickets)]
        B3[Auto-call next]
        B1 --> B2 --> B3
    end

    subgraph PATIENT["Patient (no login)"]
        direction TB
        C1[Receive ticket + QR]
        C2[Watch TV display]
        C3[Check phone ticket page]
        C1 --> C2
        C1 --> C3
    end

    A2 --> B2
    B2 --> C1
    A3 --> B3
    B3 --> C2
    B3 --> C3
```

---

## **4. Replace the PNG in guides**

After exporting from Mermaid Live:

1. Save the PNG or SVG alongside these docs (e.g. `workflow-diagram.png` in this folder) **or** embed in your thesis/submission package.
2. Update any references in `USER_GUIDE.md` and `WORKFLOW_SUMMARY.md` if you use a custom filename.

The original hand-drawn PNG can stay as a backup; Mermaid exports are usually sharper for print and slides.

---

## **Files in this project**

| File | Purpose |
|------|---------|
| [`workflow-diagram.mmd`](workflow-diagram.mmd) | Full workflow - paste into mermaid.live |
| [`ticket-lifecycle.mmd`](ticket-lifecycle.mmd) | Ticket state machine |
| `WORKFLOW_DIAGRAM.md` (this file) | Diagrams + instructions |
| [`WORKFLOW_SUMMARY.md`](WORKFLOW_SUMMARY.md) | Written process summary |
| [`INTEGRATED_USER_GUIDE.md`](INTEGRATED_USER_GUIDE.md) | Gawad BIS ↔ BHC integration guide |
| [`SOURCE_CODE_AND_DOCUMENTATION.md`](SOURCE_CODE_AND_DOCUMENTATION.md) | Technical documentation |
| [`DEPLOY_INFINITYFREE.md`](DEPLOY_INFINITYFREE.md) | Deployment guide |
| [`README.md`](README.md) | Documentation index |

---

## **Tips for a better-looking export**

| Tip | How |
|-----|-----|
| Larger text | In mermaid.live, zoom preview before PNG export, or increase `fontSize` in the YAML header |
| Wider layout | Use `flowchart LR` instead of `TB` for left-to-right posters |
| Dark background | mermaid.live -> **Configuration** -> pick theme *dark* or *forest* |
| PDF for printing | Export **SVG**, open in browser or Inkscape -> Print to PDF |
| Embed in Word | Export **PNG** at 2× zoom for crisp print |

---

*Diagrams match BHC System: Patient Routing, Queue Management, auto-call on Complete/Skip, QR tickets, displays, Monthly Reports, Activity Log.*
