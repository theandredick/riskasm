# Smart Risk Assessment — Project Plan

**Project**: Smart Risk Assessment
**Brand family**: Smart Business
**Host**: SiteGround GrowBig (shared hosting)
**Plan Date**: 2026-04-07
**Last Updated**: 2026-04-07 — schema updated after visual reference analysis; assessment table column model and multi-category consequence descriptions added
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

### Key Design Decisions (from visual reference analysis)

Two important additions were made to the schema after reviewing reference images of real-world risk assessments:

1. **Multi-category consequence descriptions**: In professional risk matrices, a single severity level (e.g. "4") carries descriptions across *multiple consequence categories simultaneously* — Safety, Environmental Impact, Asset Damage, Business Interruption, etc. Two new tables (`matrix_consequence_categories` and `matrix_level_category_descriptions`) support this. The severity level label/dropdown remains simple, but the full consequence detail is available as a hover/reference panel.

2. **Flexible assessment table columns**: Four distinct assessment table layouts exist in practice. All possible columns are stored in the `assessment_rows` table (all nullable). The active column set per assessment is stored as JSONB in `assessments.column_config`. Phase 1 uses four pre-set templates; Phase 2 adds free-form column selection.

### Assessment Template Types

Four pre-set layouts derived from reference templates:

| Template Key | Columns Included |
|---|---|
| `simple` | ID, Activity/Condition, Hazard, Effect, Existing Controls (Desc+Type), Current Risk, Accept, Comments |
| `simple_natural` | Adds Natural Risk (Sev/Lik/Risk + Accept) before Existing Controls |
| `detailed` | Simple + Proposed Controls (Desc+Type), Residual Risk, Residual Accept |
| `detailed_natural` | All columns — Natural Risk, Existing Controls, Current Risk, Proposed Controls, Residual Risk |

### Entity Relationship Summary

```
users ──< assessments ──< assessment_rows
  │              │
  │              └──> risk_matrices ──< matrix_consequence_categories
  │                         │                    │
  │                         ├──< matrix_levels   └──< matrix_level_category_descriptions
  │                         └──< matrix_cells
  │
  ├──< hazard_library
  ├──< control_library
  └──< assessment_shares
```

### PostgreSQL Enum Types (defined before tables)

```sql
CREATE TYPE user_role          AS ENUM ('admin', 'manager', 'assessor', 'viewer');
CREATE TYPE matrix_axis        AS ENUM ('severity', 'likelihood');
CREATE TYPE assessment_status  AS ENUM ('draft', 'in_review', 'approved', 'archived');
CREATE TYPE share_permission   AS ENUM ('view', 'edit');
CREATE TYPE assessment_template AS ENUM ('simple', 'simple_natural', 'detailed', 'detailed_natural');
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
| `name` | TEXT NOT NULL | e.g. "ISO 31010 5×5", "Oil & Gas 6×6" |
| `description` | TEXT | |
| `severity_axis_label` | TEXT NOT NULL DEFAULT 'Severity' | e.g. "Severity", "Consequence" |
| `likelihood_axis_label` | TEXT NOT NULL DEFAULT 'Likelihood' | e.g. "Likelihood", "Probability" |
| `is_public` | BOOLEAN NOT NULL DEFAULT FALSE | Visible to all users |
| `is_system` | BOOLEAN NOT NULL DEFAULT FALSE | Built-in, not editable |
| `created_at` | TIMESTAMPTZ NOT NULL DEFAULT NOW() | |
| `updated_at` | TIMESTAMPTZ NOT NULL DEFAULT NOW() | |

#### `matrix_consequence_categories`

**New table.** Defines the named consequence/severity dimensions for a matrix. Each severity level can carry a description in every category. This supports multi-column severity reference tables (e.g. Safety, Environmental Impact, Asset Damage, Business Interruption, Public Image, Public Notification).

| Column | Type | Notes |
|---|---|---|
| `id` | SERIAL PRIMARY KEY | |
| `matrix_id` | INTEGER NOT NULL REFERENCES risk_matrices(id) ON DELETE CASCADE | |
| `name` | TEXT NOT NULL | e.g. "Safety", "Environmental Impact (Remediation)", "Asset Damage" |
| `sort_order` | SMALLINT NOT NULL DEFAULT 0 | Left-to-right display order |

#### `matrix_levels`

Labels and ordering for each axis value within a matrix. The `description` field here is a brief single-line label; multi-category severity descriptions live in `matrix_level_category_descriptions`.

| Column | Type | Notes |
|---|---|---|
| `id` | SERIAL PRIMARY KEY | |
| `matrix_id` | INTEGER NOT NULL REFERENCES risk_matrices(id) ON DELETE CASCADE | |
| `axis` | matrix_axis NOT NULL | |
| `level_value` | SMALLINT NOT NULL | Numeric value (1, 2, 3…) |
| `label` | TEXT NOT NULL | Short label: "Catastrophic", "Almost Certain", "Frequent" |
| `one_word` | TEXT | Optional single-word descriptor: "Probable", "Rare" |
| `quantitative_range` | TEXT | Optional: "> 10⁻¹", "10⁻¹ – 10⁻³" (for likelihood levels) |
| `description` | TEXT | One-line description for the dropdown tooltip |
| `sort_order` | SMALLINT NOT NULL DEFAULT 0 | |

#### `matrix_level_category_descriptions`

**New table.** Stores the detailed description for each (severity level, consequence category) pair. This powers the multi-column consequence table shown on the matrix reference panel.

| Column | Type | Notes |
|---|---|---|
| `id` | SERIAL PRIMARY KEY | |
| `matrix_id` | INTEGER NOT NULL REFERENCES risk_matrices(id) ON DELETE CASCADE | |
| `severity_level_value` | SMALLINT NOT NULL | References the severity level_value in matrix_levels |
| `category_id` | INTEGER NOT NULL REFERENCES matrix_consequence_categories(id) ON DELETE CASCADE | |
| `description` | TEXT NOT NULL | e.g. "Fatality, Public Hospitalization or Severe Health Effects" |
| UNIQUE | (matrix_id, severity_level_value, category_id) | One description per cell |

#### `matrix_cells`

Maps each severity/likelihood pair to a risk category and display colour.

| Column | Type | Notes |
|---|---|---|
| `id` | SERIAL PRIMARY KEY | |
| `matrix_id` | INTEGER NOT NULL REFERENCES risk_matrices(id) ON DELETE CASCADE | |
| `severity_value` | SMALLINT NOT NULL | |
| `likelihood_value` | SMALLINT NOT NULL | |
| `risk_category` | TEXT NOT NULL | e.g. "High", "Medium", "Low", "Extreme" |
| `risk_band_label` | TEXT | Roman numeral or band label: "IV", "III", "II", "I" |
| `colour_hex` | CHAR(7) NOT NULL | e.g. `#FF0000` |
| `numeric_score` | SMALLINT | severity × likelihood, or custom override |
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
| `template_type` | assessment_template NOT NULL DEFAULT 'simple' | Pre-set column layout (Phase 1) |
| `column_config` | JSONB | Per-assessment column overrides; used in Phase 2 free-form customisation |
| `copied_from_id` | INTEGER REFERENCES assessments(id) | NULL = original |
| `created_at` | TIMESTAMPTZ NOT NULL DEFAULT NOW() | |
| `updated_at` | TIMESTAMPTZ NOT NULL DEFAULT NOW() | |
| `approved_at` | TIMESTAMPTZ | |
| `approved_by_id` | INTEGER REFERENCES users(id) | |

**Default `column_config` shape (JSONB):**

```json
{
  "show_activity_condition": true,
  "show_natural_risk": false,
  "show_control_type": true,
  "show_accept_yn": true,
  "show_proposed_controls": false,
  "show_residual_risk": false
}
```

The `template_type` enum sets sensible defaults; `column_config` allows per-assessment overrides in Phase 2.

#### `assessment_rows`

One row per hazard being analysed. All optional columns are nullable — active columns are controlled by the parent assessment's `column_config`. Risk level fields are denormalised from `matrix_cells` to preserve values even if the matrix is later changed.

| Column | Type | Notes |
|---|---|---|
| `id` | SERIAL PRIMARY KEY | |
| `assessment_id` | INTEGER NOT NULL REFERENCES assessments(id) ON DELETE CASCADE | |
| `sort_order` | SMALLINT NOT NULL DEFAULT 0 | Drag-reorderable |
| `activity_condition` | TEXT | "Activity or Condition" column |
| `hazard` | TEXT | Hazard description |
| `effect` | TEXT | Effect / impact (w/ exposure) |
| **Natural risk (optional)** | | Only used when `show_natural_risk = true` |
| `natural_severity_value` | SMALLINT | Severity before any controls |
| `natural_likelihood_value` | SMALLINT | Likelihood before any controls |
| `natural_risk_category` | TEXT | Denormalised from matrix_cells |
| `natural_colour_hex` | CHAR(7) | Denormalised |
| `natural_risk_accept` | BOOLEAN | Accept Y/N for natural risk |
| **Existing controls** | | |
| `existing_controls_description` | TEXT | Description of controls already in place |
| `existing_controls_type` | TEXT | Control type/classification (e.g. "Engineering", "Admin", "PPE") |
| **Current risk** | | Risk after existing controls |
| `severity_value` | SMALLINT | |
| `likelihood_value` | SMALLINT | |
| `risk_category` | TEXT | Denormalised |
| `colour_hex` | CHAR(7) | Denormalised |
| `current_risk_accept` | BOOLEAN | Accept Y/N for current risk |
| **Proposed controls (optional)** | | Only used in "detailed" templates |
| `proposed_controls_description` | TEXT | Recommended additional controls |
| `proposed_controls_type` | TEXT | Control type classification |
| **Residual risk (optional)** | | Risk after proposed controls |
| `residual_severity_value` | SMALLINT | |
| `residual_likelihood_value` | SMALLINT | |
| `residual_risk_category` | TEXT | Denormalised |
| `residual_colour_hex` | CHAR(7) | Denormalised |
| `residual_risk_accept` | BOOLEAN | Accept Y/N for residual risk |
| **Misc** | | |
| `comments` | TEXT | Additional controls/comments catch-all |
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
| `control_type` | TEXT | Default type suggestion (e.g. "Engineering", "PPE") |
| `tags` | TEXT | |
| `use_count` | INTEGER NOT NULL DEFAULT 0 | |
| `created_at` | TIMESTAMPTZ NOT NULL DEFAULT NOW() | |

#### `assessment_shares`

| Column | Type | Notes |
|---|---|---|
| `id` | SERIAL PRIMARY KEY | |
| `assessment_id` | INTEGER NOT NULL REFERENCES assessments(id) ON DELETE CASCADE | |
| `shared_with_user_id` | INTEGER REFERENCES users(id) | NULL = public/link share |
| `share_token` | TEXT UNIQUE | 64-char cryptographically random token |
| `permission` | share_permission NOT NULL DEFAULT 'view' | |
| `created_at` | TIMESTAMPTZ NOT NULL DEFAULT NOW() | |
| `expires_at` | TIMESTAMPTZ | NULL = no expiry |

#### `audit_log`

| Column | Type | Notes |
|---|---|---|
| `id` | BIGSERIAL PRIMARY KEY | |
| `user_id` | INTEGER REFERENCES users(id) | NULL = system action |
| `assessment_id` | INTEGER REFERENCES assessments(id) ON DELETE SET NULL | |
| `action` | TEXT NOT NULL | e.g. "row_added", "status_changed", "shared" |
| `detail` | TEXT | JSON blob with before/after values |
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

- **10 built-in matrices** seeded on first deployment, all read-only:
  - Simple 3×3 (everyday use)
  - Standard 4×4 (business-level)
  - Detailed 5×5 (AS/NZS ISO 31000 style)
  - **ISO 31010** 5×5 (Insignificant→Catastrophic / Rare→Almost Certain)
  - **Oil & Gas Shell/BP** 6×6 (Negligible→Catastrophic / Remote→Continuous)
  - **FAA / ICAO** 5×5 (Negligible→Catastrophic / Improbable→Frequent)
  - **NORSOK Z-013** 5×5 (A→E / Very Rare→Frequent)
  - **HSE UK Offshore** 5×5 (Negligible→Catastrophic / Remote→Frequent)
  - **NFPA Fire** 3×5 (Minor→Catastrophic / Rare→Frequent)
  - **U.S. Army** 5×4 ordinal (Negligible→Catastrophic / Unlikely→Frequent)
- Each matrix displayed as an interactive colour-coded grid in the UI
- The severity reference panel shows all consequence categories (Safety, Environmental, Asset Damage, etc.) where defined — available as a collapsible reference table and on hover in the severity dropdown
- System templates are read-only; users can clone any template to customise it

#### 5.3 Assessment CRUD

- Create a new assessment: choose matrix template, choose **assessment template type** (Simple / Simple+Natural / Detailed / Detailed+Natural), fill header metadata
- The chosen template type pre-sets which columns are active (controls the `column_config` JSONB)
- Add/edit/delete hazard rows inline in the assessment table
- Drag-to-reorder rows
- Severity + Likelihood dropdowns auto-fill Risk Level cell live with colour from matrix
- All row columns (natural risk, existing controls type, accept Y/N, proposed controls, residual risk) are stored in the DB and shown/hidden based on the template type
- **Auto-save**: localStorage-first — changes persist to localStorage immediately; sync to server on manual save, page unload, or visibility change
- Assessment status workflow: **Draft → In Review → Approved → Archived**
- Duplicate/copy an existing assessment (preserves template type and column config)

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
- For each severity level: add named consequence categories and write descriptions (e.g. Safety, Environmental, Financial) — these populate the matrix reference panel
- Likelihood levels: add label, one-word descriptor, and optional quantitative frequency range
- Live grid preview updates as you build
- Save as personal or public template
- Clone any existing matrix (including system ones) to customise

#### 5.6b Custom Assessment Column Layout

- Per-assessment toggle UI: turn individual columns on/off freely (not just the 4 pre-set types)
- Reorder columns via drag-handle
- Save a custom layout as a named personal template for reuse in future assessments
- Phase 1 uses the 4 pre-set `template_type` options; Phase 2 replaces this with free-form column selection that still writes to the same `column_config` JSONB field

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
┌─ Assessment Header (collapsible) ────────────────────────────────────┐
│  Title | Ref # | Assessor | Location | Review Date | Status          │
│  Matrix: [5×5 AS/NZS ▼]    Template: [Detailed ▼]   [Matrix Ref ↗] │
└──────────────────────────────────────────────────────────────────────┘

┌─ Toolbar ──────────────────────────────────────────────────────────┐
│  [↑ Sync to Server]  [⬇ Export PDF]  [⬇ Export Excel]  [⬇ CSV]   │
│  [Share]  [Copy Assessment]  [Change Status ▼]                     │
│  Status: ● Unsaved changes — click Sync to save                    │
└────────────────────────────────────────────────────────────────────┘

┌─ Hazard Analysis Table (horizontally scrollable) ──────────────────┐
│ Columns shown depend on template type:                             │
│                                                                    │
│ SIMPLE:                                                            │
│  ≡ # │ Act/Cond │ Hazard │ Effect │ Controls (Desc│Type) │         │
│                           Sev │ Lik │ RISK │ Accept │ Comments    │
│                                                                    │
│ DETAILED:                                                          │
│  ≡ # │ Act/Cond │ Hazard │ Effect │ Nat.Risk │ Controls (Desc│Type)│
│       Cur.Risk │ Accept │ Prop.Controls (Desc│Type) │ Res.Risk     │
│       Res.Accept │ Comments                                        │
└────────────────────────────────────────────────────────────────────┘
   [+ Add Row]
```

- Columns shown are driven by the assessment's `column_config` / `template_type`
- Each row is a single expandable table row — on narrow screens, less-used columns collapse
- The Risk Level cell (current, natural, and residual) is a full-colour block matching the matrix, showing the category text in a contrasting colour
- **Accept Y/N** column renders as a large checkbox or toggle — visual at a glance
- Severity/Likelihood dropdowns: hovering the dropdown shows a tooltip with that level's consequence category descriptions (e.g. "Safety: Fatality… / Environmental: >$10MM…")
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
│   │   ├── 001_create_types.sql                    ← PostgreSQL ENUM type definitions
│   │   ├── 002_create_users.sql
│   │   ├── 003_create_risk_matrices.sql
│   │   ├── 004_create_matrix_consequence_categories.sql  ← NEW: multi-category severity
│   │   ├── 005_create_matrix_levels.sql
│   │   ├── 006_create_matrix_level_category_descriptions.sql ← NEW: per-category descriptions
│   │   ├── 007_create_matrix_cells.sql
│   │   ├── 008_create_assessments.sql              ← includes template_type + column_config
│   │   ├── 009_create_assessment_rows.sql           ← full column set (all nullable)
│   │   ├── 010_create_hazard_library.sql
│   │   ├── 011_create_control_library.sql
│   │   ├── 012_create_assessment_shares.sql
│   │   └── 013_create_audit_log.sql
│   └── seeds/
│       ├── system_matrices.sql         ← 3×3, 4×4, 5×5 + 7 industry standards (ISO 31010,
│       │                                  Oil&Gas Shell/BP, FAA/ICAO, NORSOK Z-013,
│       │                                  HSE UK Offshore, NFPA Fire, U.S. Army)
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
| M3 | **Database Migrations & Seeds** | All 13 migration files; 10 system matrices seeded (3 generic + 7 industry standards) with full levels, consequence categories, category descriptions, and colour cells |
| M4 | **Assessment CRUD** | Create (with template type selection), list, view, delete, copy assessments; header editing |
| M5 | **Assessment Editor** | Full column-aware hazard row table: add, edit, delete, drag-reorder; live risk level colouring; Accept Y/N toggles; localStorage auto-save with sync button |
| M6 | **PDF + CSV Export** | mPDF server-side PDF with colour cells, all visible columns, and Smart Risk Assessment branding; CSV download |

**Phase 1 complete = a fully usable, deployable MVP.**

### Phase 2 — Power Features (Target: 3–4 weeks)

| # | Milestone | Deliverables |
|---|---|---|
| M7 | **Custom Matrix Builder** | Visual builder UI with consequence category editor; save/edit/delete/clone matrices |
| M8 | **Free-form Column Layout** | Per-assessment column toggle UI; save layout as personal template |
| M9 | **Hazard & Control Library** | Library CRUD with control_type field; auto-complete in editor rows; "save to library" shortcut |
| M10 | **Sharing** | Share with user; public token link with expiry; revoke; public view page |
| M11 | **Excel Export** | PhpSpreadsheet `.xlsx` with colour-coded cells and header, respects active columns |

### Phase 3 — Polish & Reporting (Target: 2–3 weeks)

| # | Milestone | Deliverables |
|---|---|---|
| M12 | **Reporting** | Risk register view; per-assessment stats; matrix distribution heat map |
| M13 | **Notifications & Audit** | Email on share; review date reminder (cron); audit trail table; admin audit viewer |
| M14 | **Admin Panel** | User list, enable/disable, role management |
| M15 | **Branded Exports** | Logo upload per user; applied to PDF and Excel |

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
| 7 | **Multiple consequence categories per severity level** | **Add to schema now** (`matrix_consequence_categories` + `matrix_level_category_descriptions` tables) | Severity levels in real risk work carry descriptions across multiple dimensions (Safety, Environmental, Financial, etc.). Schema change is cheap pre-code; expensive post-code. UI for display is Phase 1; UI for editing is Phase 2. |
| 8 | **Flexible assessment table columns** | **Full column set in schema now; pre-set template types in Phase 1 UI; free-form column picker in Phase 2** | Four real-world table layouts identified (Simple, Simple+Natural, Detailed, Detailed+Natural). All columns stored nullable in `assessment_rows`; active set controlled by `column_config` JSONB. Phase 1 offers 4 presets; Phase 2 adds free-form toggles. |
| 9 | **Industry-standard matrix seeds** | **Seed all 10 system matrices** (3 generic + 7 industry: ISO 31010, Oil & Gas Shell/BP, FAA/ICAO, NORSOK Z-013, HSE UK Offshore, NFPA Fire, U.S. Army) | Saves users from building common matrices from scratch; demonstrates the system's breadth immediately. |

---

*Plan created: 2026-04-07*
*Schema updated: 2026-04-07 — multi-category consequence descriptions and flexible column model added*
*Next step: Begin Phase 1, Milestone M1 — project scaffold.*
