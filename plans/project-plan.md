# Risk Assessment Manager — Project Plan

**Project**: Risk Assessment Manager ("RiskASM")
**Host**: SiteGround (shared/cloud hosting)
**Plan Date**: 2026-04-07
**Status**: Pre-development — planning only

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
13. [Open Questions / Decisions Needed](#13-open-questions--decisions-needed)

---

## 1. Project Vision & Goals

### Problem Statement

Risk practitioners need a consistent, reusable, shareable place to perform structured risk assessments. Existing tools (spreadsheets, Word docs) are fragmented, hard to search, and offer no auto-complete or reuse workflow.

### Solution

A professional multi-user web application that:

- Provides standard and fully custom risk matrices (3×3, 4×4, 5×5, and user-defined)
- Guides assessors through structured hazard-by-hazard analysis (hazard → effect → existing controls → severity → likelihood → risk level → additional controls → residual risk → comments)
- Stores a library of reusable hazards, effects, and controls with auto-complete
- Allows assessments to be copied, reused, exported, and shared
- Is visually polished, colour-coded, and table-driven — matching the established paper/spreadsheet workflow users already know
- Is publicly accessible via SiteGround hosting

### Success Criteria

- Any user can create a complete risk assessment in under 10 minutes
- Risk matrix colours auto-populate every row based on chosen severity/likelihood
- Assessments can be exported to PDF and Excel with one click
- Multiple users can own separate assessment libraries but share templates
- The UI works well on both desktop and tablet

---

## 2. Technology Stack Decisions

### Backend — PHP 8.2

**Why PHP**: SiteGround's primary server-side language with native support, zero configuration required, and excellent shared-hosting compatibility. Python via WSGI on SiteGround requires extra configuration and restrictions; PHP is the right call for this host.

- Framework style: Lightweight custom MVC (no heavy framework like Laravel — keeps it portable and simple for SiteGround shared hosting)
- Routing: Single `index.php` front controller with clean URL routing via `.htaccess` rewrite rules
- Templating: Plain PHP templates (no Twig/Blade — keeps dependencies minimal)
- Sessions: PHP native sessions for authentication state

### Database — MySQL 8 / MariaDB

**⚠️ Important conflict with user rules**: The user's standard specifies PostgreSQL, but SiteGround shared hosting provides **MySQL/MariaDB only**. Options:

| Option | Pros | Cons |
|---|---|---|
| Use SiteGround MySQL | Native, zero setup, included in hosting | Not PostgreSQL |
| SiteGround Cloud + self-managed Postgres | Matches user standard | More complex, higher cost |
| External managed Postgres (e.g. Neon, Supabase) | Matches user standard | Extra cost, network latency, external dependency |

**Recommended**: Use MySQL/MariaDB as the pragmatic choice for SiteGround shared hosting. Write SQL in a way that is largely portable (avoid MySQL-specific idioms where possible). **This needs a decision from the project owner before coding begins.**

### Frontend — Vanilla JavaScript + Bulma CSS

- **CSS**: Bulma (matches user standards for new projects)
- **JavaScript**: Vanilla JS, no frameworks — ES2022+ syntax with modules
- **Icons**: Font Awesome 6 (free tier, CDN)
- **Assessment table editor**: Rich in-page JS component with inline editing, drag-to-reorder rows, and live risk-level colour preview
- **Export**: Client-side PDF via `jsPDF` library; server-side Excel via a PHP library (`PhpSpreadsheet`)
- **Auto-complete**: Custom JS component backed by a JSON API endpoint — no external dependency

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
         ├──► Router  →  Controller  →  Model  →  MySQL DB
         │
         └──► View (PHP template)
                  │
                  └──► Response (HTML / JSON)
```

### Request Types

- **Page requests**: Browser → PHP Controller → PHP template → HTML response
- **API requests**: Browser JS → PHP Controller → JSON response (for auto-complete, row saves, matrix preview, etc.)
- **Export requests**: Browser → PHP Controller → `PhpSpreadsheet` → file download / PDF

### Session & Auth Flow

```
Login form → POST /auth/login
    → PHP validates credentials (bcrypt password check)
    → Sets $_SESSION['user_id'] + $_SESSION['role']
    → Redirects to dashboard

All protected routes: Middleware checks $_SESSION['user_id']
    → Not set? Redirect to /auth/login with return_url
```

---

## 4. Database Design

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

### Table Definitions

#### `users`

| Column | Type | Notes |
|---|---|---|
| `id` | INT PK AUTO_INCREMENT | |
| `email` | VARCHAR(255) UNIQUE NOT NULL | Login credential |
| `display_name` | VARCHAR(100) NOT NULL | Shown in UI |
| `password_hash` | VARCHAR(255) NOT NULL | bcrypt |
| `role` | ENUM('admin','manager','assessor','viewer') | Default: assessor |
| `is_active` | TINYINT(1) | Soft disable |
| `created_at` | DATETIME | |
| `updated_at` | DATETIME | |
| `last_login_at` | DATETIME | |

#### `risk_matrices`

A matrix defines the scale — e.g. a 5×5 with severity labels 1–5 and likelihood labels 1–5.

| Column | Type | Notes |
|---|---|---|
| `id` | INT PK AUTO_INCREMENT | |
| `owner_id` | INT FK → users.id | NULL = system template |
| `name` | VARCHAR(150) NOT NULL | |
| `description` | TEXT | |
| `severity_axis_label` | VARCHAR(80) | e.g. "Severity" or "Consequence" |
| `likelihood_axis_label` | VARCHAR(80) | e.g. "Likelihood" or "Probability" |
| `is_public` | TINYINT(1) | Shared with all users |
| `is_system` | TINYINT(1) | Built-in template, not editable |
| `created_at` | DATETIME | |
| `updated_at` | DATETIME | |

#### `matrix_levels`

Defines the labels (and ordering) for each axis of a matrix.

| Column | Type | Notes |
|---|---|---|
| `id` | INT PK AUTO_INCREMENT | |
| `matrix_id` | INT FK → risk_matrices.id | |
| `axis` | ENUM('severity','likelihood') | |
| `level_value` | TINYINT | Numeric value (1, 2, 3…) |
| `label` | VARCHAR(80) | e.g. "Catastrophic", "Almost Certain" |
| `description` | TEXT | Optional clarification |
| `sort_order` | TINYINT | Display order |

#### `matrix_cells`

Maps each severity/likelihood combination to a risk category and colour.

| Column | Type | Notes |
|---|---|---|
| `id` | INT PK AUTO_INCREMENT | |
| `matrix_id` | INT FK → risk_matrices.id | |
| `severity_value` | TINYINT | |
| `likelihood_value` | TINYINT | |
| `risk_category` | VARCHAR(80) | e.g. "High", "Medium", "Low", "Extreme" |
| `colour_hex` | CHAR(7) | e.g. `#FF0000` |
| `numeric_score` | TINYINT | severity × likelihood or custom |

#### `assessments`

| Column | Type | Notes |
|---|---|---|
| `id` | INT PK AUTO_INCREMENT | |
| `owner_id` | INT FK → users.id | |
| `matrix_id` | INT FK → risk_matrices.id | The matrix template used |
| `title` | VARCHAR(200) NOT NULL | |
| `description` | TEXT | Project/activity context |
| `reference_number` | VARCHAR(80) | e.g. "RA-2026-001" |
| `location` | VARCHAR(200) | |
| `assessor_name` | VARCHAR(150) | Free text or resolved from user |
| `review_date` | DATE | When next review is due |
| `status` | ENUM('draft','in_review','approved','archived') | Default: draft |
| `copied_from_id` | INT FK → assessments.id | NULL = original |
| `created_at` | DATETIME | |
| `updated_at` | DATETIME | |
| `approved_at` | DATETIME | |
| `approved_by_id` | INT FK → users.id | |

#### `assessment_rows`

Each row represents one hazard being analysed.

| Column | Type | Notes |
|---|---|---|
| `id` | INT PK AUTO_INCREMENT | |
| `assessment_id` | INT FK → assessments.id | |
| `sort_order` | SMALLINT | Display order, drag-reorderable |
| `hazard` | TEXT | Hazard description |
| `effect` | TEXT | Effect / impact of the hazard |
| `existing_controls` | TEXT | Controls already in place |
| `severity_value` | TINYINT | FK-equivalent to matrix_levels |
| `likelihood_value` | TINYINT | FK-equivalent to matrix_levels |
| `risk_category` | VARCHAR(80) | Denormalised from matrix_cells for speed |
| `colour_hex` | CHAR(7) | Denormalised from matrix_cells |
| `additional_controls` | TEXT | Recommended further controls |
| `residual_severity_value` | TINYINT | After additional controls |
| `residual_likelihood_value` | TINYINT | After additional controls |
| `residual_risk_category` | VARCHAR(80) | |
| `residual_colour_hex` | CHAR(7) | |
| `comments` | TEXT | |
| `created_at` | DATETIME | |
| `updated_at` | DATETIME | |

#### `hazard_library`

Reusable hazard and effect text for auto-complete.

| Column | Type | Notes |
|---|---|---|
| `id` | INT PK AUTO_INCREMENT | |
| `owner_id` | INT FK → users.id | NULL = global |
| `hazard_text` | VARCHAR(500) | |
| `effect_text` | VARCHAR(500) | Suggested default effect |
| `tags` | VARCHAR(300) | Comma-separated for simple filtering |
| `use_count` | INT | Track popularity for ordering |
| `created_at` | DATETIME | |

#### `control_library`

Reusable control descriptions for auto-complete.

| Column | Type | Notes |
|---|---|---|
| `id` | INT PK AUTO_INCREMENT | |
| `owner_id` | INT FK → users.id | NULL = global |
| `control_text` | VARCHAR(500) | |
| `tags` | VARCHAR(300) | |
| `use_count` | INT | |
| `created_at` | DATETIME | |

#### `assessment_shares`

| Column | Type | Notes |
|---|---|---|
| `id` | INT PK AUTO_INCREMENT | |
| `assessment_id` | INT FK → assessments.id | |
| `shared_with_user_id` | INT FK → users.id | NULL = public link |
| `share_token` | VARCHAR(64) | For public/link sharing |
| `permission` | ENUM('view','comment','edit') | |
| `created_at` | DATETIME | |
| `expires_at` | DATETIME | NULL = no expiry |

---

## 5. Feature Breakdown

### Phase 1 — Foundation (MVP)

These features must be complete before the app is usable.

#### 5.1 User Authentication

- User registration with email + display name + password
- Login / logout with secure session management
- "Remember me" cookie (30-day sliding expiry)
- Password reset via email link
- Admin can create/disable users
- Role-based access: **admin**, **manager**, **assessor**, **viewer**

#### 5.2 System Risk Matrix Templates

- At least 3 built-in matrices: 3×3 (simple), 4×4 (standard), 5×5 (detailed AS/NZS style)
- Each matrix displayed as a colour-coded grid in the UI
- System templates are read-only; users can copy/clone a system template to customise it

#### 5.3 Assessment CRUD

- Create new assessment (choose matrix, fill header metadata)
- Add/edit/delete hazard rows inline in the table
- Drag-to-reorder rows
- Severity + Likelihood dropdowns auto-fill Risk Level cell with colour from the matrix
- Residual risk section per row (same dropdowns)
- Auto-save on change (debounced POST to API endpoint)
- Assessment status workflow: Draft → In Review → Approved → Archived
- Duplicate/copy an existing assessment

#### 5.4 Assessment List / Dashboard

- Dashboard showing: My Assessments (recent), Shared with Me, Drafts needing attention
- Sortable/filterable table: title, date, status, matrix type, risk level summary
- Search by title, reference number, or hazard text

#### 5.5 Export

- **Print/PDF**: Print-friendly CSS layout + browser print dialog (no library needed for basic version)
- **Excel (.xlsx)**: Server-side generation via `PhpSpreadsheet` with colour-coded risk cells
- **CSV**: Simple server-side export for data portability

### Phase 2 — Power Features

#### 5.6 Custom Risk Matrix Builder

- Visual matrix builder: choose dimensions (2–6 × 2–6), label each axis level, assign risk category + colour to each cell
- Preview live as you build
- Save as personal template or share publicly
- Clone any existing matrix to customise

#### 5.7 Hazard & Control Library

- Personal + global library of hazard descriptions and control text
- Auto-complete in assessment rows (type-ahead search)
- When a hazard is selected from the library, optionally pre-fill effect and suggested controls
- "Add to library" button when typing a new hazard/control in an assessment
- Library management page: browse, edit, tag, delete entries

#### 5.8 Sharing & Collaboration

- Share an assessment with a specific user (view / edit permissions)
- Generate a public read-only share link with optional expiry
- "Shared with me" section on dashboard
- Assessment owner can revoke shares at any time

### Phase 3 — Polish & Reporting

#### 5.9 Reporting & Analytics

- Summary statistics per assessment: count of High/Extreme/Medium/Low risks before and after controls
- Organisation-level report: all assessments by status, date range, assessor
- Risk register view: all open high/extreme risks across all assessments
- Visual risk matrix summary showing distribution of hazards across the grid

#### 5.10 Notifications & Audit Trail

- Email notification when an assessment is shared with you
- Email reminder when a review date is approaching (cron job)
- Audit log of changes (who changed what and when) per assessment

#### 5.11 Enhanced Export

- Enhanced PDF via `mpdf` or `TCPDF` (full fidelity, colour cells)
- Branded export (organisation logo + colours)

---

## 6. Page & Route Map

```
GET  /                          → Dashboard (redirect to /auth/login if not authenticated)
GET  /auth/login                → Login form
POST /auth/login                → Process login
GET  /auth/logout               → Destroy session, redirect to login
GET  /auth/register             → Registration form
POST /auth/register             → Process registration
GET  /auth/forgot-password      → Forgot password form
POST /auth/forgot-password      → Send reset email
GET  /auth/reset-password/{token} → Reset password form
POST /auth/reset-password/{token} → Process reset

GET  /assessments               → My assessments list
GET  /assessments/new           → New assessment form (choose matrix)
POST /assessments/new           → Create assessment, redirect to editor
GET  /assessments/{id}          → Assessment editor (main working page)
POST /assessments/{id}          → Update assessment header
DELETE /assessments/{id}        → Delete (soft-delete)
POST /assessments/{id}/copy     → Duplicate assessment
POST /assessments/{id}/status   → Change status
GET  /assessments/{id}/export/pdf   → Download PDF
GET  /assessments/{id}/export/xlsx  → Download Excel
GET  /assessments/{id}/export/csv   → Download CSV
GET  /assessments/{id}/share    → Manage sharing
POST /assessments/{id}/share    → Create share
DELETE /assessments/{id}/share/{shareId} → Revoke share

GET  /shared/{token}            → Public read-only assessment view

--- API Endpoints (return JSON) ---
GET  /api/assessments/{id}/rows           → Fetch all rows
POST /api/assessments/{id}/rows           → Add new row
PUT  /api/assessments/{id}/rows/{rowId}   → Update row
DELETE /api/assessments/{id}/rows/{rowId} → Delete row
POST /api/assessments/{id}/rows/reorder   → Update sort order

GET  /api/matrices/{id}                   → Matrix data (levels + cells)
GET  /api/matrices/{id}/risk-level?sev=X&lik=Y → Calculate risk for a cell

GET  /api/library/hazards?q=term          → Auto-complete hazard search
GET  /api/library/controls?q=term         → Auto-complete control search
POST /api/library/hazards                 → Save to hazard library
POST /api/library/controls                → Save to control library

--- Matrices ---
GET  /matrices                  → Matrix template library
GET  /matrices/new              → Create custom matrix
POST /matrices/new              → Save new matrix
GET  /matrices/{id}             → View matrix
GET  /matrices/{id}/edit        → Edit matrix (only owner/admin)
POST /matrices/{id}/edit        → Save changes
DELETE /matrices/{id}           → Delete matrix
POST /matrices/{id}/copy        → Clone matrix

--- Library ---
GET  /library                   → Hazard & control library browser
GET  /library/hazards           → Hazard list with search/filter
GET  /library/controls          → Control list with search/filter
POST /library/hazards/{id}/edit → Inline edit
DELETE /library/hazards/{id}    → Delete hazard entry
POST /library/controls/{id}/edit → Inline edit
DELETE /library/controls/{id}   → Delete control entry

--- Admin ---
GET  /admin                     → Admin overview
GET  /admin/users               → User list
POST /admin/users/{id}/toggle   → Enable/disable user
POST /admin/users/{id}/role     → Change role
GET  /admin/audit               → Audit log viewer
```

---

## 7. UI/UX Design Direction

### Overall Style

- **Framework**: Bulma CSS — clean, modern, responsive
- **Colour Palette**: Neutral greys/whites for chrome; vivid matrix colours (red/orange/yellow/green) are the dominant colour story of the app — they belong to the data, not the UI
- **Typography**: System font stack (Inter or system-ui) — no web font dependency
- **Icons**: Font Awesome 6 Free

### Key Screen Designs

#### Dashboard

- Clean card grid: "My Recent Assessments", "Shared With Me", "Quick Stats" (total assessments, open highs)
- Prominent "New Assessment" call-to-action button
- Status badge chips (Draft / In Review / Approved) with colour coding

#### Assessment Editor (Core Screen)

- Header section (collapsible): Title, reference number, description, assessor, location, review date, status, matrix selection
- Main area: **Scrollable wide table** with columns:
  - # | Hazard | Effect | Existing Controls | Sev | Lik | **Risk Level** (coloured cell) | Additional Controls | Res. Sev | Res. Lik | **Residual Risk** (coloured cell) | Comments | Actions
- Each cell is click-to-edit inline — no modal dialogs for row editing
- Risk Level cell colour auto-updates live as severity/likelihood are changed
- Drag handle on the left of each row for reordering
- "Add Row" button floats at the bottom of the table
- Toolbar: Save / Export / Share / Status Change / Copy Assessment
- Auto-save indicator ("Saved a moment ago" / "Saving…" / "Unsaved changes")

#### Matrix Builder

- Side-by-side layout: configuration panel on left, live matrix grid preview on right
- Axis configuration: add/remove/label levels
- Click any cell in the preview to assign a risk category and pick a colour

#### Risk Matrix Display (Inline, Reference Panel)

- Mini matrix displayed on the assessment editor page as a reference panel (collapsible sidebar or modal)
- Hovering a risk level in the table highlights the corresponding cell in the mini matrix

### Responsive Behaviour

- Desktop (1200px+): Full wide table visible, sidebar panels
- Tablet (768px–1199px): Table scrolls horizontally, panels collapse
- Mobile: Assessment list and view are mobile-friendly; the editor table is desktop-first (wide tables are inherently difficult on mobile — the design should acknowledge this with a "best viewed on desktop" notice rather than forcing a cramped mobile layout)

---

## 8. Folder & File Structure

```
riskasm/
│
├── public/                     ← Web root (point SiteGround document root here)
│   ├── index.php               ← Front controller / router entry point
│   ├── .htaccess               ← URL rewrite rules
│   └── assets/
│       ├── css/
│       │   ├── app.css         ← App-specific styles (Bulma overrides, table styles)
│       │   └── print.css       ← Print / PDF styles
│       ├── js/
│       │   ├── assessment-editor.js   ← Main assessment table editor component
│       │   ├── matrix-builder.js      ← Custom matrix builder UI
│       │   ├── autocomplete.js        ← Reusable autocomplete widget
│       │   ├── autosave.js            ← Debounced save logic
│       │   └── app.js                 ← Global utilities, flash messages
│       └── img/
│           └── logo.svg
│
├── src/                        ← PHP application source (not web-accessible)
│   ├── Config/
│   │   ├── config.php          ← DB credentials, app settings (env-based)
│   │   └── routes.php          ← Route definitions
│   │
│   ├── Core/
│   │   ├── Router.php          ← URL matching and dispatch
│   │   ├── Request.php         ← HTTP request wrapper
│   │   ├── Response.php        ← HTTP response helpers
│   │   ├── Session.php         ← Session abstraction
│   │   ├── Database.php        ← PDO wrapper / query builder
│   │   └── Mailer.php          ← Email sending abstraction
│   │
│   ├── Middleware/
│   │   ├── AuthMiddleware.php  ← Require authenticated session
│   │   └── RoleMiddleware.php  ← Require specific role
│   │
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── AssessmentController.php
│   │   ├── AssessmentRowApiController.php
│   │   ├── MatrixController.php
│   │   ├── LibraryController.php
│   │   ├── LibraryApiController.php
│   │   ├── ExportController.php
│   │   ├── ShareController.php
│   │   └── AdminController.php
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
│   │   └── AssessmentShare.php
│   │
│   └── Helpers/
│       ├── Csrf.php            ← CSRF token generation and validation
│       ├── Validator.php       ← Input validation helper
│       ├── Paginator.php       ← Simple pagination helper
│       └── DateHelper.php      ← Date formatting utilities
│
├── templates/                  ← PHP view templates
│   ├── layout/
│   │   ├── base.php            ← Master layout (head, navbar, footer)
│   │   ├── navbar.php
│   │   └── flash.php           ← Flash message display component
│   ├── auth/
│   │   ├── login.php
│   │   ├── register.php
│   │   └── forgot-password.php
│   ├── dashboard/
│   │   └── index.php
│   ├── assessments/
│   │   ├── index.php           ← Assessment list
│   │   ├── new.php             ← New assessment form
│   │   ├── editor.php          ← Main assessment editor (the core screen)
│   │   ├── shared-view.php     ← Public read-only view
│   │   └── _row.php            ← Row partial for JS rendering
│   ├── matrices/
│   │   ├── index.php
│   │   ├── view.php
│   │   └── builder.php
│   ├── library/
│   │   └── index.php
│   ├── admin/
│   │   ├── index.php
│   │   └── users.php
│   └── exports/
│       └── pdf-layout.php      ← PDF/print template
│
├── database/
│   ├── migrations/
│   │   ├── 001_create_users.sql
│   │   ├── 002_create_risk_matrices.sql
│   │   ├── 003_create_matrix_levels.sql
│   │   ├── 004_create_matrix_cells.sql
│   │   ├── 005_create_assessments.sql
│   │   ├── 006_create_assessment_rows.sql
│   │   ├── 007_create_hazard_library.sql
│   │   ├── 008_create_control_library.sql
│   │   └── 009_create_assessment_shares.sql
│   └── seeds/
│       ├── system_matrices.sql ← Built-in 3×3, 4×4, 5×5 matrices
│       └── sample_hazard_library.sql
│
├── vendor/                     ← Composer dependencies (gitignored)
├── composer.json               ← PHP dependencies
│
├── plans/                      ← This planning directory
│   └── project-plan.md
│
├── .env.example                ← Environment variable template
├── .gitignore
├── readme.md
└── change-log.md
```

---

## 9. Development Phases & Milestones

### Phase 1 — Foundation (Target: 4–5 weeks)

| Milestone | Deliverables |
|---|---|
| **M1: Project Scaffold** | Folder structure, Composer setup, Router, Database wrapper, .htaccess, base layout, Bulma integration |
| **M2: Auth System** | Login, register, logout, password reset, sessions, role middleware |
| **M3: System Matrices** | DB migrations + seeds for 3 system matrices; matrix display component |
| **M4: Assessment CRUD** | Create, list, view, delete, copy assessments; header editing |
| **M5: Assessment Editor** | Full hazard row table: add, edit, delete, reorder rows; live risk level colouring; auto-save |
| **M6: Basic Export** | Print CSS + browser print; CSV download |

**Phase 1 = a fully usable, shareable MVP.**

### Phase 2 — Power Features (Target: 3–4 weeks)

| Milestone | Deliverables |
|---|---|
| **M7: Custom Matrix Builder** | Visual builder UI; save/edit/clone matrices |
| **M8: Hazard & Control Library** | Library CRUD; auto-complete in editor rows |
| **M9: Sharing** | Share with user; public link with token; revoke |
| **M10: Excel Export** | PhpSpreadsheet integration; colour-coded .xlsx download |

### Phase 3 — Polish & Reporting (Target: 2–3 weeks)

| Milestone | Deliverables |
|---|---|
| **M11: Reporting** | Risk register view; assessment summary stats; matrix distribution chart |
| **M12: Notifications** | Email on share; review date reminder (cron); audit trail |
| **M13: Enhanced Export** | High-fidelity PDF via mpdf/TCPDF; branded exports |
| **M14: Admin Panel** | User management; audit log viewer |

### Phase 4 — AI Integration (Future, no timeline yet)

See Section 12.

---

## 10. Security Considerations

| Risk | Mitigation |
|---|---|
| SQL injection | Use PDO with prepared statements everywhere — no raw string interpolation in SQL |
| XSS | `htmlspecialchars()` on all output; CSP headers |
| CSRF | CSRF token on all state-changing forms and API calls |
| Auth bypass | Middleware checks on every protected route before any controller logic |
| Insecure direct object reference | Check ownership/permission on every assessment/matrix/row access |
| Password security | `password_hash()`/`password_verify()` with `PASSWORD_BCRYPT` |
| Session fixation | `session_regenerate_id(true)` on login |
| Sensitive config | DB credentials and secrets in `.env` file, never committed to git |
| File upload (future) | Strict MIME type and extension validation; store outside web root |
| Rate limiting | Login throttle: lock account / require CAPTCHA after 5 failed attempts |
| Share link brute force | 64-character cryptographically random tokens (`random_bytes`) |

---

## 11. Deployment Strategy

### Development Environment

- Local PHP dev server or MAMP/Herd/Valet
- MySQL local instance matching SiteGround's MariaDB version
- `.env` file for local config (never committed)

### SiteGround Setup

- Create MySQL database + user via SiteGround's Site Tools
- Set document root to the `public/` subdirectory (not the repo root)
- Upload via Git integration (SiteGround supports Git deployment) or SFTP
- Run database migrations manually via SiteGround's phpMyAdmin or SSH
- Configure PHP version to 8.2 in SiteGround's PHP Manager
- Set `APP_ENV=production` and `APP_DEBUG=false` in production `.env`
- Configure cron job (SiteGround cron) for review date reminder emails

### Git Workflow

- `main` branch = production-ready
- `develop` branch = integration
- Feature branches per milestone (e.g., `feature/m5-assessment-editor`)
- Deploy to production by merging to `main`

---

## 12. Future Enhancements (AI)

The readme specifically calls out AI as a planned future direction. This is a Phase 4 scope item.

### AI Feature Concepts

| Feature | Description |
|---|---|
| **Hazard suggestion** | Given an activity or task description, suggest likely hazards from a trained model or prompt-based LLM |
| **Control suggestion** | Given a hazard + effect, suggest common industry controls |
| **Risk level validation** | Flag where the assigned risk level seems inconsistent with the hazard type |
| **Assessment review** | AI reviews a completed assessment and highlights gaps or inconsistencies |
| **Hazard library enrichment** | Periodically suggest new global library entries based on assessment data |

### AI Implementation Approach (when ready)

- Use OpenAI API (GPT-4o or later) via server-side PHP calls — credentials stored in `.env`, never exposed to browser
- Suggestions presented as dismissable "suggestions" that the user can accept, edit, or ignore
- No AI response is ever saved automatically — always user-confirmed
- Rate limit AI calls per user to control API cost

---

## 13. Open Questions / Decisions Needed

These need answers before or early in coding to avoid rework.

| # | Question | Options | Impact |
|---|---|---|---|
| 1 | **Database: MySQL vs PostgreSQL?** | (a) MySQL — SiteGround native, simplest; (b) External PostgreSQL (Neon/Supabase) — matches user standard but adds complexity | Schema design, DB wrapper |
| 2 | **Assessment auto-save: server-side or client-side first?** | (a) Auto-save every change to server (requires good API and internet); (b) Save to localStorage first, sync on demand | Architecture of editor JS |
| 3 | **PDF export approach?** | (a) Browser print CSS only (simple, free); (b) Server-side mpdf/TCPDF (more fidelity, adds PHP dependency) | Export feature scope |
| 4 | **Multi-tenancy / organisations?** | (a) Individual users only (simpler); (b) Users belong to organisations and share a library + assessments within their org | DB schema changes if added later |
| 5 | **Email provider for password reset / notifications?** | SiteGround SMTP, SendGrid, Mailgun, Resend | Mailer config |
| 6 | **Branding / app name?** | "RiskASM" (working title), something else? | Logo, domain, export headers |

---

*Plan created: 2026-04-07*
*Next step: Review open questions, make decisions, then begin Phase 1 coding.*
