# Smart Risk Assessment — Project Plan

**Project**: Smart Risk Assessment
**Brand family**: Smart Business
**Host**: SiteGround GrowBig (shared hosting)
**Plan Date**: 2026-04-07
**Last Updated**: 2026-04-07 — all open questions resolved; plan is ready for coding
**Status**: Approved for development

---

## Table of Contents

1. [Project Vision & Goals](#1-project-vision--goals)
2. [Technology Stack Decisions](#2-technology-stack-decisions)
3. [Architecture Overview](#3-architecture-overview)
4. [Database Design](#4-database-design)
5. [Feature Breakdown](#5-feature-breakdown)
6. [Page & Route Map](#6-page--route-map)
7. [UI/UX Design Direction](#7-uiux-design-direction)
8. [Folder & File Structure](#8-folder--file-structure)
9. [Development Phases & Milestones](#9-development-phases--milestones)
10. [Security Considerations](#10-security-considerations)
11. [Deployment Strategy](#11-deployment-strategy)
12. [Future Enhancements (AI)](#12-future-enhancements-ai)
13. [Decisions Log](#13-decisions-log)

---

## 1. Project Vision & Goals

### Problem Statement

Risk practitioners need a consistent, reusable, shareable place to perform structured risk assessments. Existing tools (spreadsheets, Word docs) are fragmented, hard to search, offer no auto-complete or reuse workflow, and produce inconsistent results across an organisation.

### Solution

A professional multi-user web application — **Smart Risk Assessment** — that:

- Provides standard and fully custom risk matrices (3×3, 4×4, 5×5, and user-defined dimensions)
- Guides assessors through structured hazard-by-hazard analysis: hazard → effect/impact → existing controls → severity → likelihood → risk level → additional controls → residual risk → comments
- Stores a searchable library of reusable hazards, effects, and controls with auto-complete
- Allows assessments to be copied, reused, exported to PDF and Excel, and shared with other users
- Is visually polished, colour-coded, and table-driven — matching the established paper/spreadsheet workflow users already know
- Is publicly accessible via SiteGround hosting as part of the **Smart Business** brand family
- Will eventually use AI to suggest hazards, risk levels, and controls

### Success Criteria

- Any user can create a complete risk assessment in under 10 minutes
- Risk matrix colours auto-populate every row live as severity and likelihood are set
- Assessments can be exported to fully colour-coded PDF and Excel with one click
- Multiple users can own separate assessment libraries but share public templates
- The UI works well on both desktop and tablet
- The app is fast and usable even on a spotty internet connection

---

## 2. Technology Stack Decisions

### Backend — PHP 8.2

**Why PHP**: SiteGround GrowBig's primary server-side language with native support, zero configuration required, and excellent shared-hosting compatibility. Python via WSGI on SiteGround requires extra configuration; PHP is the right call for this host.

- **Style**: Lightweight custom MVC — no heavy framework (no Laravel/Symfony). Keeps the app portable and straightforward for shared hosting.
- **Routing**: Single `index.php` front controller; clean URL routing via `.htaccess` rewrite rules
- **Templating**: Plain PHP templates — no Twig/Blade, keeps dependencies minimal
- **Sessions**: PHP native sessions for authentication state
- **Email**: PHPMailer via SMTP — configured to use **MXroute** credentials stored in `.env`
- **PDF export**: **mPDF** (server-side, full colour fidelity) — installed via Composer
- **Excel export**: **PhpSpreadsheet** — installed via Composer, produces colour-coded `.xlsx` files

### Database — PostgreSQL

**Confirmed**: SiteGround GrowBig includes PostgreSQL support. The project already has two database users configured (admin + test). All schema migrations will use PostgreSQL syntax.

PostgreSQL-specific choices:
- Primary keys: `SERIAL` or `BIGSERIAL`
- Booleans: `BOOLEAN` (not `TINYINT(1)`)
- Timestamps: `TIMESTAMPTZ` (timezone-aware)
- Text fields: `TEXT` (PostgreSQL's `TEXT` is fully indexed and has no length penalty)
- Enums: PostgreSQL `CREATE TYPE` enums for status fields
- PHP PDO with `pgsql` driver

### Frontend — Vanilla JavaScript + Bulma CSS

- **CSS**: Bulma (project standard for new CSS)
- **JavaScript**: Vanilla JS, ES2022+ modules — no frameworks
- **Icons**: Font Awesome 6 Free (CDN)
- **Assessment table editor**: Rich in-page JS component with inline editing, drag-to-reorder rows, and live risk-level colour preview
- **Auto-save strategy**: **localStorage-first** — changes are written to localStorage immediately; server sync happens on demand (manual save button or page-unload trigger). Status indicator shows: "All changes saved" / "Unsaved changes — click to sync" / "Syncing…" / "Sync failed — working offline"
- **Auto-complete**: Custom lightweight JS widget backed by a `/api/` JSON endpoint

---

## 3. Architecture Overview

```
Browser (Bulma + Vanilla JS)
         │
         │  HTTPS
         ▼
 SiteGround Web Server (Apache)
         │
         │  .htaccess rewrites all requests to:
         ▼
  public/index.php  (Front Controller)
         │
         ├──► Router  →  Controller  →  Model  →  PostgreSQL DB
         │
         └──► View (PHP template)
                  │
                  └──► Response (HTML page / JSON / file download)
```

### Request Types

| Type | Flow | Purpose |
|---|---|---|
| **Page request** | Browser → PHP → Template → HTML | Full page loads, navigation |
| **API request** | Browser JS → PHP → JSON | Row CRUD, auto-complete, matrix lookup |
| **Export request** | Browser → PHP → mPDF / PhpSpreadsheet → file download | PDF and Excel exports |
| **Sync request** | JS flushes localStorage → POST /api/sync → PHP → DB | localStorage-to-server save |

### Auto-Save Data Flow

```
User edits a cell
       │
       ▼
JS writes change to localStorage (key: assessment_{id})
       │
       ▼
Status indicator: "Unsaved changes"
       │
       ├── User clicks "Sync" button
       │       │
       │       ▼
       │   POST /api/assessments/{id}/sync
       │   Body: full row array from localStorage
       │       │
       │       ▼
       │   PHP upserts rows in DB
       │       │
       │       ▼
       │   Status: "All changes saved"
       │
       └── Page unload / visibility change → auto-trigger sync
```

### Session & Auth Flow

```
Login form → POST /auth/login
    → PHP validates credentials (bcrypt password check)
    → Sets $_SESSION['user_id'] + $_SESSION['role']
    → session_regenerate_id(true)
    → Redirects to dashboard

All protected routes: AuthMiddleware checks $_SESSION['user_id']
    → Not set? Redirect to /auth/login?return={url}
```

---

## 4. Database Design

All tables use PostgreSQL syntax. Primary keys are `SERIAL`. Timestamps are `TIMESTAMPTZ` defaulting to `NOW()`.

### Entity Relationship Summary

```
users ──< assessments ──< assessment_rows
  │              │
  │              └──> risk_matrices
  │
  ├──< risk_matrices ──< matrix_levels
  │              └──< matrix_cells
  │
  ├──< hazard_library
  ├──< control_library
  └──< assessment_shares
```

### PostgreSQL Enum Types (defined before tables)

```sql
CREATE TYPE user_role   AS ENUM ('admin', 'manager', 'assessor', 'viewer');
CREATE TYPE matrix_axis AS ENUM ('severity', 'likelihood');
CREATE TYPE assessment_status AS ENUM ('draft', 'in_review', 'approved', 'archived');
CREATE TYPE share_permission  AS ENUM ('view', 'edit');
```

### Table Definitions

#### `users`

| Column | Type | Notes |
|---|---|---|
| `id` | SERIAL PRIMARY KEY | |
| `email` | TEXT UNIQUE NOT NULL | Login credential |
| `display_name` | TEXT NOT NULL | Shown in UI |
| `password_hash` | TEXT NOT NULL | bcrypt via `password_hash()` |
| `role` | user_role NOT NULL DEFAULT 'assessor' | |
| `is_active` | BOOLEAN NOT NULL DEFAULT TRUE | Soft disable |
| `created_at` | TIMESTAMPTZ NOT NULL DEFAULT NOW() | |
| `updated_at` | TIMESTAMPTZ NOT NULL DEFAULT NOW() | |
| `last_login_at` | TIMESTAMPTZ | |

#### `risk_matrices`

Defines a risk rating scale — e.g., a 5×5 with severity and likelihood axes.

| Column | Type | Notes |
|---|---|---|
| `id` | SERIAL PRIMARY KEY | |
| `owner_id` | INTEGER REFERENCES users(id) | NULL = system template |
| `name` | TEXT NOT NULL | |
| `description` | TEXT | |
| `severity_axis_label` | TEXT NOT NULL DEFAULT 'Severity' | e.g. "Severity", "Consequence" |
| `likelihood_axis_label` | TEXT NOT NULL DEFAULT 'Likelihood' | e.g. "Likelihood", "Probability" |
| `is_public` | BOOLEAN NOT NULL DEFAULT FALSE | Visible to all users |
| `is_system` | BOOLEAN NOT NULL DEFAULT FALSE | Built-in, not editable |
| `created_at` | TIMESTAMPTZ NOT NULL DEFAULT NOW() | |
| `updated_at` | TIMESTAMPTZ NOT NULL DEFAULT NOW() | |

#### `matrix_levels`

Labels and ordering for each axis value within a matrix.

| Column | Type | Notes |
|---|---|---|
| `id` | SERIAL PRIMARY KEY | |
| `matrix_id` | INTEGER NOT NULL REFERENCES risk_matrices(id) ON DELETE CASCADE | |
| `axis` | matrix_axis NOT NULL | |
| `level_value` | SMALLINT NOT NULL | Numeric value (1, 2, 3…) |
| `label` | TEXT NOT NULL | e.g. "Catastrophic", "Almost Certain" |
| `description` | TEXT | Optional clarification for users |
| `sort_order` | SMALLINT NOT NULL DEFAULT 0 | |

#### `matrix_cells`

Maps each severity/likelihood pair to a risk category and display colour.

| Column | Type | Notes |
|---|---|---|
| `id` | SERIAL PRIMARY KEY | |
| `matrix_id` | INTEGER NOT NULL REFERENCES risk_matrices(id) ON DELETE CASCADE | |
| `severity_value` | SMALLINT NOT NULL | |
| `likelihood_value` | SMALLINT NOT NULL | |
| `risk_category` | TEXT NOT NULL | e.g. "High", "Medium", "Low", "Extreme" |
| `colour_hex` | CHAR(7) NOT NULL | e.g. `#FF0000` |
| `numeric_score` | SMALLINT | severity × likelihood or custom |
| UNIQUE | (matrix_id, severity_value, likelihood_value) | One cell per combination |

#### `assessments`

| Column | Type | Notes |
|---|---|---|
| `id` | SERIAL PRIMARY KEY | |
| `owner_id` | INTEGER NOT NULL REFERENCES users(id) | |
| `matrix_id` | INTEGER NOT NULL REFERENCES risk_matrices(id) | The matrix template in use |
| `title` | TEXT NOT NULL | |
| `description` | TEXT | Project/activity context |
| `reference_number` | TEXT | e.g. "RA-2026-001" |
| `location` | TEXT | |
| `assessor_name` | TEXT | Free text (can differ from account name) |
| `review_date` | DATE | When the next review is due |
| `status` | assessment_status NOT NULL DEFAULT 'draft' | |
| `copied_from_id` | INTEGER REFERENCES assessments(id) | NULL = original |
| `created_at` | TIMESTAMPTZ NOT NULL DEFAULT NOW() | |
| `updated_at` | TIMESTAMPTZ NOT NULL DEFAULT NOW() | |
| `approved_at` | TIMESTAMPTZ | |
| `approved_by_id` | INTEGER REFERENCES users(id) | |

#### `assessment_rows`

One row per hazard being analysed. Risk level fields are denormalised from `matrix_cells` for performance and to preserve the value even if the matrix is later changed.

| Column | Type | Notes |
|---|---|---|
| `id` | SERIAL PRIMARY KEY | |
| `assessment_id` | INTEGER NOT NULL REFERENCES assessments(id) ON DELETE CASCADE | |
| `sort_order` | SMALLINT NOT NULL DEFAULT 0 | Drag-reorderable |
| `hazard` | TEXT | Hazard description |
| `effect` | TEXT | Effect / impact |
| `existing_controls` | TEXT | Controls already in place |
| `severity_value` | SMALLINT | |
| `likelihood_value` | SMALLINT | |
| `risk_category` | TEXT | Denormalised from matrix_cells |
| `colour_hex` | CHAR(7) | Denormalised from matrix_cells |
| `additional_controls` | TEXT | Recommended further controls |
| `residual_severity_value` | SMALLINT | After additional controls |
| `residual_likelihood_value` | SMALLINT | After additional controls |
| `residual_risk_category` | TEXT | Denormalised |
| `residual_colour_hex` | CHAR(7) | Denormalised |
| `comments` | TEXT | |
| `created_at` | TIMESTAMPTZ NOT NULL DEFAULT NOW() | |
| `updated_at` | TIMESTAMPTZ NOT NULL DEFAULT NOW() | |

#### `hazard_library`

Reusable hazard descriptions with optional suggested effect text.

| Column | Type | Notes |
|---|---|---|
| `id` | SERIAL PRIMARY KEY | |
| `owner_id` | INTEGER REFERENCES users(id) | NULL = global |
| `hazard_text` | TEXT NOT NULL | |
| `effect_text` | TEXT | Suggested default effect text |
| `tags` | TEXT | Comma-separated for simple filtering |
| `use_count` | INTEGER NOT NULL DEFAULT 0 | Popularity ordering |
| `created_at` | TIMESTAMPTZ NOT NULL DEFAULT NOW() | |

#### `control_library`

| Column | Type | Notes |
|---|---|---|
| `id` | SERIAL PRIMARY KEY | |
| `owner_id` | INTEGER REFERENCES users(id) | NULL = global |
| `control_text` | TEXT NOT NULL | |
| `tags` | TEXT | |
| `use_count` | INTEGER NOT NULL DEFAULT 0 | |
| `created_at` | TIMESTAMPTZ NOT NULL DEFAULT NOW() | |

#### `assessment_shares`

| Column | Type | Notes |
|---|---|---|
| `id` | SERIAL PRIMARY KEY | |
| `assessment_id` | INTEGER NOT NULL REFERENCES assessments(id) ON DELETE CASCADE | |
| `shared_with_user_id` | INTEGER REFERENCES users(id) | NULL = public/link share |
| `share_token` | TEXT UNIQUE | 64-char cryptographically random token (for link sharing) |
| `permission` | share_permission NOT NULL DEFAULT 'view' | |
| `created_at` | TIMESTAMPTZ NOT NULL DEFAULT NOW() | |
| `expires_at` | TIMESTAMPTZ | NULL = no expiry |

#### `audit_log`

Tracks who changed what and when on assessments. Used in Phase 3.

| Column | Type | Notes |
|---|---|---|
| `id` | BIGSERIAL PRIMARY KEY | |
| `user_id` | INTEGER REFERENCES users(id) | NULL = system action |
| `assessment_id` | INTEGER REFERENCES assessments(id) ON DELETE SET NULL | |
| `action` | TEXT NOT NULL | e.g. "row_added", "status_changed", "shared" |
| `detail` | TEXT | JSON blob with changed fields/values |
| `created_at` | TIMESTAMPTZ NOT NULL DEFAULT NOW() | |

---

## 5. Feature Breakdown

### Phase 1 — Foundation (MVP)

These features must be complete before the app is usable.

#### 5.1 User Authentication

- User registration: email + display name + password
- Login / logout with secure session management
- "Remember me" cookie (30-day sliding expiry)
- Password reset via email link (MXroute SMTP via PHPMailer)
- Admin can create and disable/enable users
- Role-based access control: **admin**, **manager**, **assessor**, **viewer**

#### 5.2 System Risk Matrix Templates

- 3 built-in matrices, seeded on first deployment:
  - **Simple 3×3**: Low / Medium / High, everyday use
  - **Standard 4×4**: business-level risk assessments
  - **Detailed 5×5**: AS/NZS ISO 31000 style with Catastrophic/Major/Moderate/Minor/Insignificant
- Each matrix displayed as an interactive colour-coded grid in the UI
- System templates are read-only; users can clone any system template to customise it

#### 5.3 Assessment CRUD

- Create a new assessment: choose matrix template, fill header metadata
- Add/edit/delete hazard rows inline in the assessment table
- Drag-to-reorder rows (HTML5 drag API or SortableJS lightweight library)
- Severity + Likelihood dropdowns auto-fill Risk Level cell live with colour from matrix
- Residual risk section per row (mirrored severity/likelihood dropdowns)
- **Auto-save**: localStorage-first approach — changes persist to localStorage immediately; sync to server on manual save, on page unload, or on visibility change
- Assessment status workflow: **Draft → In Review → Approved → Archived**
- Duplicate/copy an existing assessment (creates a new Draft with identical rows)

#### 5.4 Assessment List / Dashboard

- Dashboard showing: My Recent Assessments, Shared With Me, Quick Stats
- Sortable/filterable table: title, date created, status, matrix type, highest risk level
- Search by title, reference number, or hazard text (full-text PostgreSQL search)

#### 5.5 Export — Phase 1

- **PDF**: Server-side mPDF export with full colour-coded risk cells, assessment header, table, and Smart Risk Assessment branding
- **CSV**: Simple server-side export for data portability

### Phase 2 — Power Features

#### 5.6 Custom Risk Matrix Builder

- Visual builder: choose dimensions (2×2 up to 6×6), label each axis level, assign risk category + colour to each cell
- Live preview updates as you build
- Save as personal or public template
- Clone any existing matrix to customise

#### 5.7 Hazard & Control Library

- Personal + global library of hazards, effects, and controls
- Auto-complete (type-ahead) in all assessment row text fields
- When selecting a hazard from the library, optionally pre-fill effect and suggested existing controls
- "Save to library" shortcut when editing a row in an assessment
- Library management page: browse, search by tag, edit, delete entries

#### 5.8 Sharing & Collaboration

- Share a specific assessment with another registered user (view or edit permission)
- Generate a public read-only share link with optional expiry date
- "Shared With Me" section on dashboard
- Assessment owner can revoke any share at any time

#### 5.9 Excel Export

- Server-side `.xlsx` generation via **PhpSpreadsheet**
- Colour-coded risk level cells match the matrix colours exactly
- Assessment header section above the table
- Smart Risk Assessment branding in the export header

### Phase 3 — Polish & Reporting

#### 5.10 Reporting & Analytics

- Per-assessment summary: count of Extreme/High/Medium/Low risks before and after controls
- User-level report: all assessments by status, date range
- Risk register view: all open Extreme/High risks across all the user's assessments in one table
- Visual matrix heat map: bubble/count overlay showing distribution of rows across the risk grid

#### 5.11 Notifications & Audit Trail

- Email notification (MXroute/PHPMailer) when an assessment is shared with you
- Email reminder 2 weeks before a review date is due (SiteGround cron job)
- Audit trail per assessment: who made what change and when (using `audit_log` table)
- Admin audit log viewer

#### 5.12 Enhanced Branding on Exports

- Custom logo upload per user (stored outside web root)
- Logo and brand colours applied to PDF and Excel exports

### Phase 4 — AI Integration (Future)

See Section 12.

---

## 6. Page & Route Map

```
GET  /                              → Dashboard (redirect to /auth/login if unauthenticated)
GET  /auth/login                    → Login form
POST /auth/login                    → Process login
GET  /auth/logout                   → Destroy session, redirect to login
GET  /auth/register                 → Registration form
POST /auth/register                 → Process registration
GET  /auth/forgot-password          → Forgot password form
POST /auth/forgot-password          → Send reset email via MXroute SMTP
GET  /auth/reset-password/{token}   → Reset password form
POST /auth/reset-password/{token}   → Process password reset

GET  /assessments                   → My assessments list
GET  /assessments/new               → New assessment form (choose matrix template)
POST /assessments/new               → Create assessment, redirect to editor
GET  /assessments/{id}              → Assessment editor (main working page)
POST /assessments/{id}              → Update assessment header metadata
POST /assessments/{id}/delete       → Soft-delete assessment
POST /assessments/{id}/copy         → Duplicate assessment
POST /assessments/{id}/status       → Change status (draft/in_review/approved/archived)
GET  /assessments/{id}/export/pdf   → Download mPDF-generated PDF
GET  /assessments/{id}/export/xlsx  → Download PhpSpreadsheet Excel file
GET  /assessments/{id}/export/csv   → Download CSV
GET  /assessments/{id}/share        → Manage sharing for this assessment
POST /assessments/{id}/share        → Create a new share (user or public link)
POST /assessments/{id}/share/{sid}/revoke → Revoke a share

GET  /shared/{token}                → Public read-only assessment view (no login required)

--- JSON API Endpoints ---
GET    /api/assessments/{id}/rows           → Fetch all rows for an assessment
POST   /api/assessments/{id}/rows           → Add a new blank row
PUT    /api/assessments/{id}/rows/{rowId}   → Update a single row field
DELETE /api/assessments/{id}/rows/{rowId}   → Delete a row
POST   /api/assessments/{id}/rows/reorder   → Batch update sort_order after drag
POST   /api/assessments/{id}/sync           → Flush full localStorage state to DB

GET  /api/matrices/{id}                     → Full matrix data (levels + cells as JSON)
GET  /api/matrices/{id}/cell?sev=X&lik=Y    → Return risk_category + colour_hex for a cell

GET  /api/library/hazards?q=term            → Type-ahead hazard search (returns JSON)
GET  /api/library/controls?q=term           → Type-ahead control search
POST /api/library/hazards                   → Save new hazard to library
POST /api/library/controls                  → Save new control to library

--- Matrices ---
GET  /matrices                  → Matrix template library (system + user's own + public)
GET  /matrices/new              → Create custom matrix
POST /matrices/new              → Save new custom matrix
GET  /matrices/{id}             → View matrix (full grid display)
GET  /matrices/{id}/edit        → Edit matrix (owner or admin only)
POST /matrices/{id}/edit        → Save changes
POST /matrices/{id}/delete      → Delete matrix
POST /matrices/{id}/copy        → Clone matrix to current user

--- Library ---
GET  /library                       → Hazard & control library browser
GET  /library/hazards               → Hazard list with search and tag filter
GET  /library/controls              → Control list with search and tag filter
POST /library/hazards/{id}/edit     → Edit a hazard entry
POST /library/hazards/{id}/delete   → Delete a hazard entry
POST /library/controls/{id}/edit    → Edit a control entry
POST /library/controls/{id}/delete  → Delete a control entry

--- Admin ---
GET  /admin                         → Admin overview + quick stats
GET  /admin/users                   → User management list
POST /admin/users/{id}/toggle       → Enable or disable a user account
POST /admin/users/{id}/role         → Change a user's role
GET  /admin/audit                   → Audit log viewer
```

---

## 7. UI/UX Design Direction

### Brand Context

**Smart Risk Assessment** is part of the **Smart Business** umbrella brand. The visual identity should be:

- Professional and trustworthy — this is a safety/compliance tool
- Clean and uncluttered — the risk data (colour-coded matrix cells) is the star
- Consistent with the Smart Business brand family (same type, same neutral chrome)
- Distinct from generic SaaS — avoid blue-heavy "tech startup" aesthetic; prefer a more authoritative, document-like feel

### Visual Style

| Element | Decision |
|---|---|
| **CSS framework** | Bulma |
| **Chrome colours** | Near-white background, dark grey navbar, subtle borders — neutral to let risk colours dominate |
| **Risk colours** | Standard traffic-light palette: Extreme = deep red, High = orange-red, Medium = amber/yellow, Low = green. These are the app's primary colour story. |
| **Typography** | System font stack (`system-ui, -apple-system, sans-serif`) — no external font dependency |
| **Icons** | Font Awesome 6 Free |
| **Tone** | Structured, efficient, no marketing language inside the app |

### Key Screen Designs

#### Dashboard

- Top row: Quick Stats cards (Total Assessments, Open Drafts, Shared With Me, Overdue Reviews)
- Main area: "My Recent Assessments" table with status badges
- Prominent "New Assessment" primary button in the navbar and hero area
- Status badges: `Draft` (grey), `In Review` (blue), `Approved` (green), `Archived` (dark grey)

#### Assessment Editor (Core Screen — most important)

```
┌─ Assessment Header (collapsible) ────────────────────────────────┐
│  Title | Ref # | Assessor | Location | Review Date | Status      │
│  Matrix: [5×5 AS/NZS ▼]    [Matrix Reference ↗]                 │
└──────────────────────────────────────────────────────────────────┘

┌─ Toolbar ────────────────────────────────────────────────────────┐
│  [↑ Sync to Server]  [⬇ Export PDF]  [⬇ Export Excel]           │
│  [Share]  [Copy Assessment]  [Change Status ▼]                   │
│  Status: ● Unsaved changes                                       │
└──────────────────────────────────────────────────────────────────┘

┌─ Hazard Analysis Table (horizontally scrollable) ────────────────┐
│ ≡ # │ Hazard │ Effect │ Existing Controls │ Sev │ Lik │ RISK     │
│     │        │        │ Additional Controls│ Sev │ Lik │ RESIDUAL │
│     │        │        │ Comments          │     │     │ [×]      │
└──────────────────────────────────────────────────────────────────┘
   [+ Add Row]
```

- Each row has two sub-rows: initial risk (top) and residual risk (bottom)
- The Risk Level cell is a full-colour block (matching the matrix), showing the category text in white
- Inline editing: click any cell to edit in-place — no modal dialogs
- Drag handle (`≡`) on the left for row reordering
- Risk Level cell updates in real time as severity/likelihood dropdowns change
- Auto-complete appears below text fields as a dropdown when typing

#### Matrix Builder

- Left panel: configuration (matrix name, axis labels, add/remove levels, label each level)
- Right panel: live interactive grid — click any cell to set its category name and colour via a colour picker

#### Mini Matrix Reference Panel

- Collapsible side panel on the assessment editor showing the full matrix grid at small scale
- Hovering a row in the table highlights the corresponding matrix cell
- Provides a constant visual reference without leaving the page

### Responsive Behaviour

| Breakpoint | Behaviour |
|---|---|
| Desktop 1200px+ | Full wide table, side panels visible |
| Tablet 768–1199px | Table scrolls horizontally, panels collapse to toggleable drawers |
| Mobile < 768px | Assessment list, dashboard, and view pages are mobile-friendly. The editor table is desktop-first; show a friendly banner: "For the best editing experience, use a desktop or laptop." |

---

## 8. Folder & File Structure

```
riskasm/
│
├── public/                             ← Web root (SiteGround document root points here)
│   ├── index.php                       ← Front controller / router entry point
│   ├── .htaccess                       ← Apache URL rewrite rules
│   └── assets/
│       ├── css/
│       │   ├── app.css                 ← App-specific Bulma overrides + table styles
│       │   └── print.css              ← Print layout (used by mPDF template)
│       ├── js/
│       │   ├── assessment-editor.js    ← Assessment table: inline editing, drag-reorder, live risk colouring
│       │   ├── local-storage-sync.js   ← localStorage save/restore + sync-to-server logic
│       │   ├── matrix-builder.js       ← Custom matrix builder interactive UI
│       │   ├── autocomplete.js         ← Reusable type-ahead widget (hazards + controls)
│       │   └── app.js                  ← Global utilities: flash messages, CSRF headers, helpers
│       └── img/
│           ├── logo.svg                ← Smart Risk Assessment logo
│           └── smart-business-logo.svg ← Parent brand logo
│
├── src/                                ← PHP application source (not web-accessible)
│   │
│   ├── Config/
│   │   ├── config.php                  ← App settings loaded from .env
│   │   └── routes.php                  ← Route definitions (method + path → controller@method)
│   │
│   ├── Core/
│   │   ├── Router.php                  ← URL pattern matching and dispatch
│   │   ├── Request.php                 ← HTTP request wrapper (method, path, body, files)
│   │   ├── Response.php                ← Response helpers (redirect, json, download)
│   │   ├── Session.php                 ← Session abstraction (start, get, set, flash, destroy)
│   │   ├── Database.php                ← PDO/pgsql wrapper with prepared statement helpers
│   │   └── Mailer.php                  ← PHPMailer wrapper configured for MXroute SMTP
│   │
│   ├── Middleware/
│   │   ├── AuthMiddleware.php          ← Require authenticated session; redirect if not
│   │   └── RoleMiddleware.php          ← Require specific role level
│   │
│   ├── Controllers/
│   │   ├── AuthController.php          ← Login, logout, register, password reset
│   │   ├── DashboardController.php     ← Dashboard page
│   │   ├── AssessmentController.php    ← Assessment CRUD, status, copy
│   │   ├── AssessmentRowApiController.php ← JSON API for row add/edit/delete/reorder/sync
│   │   ├── MatrixController.php        ← Matrix CRUD, builder, clone
│   │   ├── MatrixApiController.php     ← JSON API for matrix data and cell lookups
│   │   ├── LibraryController.php       ← Library browser pages
│   │   ├── LibraryApiController.php    ← JSON API for hazard/control auto-complete
│   │   ├── ExportController.php        ← PDF, Excel, CSV generation
│   │   ├── ShareController.php         ← Share management and public view
│   │   └── AdminController.php         ← User management, audit log
│   │
│   ├── Models/
│   │   ├── User.php
│   │   ├── Assessment.php
│   │   ├── AssessmentRow.php
│   │   ├── RiskMatrix.php
│   │   ├── MatrixLevel.php
│   │   ├── MatrixCell.php
│   │   ├── HazardLibrary.php
│   │   ├── ControlLibrary.php
│   │   ├── AssessmentShare.php
│   │   └── AuditLog.php
│   │
│   └── Helpers/
│       ├── Csrf.php                    ← CSRF token generation and validation
│       ├── Validator.php               ← Input validation helper
│       ├── Paginator.php               ← Simple offset/limit pagination
│       └── DateHelper.php              ← Date formatting and review date helpers
│
├── templates/                          ← PHP view templates
│   ├── layout/
│   │   ├── base.php                    ← Master HTML shell (head, navbar, flash, footer)
│   │   ├── navbar.php
│   │   └── flash.php                   ← Flash message banner component
│   ├── auth/
│   │   ├── login.php
│   │   ├── register.php
│   │   └── forgot-password.php
│   ├── dashboard/
│   │   └── index.php
│   ├── assessments/
│   │   ├── index.php                   ← Assessment list with search/filter
│   │   ├── new.php                     ← New assessment wizard (choose matrix)
│   │   ├── editor.php                  ← Core assessment editor (main screen)
│   │   ├── share.php                   ← Share management page
│   │   └── shared-view.php             ← Public read-only view (no login)
│   ├── matrices/
│   │   ├── index.php                   ← Matrix library
│   │   ├── view.php                    ← Matrix grid display
│   │   └── builder.php                 ← Custom matrix builder
│   ├── library/
│   │   └── index.php                   ← Hazard + control library browser
│   ├── admin/
│   │   ├── index.php
│   │   └── users.php
│   └── exports/
│       └── pdf-template.php            ← mPDF template (assessment table + branding)
│
├── database/
│   ├── migrations/
│   │   ├── 001_create_types.sql        ← PostgreSQL ENUM type definitions
│   │   ├── 002_create_users.sql
│   │   ├── 003_create_risk_matrices.sql
│   │   ├── 004_create_matrix_levels.sql
│   │   ├── 005_create_matrix_cells.sql
│   │   ├── 006_create_assessments.sql
│   │   ├── 007_create_assessment_rows.sql
│   │   ├── 008_create_hazard_library.sql
│   │   ├── 009_create_control_library.sql
│   │   ├── 010_create_assessment_shares.sql
│   │   └── 011_create_audit_log.sql
│   └── seeds/
│       ├── system_matrices.sql         ← 3×3, 4×4, 5×5 built-in matrices with colour cells
│       └── sample_hazard_library.sql   ← Starter global hazard/control entries
│
├── vendor/                             ← Composer dependencies (gitignored)
├── composer.json                       ← PHP dependencies (mPDF, PhpSpreadsheet, PHPMailer)
│
├── plans/
│   └── project-plan.md                 ← This document
│
├── .env                                ← Local secrets (gitignored)
├── .env.example                        ← Template with all required keys, no values
├── .gitignore
├── readme.md
└── change-log.md
```

---

## 9. Development Phases & Milestones

### Phase 1 — Foundation / MVP (Target: 4–5 weeks)

| # | Milestone | Deliverables |
|---|---|---|
| M1 | **Project Scaffold** | Folder structure, `composer.json`, Router, PDO/pgsql wrapper, `.htaccess`, base Bulma layout, `.env` loading, CSRF helper |
| M2 | **Auth System** | Login, register, logout, password reset (PHPMailer + MXroute), sessions, role middleware |
| M3 | **Database Migrations & Seeds** | All 11 migration files; 3 system matrices seeded with correct levels, cells, and colours |
| M4 | **Assessment CRUD** | Create, list, view, delete, copy assessments; header editing form |
| M5 | **Assessment Editor** | Full hazard row table: add, edit, delete, drag-reorder; live risk level colouring; localStorage auto-save with sync button |
| M6 | **PDF + CSV Export** | mPDF server-side PDF with colour cells; CSV download |

**Phase 1 complete = a fully usable, deployable MVP.**

### Phase 2 — Power Features (Target: 3–4 weeks)

| # | Milestone | Deliverables |
|---|---|---|
| M7 | **Custom Matrix Builder** | Visual builder UI; save/edit/delete/clone matrices |
| M8 | **Hazard & Control Library** | Library CRUD; auto-complete type-ahead in editor rows; "save to library" shortcut |
| M9 | **Sharing** | Share with user; public token link with expiry; revoke; public view page |
| M10 | **Excel Export** | PhpSpreadsheet `.xlsx` with colour-coded cells and header |

### Phase 3 — Polish & Reporting (Target: 2–3 weeks)

| # | Milestone | Deliverables |
|---|---|---|
| M11 | **Reporting** | Risk register view; per-assessment stats; matrix distribution heat map |
| M12 | **Notifications & Audit** | Email on share; review date reminder (cron); audit trail table; admin audit viewer |
| M13 | **Admin Panel** | User list, enable/disable, role management |
| M14 | **Branded Exports** | Logo upload per user; applied to PDF and Excel |

### Phase 4 — AI Integration (Future, no timeline)

See Section 12.

---

## 10. Security Considerations

| Risk | Mitigation |
|---|---|
| SQL injection | PDO with prepared statements everywhere; no raw string interpolation in SQL |
| XSS | `htmlspecialchars()` / `htmlentities()` on all output; Content-Security-Policy header |
| CSRF | CSRF token validated on every state-changing form and API POST/PUT/DELETE |
| Auth bypass | AuthMiddleware runs before every protected controller action |
| Insecure direct object reference (IDOR) | Every assessment/matrix/row access checks `owner_id = session user` or valid share |
| Password security | `password_hash()` with `PASSWORD_BCRYPT`; `password_verify()` for checks |
| Session fixation | `session_regenerate_id(true)` immediately on successful login |
| Sensitive config | All credentials in `.env`, never committed to git; `.env.example` has keys but no values |
| File uploads (Phase 3) | Strict MIME + extension validation; uploaded logos stored outside web root |
| Login brute force | Failed attempt counter per email in session/DB; lock after 5 attempts; show CAPTCHA |
| Share link enumeration | `random_bytes(32)` → `bin2hex()` = 64-char token, computationally infeasible to brute force |
| localStorage data exposure | LocalStorage data is user-scoped and contains no other user's data; cleared on logout |

---

## 11. Deployment Strategy

### Local Development Environment

- PHP 8.2 via MAMP, Laravel Herd, or `php -S localhost:10000 -t public/`
- PostgreSQL 16 locally, schema matches production
- `.env` with local DB credentials (never committed)
- Composer for PHP dependencies

### SiteGround GrowBig Setup

- PostgreSQL database already provisioned on SiteGround with **admin user** and **test user**
- Set document root to the `public/` subdirectory in SiteGround Site Tools → Domain Manager
- PHP version: set to 8.2 in SiteGround's PHP Manager
- Deploy via SiteGround Git integration (push to remote triggers deploy) or SFTP
- Run migrations manually via SSH (`psql`) on first deploy and after each migration addition
- Configure `.env` on the server with production values (`APP_ENV=production`, `APP_DEBUG=false`, DB credentials, MXroute SMTP credentials)
- Cron job (SiteGround's cron manager): daily run of `php /path/to/public/index.php cron:review-reminders`

### MXroute Email Configuration (.env keys)

```
MAIL_HOST=<mxroute smtp host>
MAIL_PORT=587
MAIL_USERNAME=<mxroute account>
MAIL_PASSWORD=<mxroute password>
MAIL_FROM_ADDRESS=noreply@smartrisk.app
MAIL_FROM_NAME="Smart Risk Assessment"
MAIL_ENCRYPTION=tls
```

### Git Workflow

- `main` branch = production-ready code
- `develop` branch = integration branch
- Feature branches per milestone (e.g. `feature/m5-editor`, `feature/m8-library`)
- Deploy to production: merge `develop` → `main`, pull on server

### Composer Dependencies (planned)

| Package | Purpose |
|---|---|
| `mpdf/mpdf` | Server-side PDF generation |
| `phpoffice/phpspreadsheet` | Excel `.xlsx` export |
| `phpmailer/phpmailer` | Email sending via MXroute SMTP |
| `vlucas/phpdotenv` | `.env` file loading |

---

## 12. Future Enhancements (AI)

The readme specifically calls out AI as a planned direction. This is a Phase 4 scope item, planned but not yet scheduled.

### AI Feature Concepts

| Feature | Description |
|---|---|
| **Hazard suggestion** | Given an activity or task description, suggest likely hazards from an LLM prompt |
| **Control suggestion** | Given a hazard + effect, suggest common industry-appropriate controls |
| **Risk level validation** | Flag rows where the assigned risk level seems inconsistent with the hazard description |
| **Assessment completeness review** | AI scans a completed assessment and highlights missing or weak entries |
| **Library enrichment** | Periodically suggest new entries for the global hazard/control library |

### AI Implementation Approach (when ready)

- Server-side PHP calls to OpenAI API (GPT-4o or equivalent) — credentials in `.env`, never exposed to browser
- Suggestions displayed as dismissable inline prompts — the user accepts, edits, or ignores them
- No AI-generated content is ever saved automatically; always requires explicit user action
- Rate limit AI calls per user per day to control API costs
- Implement in Phase 4 after the core product is stable and in active use

---

## 13. Decisions Log

All open questions from initial planning have been resolved. This table documents the final decisions.

| # | Question | Decision | Rationale |
|---|---|---|---|
| 1 | **Database** | **PostgreSQL** on SiteGround GrowBig | SiteGround GrowBig includes PostgreSQL; already provisioned with admin + test users. Matches project standard. |
| 2 | **Auto-save strategy** | **localStorage-first, sync on demand** | Better performance and resilience on spotty internet. Changes persist locally immediately; user controls when to push to server. |
| 3 | **PDF export** | **Server-side mPDF** | Full colour fidelity, consistent output, Smart Business branding. jsPDF client-side was rejected in favour of high-quality server-side generation. |
| 4 | **Multi-tenancy** | **Individual users only** | Simpler schema and access control. No organisation layer. Global shared library + public matrix templates serve the sharing need. |
| 5 | **Email provider** | **MXroute via PHPMailer SMTP** | Project owner already has MXroute account. PHPMailer is the standard PHP email library. |
| 6 | **App name / branding** | **"Smart Risk Assessment"** under **"Smart Business"** umbrella | Establishes consistent brand family. PDF/Excel exports and page titles will use Smart Risk Assessment branding. |

---

*Plan created: 2026-04-07*
*All decisions finalised: 2026-04-07*
*Next step: Begin Phase 1, Milestone M1 — project scaffold.*
