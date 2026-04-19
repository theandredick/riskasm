# Change Log — Smart Risk Assessment

## [Phase 1 — 5.3 Assessment CRUD] — 2026-04-19

### Added
- **Assessment Model** (`src/Models/Assessment.php`) — full CRUD: `create`, `findForUser`, `findAllForUser`, `update`, `updateStatus`, `delete`, `copy`; status workflow constants (`STATUS_TRANSITIONS`); four template-type presets for `column_config` JSONB; `sharePermission` helper
- **AssessmentRow Model** (`src/Models/AssessmentRow.php`) — full CRUD with denormalized controls text via `LATERAL JOIN`; `batchSync` for localStorage→server flush; `saveControls` stores one existing-controls and one proposed-controls record per row in `row_controls` (phase 1 simplified); `reorder` by sort_order
- **AssessmentController** (`src/Controllers/AssessmentController.php`) — `index`, `create`, `store`, `edit`, `update`, `destroy`, `copy`, `updateStatus`; access control (owner vs. share); status-workflow guard
- **AssessmentRowApiController** (`src/Controllers/AssessmentRowApiController.php`) — `index`, `store`, `update`, `destroy`, `reorder`, `sync`; JSON API; CSRF validated from body `_csrf` field
- **New Assessment form** (`templates/assessments/new.php`) — two-column layout: left = matrix select (grouped system/custom) + 4 template-type radio cards with descriptions; right = header metadata fields (title*, ref, description, location, assessor, review date)
- **Assessment Editor** (`templates/assessments/editor.php`) — inline table editor; all columns shown/hidden via `column_config`; grouped headers (Natural Risk / Current Risk / Residual Risk); page header with status badge, Save, Duplicate, Export dropdown, Status workflow dropdown, Delete, Edit Details modal; localStorage-first draft banner with Keep/Discard options
- **Assessment Editor JS** (`public_html/assets/js/assessment-editor.js`) — vanilla JS `AssessmentEditor`; state stored in `localStorage` under `assessment_{id}_state`; server sync via `POST /api/assessments/{id}/sync`; `sendBeacon` on `beforeunload`; visibility-change sync; SortableJS drag-to-reorder; risk level auto-fill from preloaded matrix cells; contrast-colour detection for risk badges
- **Assessment List** (`templates/assessments/index.php`) — table with title, reference, template, matrix, row count, status badge, updated date; client-side search filter (> 5 rows); Duplicate and Delete inline actions
- **CSS** — assessment table styles: sticky Ocean Blue header, S/L/Risk sub-header row, drag handle, sortable ghost, min-width for horizontal scroll

### Files involved
- `src/Models/Assessment.php`, `src/Models/AssessmentRow.php` (new)
- `src/Controllers/AssessmentController.php`, `src/Controllers/AssessmentRowApiController.php`
- `templates/assessments/new.php`, `templates/assessments/editor.php`, `templates/assessments/index.php`
- `public_html/assets/js/assessment-editor.js` (new)
- `public_html/assets/css/app.css`, `templates/dashboard/index.php`

---


All notable changes to this project are documented here.
Format: `## [version or milestone] — YYYY-MM-DD`

---

## [Phase 1 — 5.2 System Risk Matrix Templates] — 2026-04-16

### Added
- **10 built-in system risk matrices** seeded via migration 018, all read-only:
  - Simple 3×3, Standard 4×4, Detailed 5×5 (AS/NZS ISO 31000), ISO 31010 5×5
  - Oil & Gas 6×6 (Shell/BP), FAA/ICAO Aviation 5×5, NORSOK Z-013 5×5
  - HSE UK Offshore 5×5, NFPA Fire Risk 5×3, U.S. Army ATP 5-19 4×5
- **Matrix Library page** (`/matrices`) — card grid showing all system and user-owned matrices with dimension badge, description, View and Clone actions
- **Matrix View page** (`/matrices/{id}`) — interactive colour-coded risk grid (severity × likelihood), risk bands legend with management guidance, likelihood reference table with quantitative frequency ranges, severity/consequence table with multi-category descriptions (Safety, Environmental, Asset Damage, Business Interruption where defined), clone action
- **RiskMatrix model** (`src/Models/RiskMatrix.php`) — full queries: list for user, full data bundle, cell lookup, consequence category descriptions, clone, delete
- **MatrixController** (`src/Controllers/MatrixController.php`) — index, show, copy, destroy; Phase 2 builder stubs
- **MatrixApiController** (`src/Controllers/MatrixApiController.php`) — `GET /api/matrices/{id}` (full bundle JSON) and `GET /api/matrices/{id}/cell` (single cell lookup for live risk level in assessment editor)
- **Consequence categories with per-level descriptions** for: AS/NZS 5×5 (Safety, Environmental, Asset Damage, Business Interruption), ISO 31010 (Safety/Health, Environmental, Reputation), Oil & Gas (People, Environment, Asset/Financial, Reputation), FAA/ICAO (Aviation Safety)
- **Clone functionality** — POST `/matrices/{id}/copy` deep-copies a matrix (levels, bands, cells, categories, descriptions) into a user-owned editable copy

### Files involved
- `database/migrations/018_seed_system_matrices.sql` (new)
- `src/Models/RiskMatrix.php`, `src/Models/MatrixLevel.php` (stub)
- `src/Controllers/MatrixController.php`, `src/Controllers/MatrixApiController.php`
- `templates/matrices/index.php` (new), `templates/matrices/view.php` (new)

---

## [Phase 1 — 5.2 Profile, UX & Mobile] — 2026-04-15

### Added
- **My Profile page** (`/profile`) — logged-in users can update their display name, email, and password (current-password verification required for password changes); session name kept in sync; remember-me tokens invalidated on password change
- **ProfileController** (`src/Controllers/ProfileController.php`) — `show` and `update` actions
- **Profile template** (`templates/profile/edit.php`) — two-panel form (account details + optional password change)
- **Profile link** in the navbar user-account dropdown (visible to all roles)

### Changed
- **User Management page** — "Create User" now opens as a Bulma modal; no page navigation required. On validation error the modal re-opens with field errors intact.
- **User table** — live client-side search/filter input (shown when > 5 users); mobile-responsive columns (email/status/last-login hidden on small screens, shown inline below the name)
- **Disable button** restyled to calm red (`is-danger-muted`) instead of amber warning
- **Navbar** — mobile hamburger now correctly colours the expanded menu in Ocean Blue; user-account dropdown responds to tap on mobile (was hover-only); nav links close the mobile menu on tap
- **`public_html/assets/js/app.js`** — replaced `type="module"` with `defer` for reliable DOM-ready behaviour; added modal, search-filter, and mobile-tap handlers
- **`public_html/assets/css/app.css`** — added `.is-danger-muted`, mobile navbar media-query overrides, modal responsive sizing

### Security
- **Last-admin guard** — `toggleUser` and `updateRole` now refuse actions that would leave zero active admin accounts (`User::isLastAdmin()`)

### Files involved
- `src/Controllers/ProfileController.php` (new)
- `src/Controllers/AdminController.php`, `src/Models/User.php`
- `src/Config/routes.php`
- `templates/profile/edit.php` (new)
- `templates/admin/users.php`, `templates/layout/navbar.php`, `templates/layout/base.php`
- `public_html/assets/css/app.css`, `public_html/assets/js/app.js`

---

## [Phase 1 — 5.1 User Authentication] — 2026-04-10

### Added
- **User model** (`src/Models/User.php`) — full CRUD, password hashing, reset tokens, remember-me tokens
- **AuthController** (`src/Controllers/AuthController.php`) — login, logout, register (first user auto-promoted to admin), forgot password, reset password, remember-me cookie (30-day sliding expiry)
- **AdminController** (`src/Controllers/AdminController.php`) — user list, toggle active/inactive, change role, create user (admin-only, role-guarded)
- **DashboardController** — basic protected dashboard page
- **View helper** (`src/Helpers/View.php`) — renders PHP templates with Bulma base or auth layout
- **Migrations 016 & 017** — `password_reset_tokens` and `remember_tokens` tables
- **Layout templates** — `base.php` (full app with fixed navbar), `auth.php` (centered card), `navbar.php`, `flash.php` (4 levels, auto-dismiss)
- **Auth templates** — `login.php`, `register.php`, `forgot-password.php`, `reset-password.php`
- **Admin templates** — `users.php` (table with inline role select & toggle), `create-user.php`
- **Dashboard template** — welcome card with navigation tiles
- **`public_html/assets/css/app.css`** — Bulma custom overrides (auth layout, risk cells, navbar, flash animations)
- **`public_html/assets/js/app.js`** — navbar burger, flash auto-dismiss, notification delete buttons
- Remember-me boot called from `index.php` before routing — restores session from cookie automatically
- Admin routes for user management added to `routes.php`

### Security
- CSRF token required on all POST forms
- bcrypt password hashing
- Password reset tokens: SHA-256 hashed in DB, 60-minute expiry, single-use
- Remember-me tokens: SHA-256 hashed in DB, 30-day sliding expiry, deleted on logout
- Session regenerated on every login (`session_regenerate_id(true)`)
- Safe redirect validation (open redirect guard on `return_url`)
- User enumeration prevention on forgot-password form

### Files involved
- `src/Models/User.php`, `src/Helpers/View.php`
- `src/Controllers/AuthController.php`, `AdminController.php`, `DashboardController.php`
- `src/Config/routes.php`, `public_html/index.php`
- `database/migrations/016_create_password_reset_tokens.sql`, `017_create_remember_tokens.sql`
- `templates/layout/base.php`, `auth.php`, `navbar.php`, `flash.php`
- `templates/auth/login.php`, `register.php`, `forgot-password.php`, `reset-password.php`
- `templates/admin/users.php`, `create-user.php`, `index.php`
- `templates/dashboard/index.php`
- `public_html/assets/css/app.css`, `public_html/assets/js/app.js`

---

## [Phase 0 — Deployment Fix & Installation Guide] — 2026-04-09

### Changed
- Renamed `public/` to `public_html/` to match SiteGround's fixed document root (no document root change in Site Tools required)
- Updated `deploy.sh`: added `--first-deploy` flag (removes SiteGround's `default.html` placeholder), `--upload-env` flag (SCP `.env.production` to server as `.env`), and improved post-deploy checklist
- Updated `readme.md` with local dev server command (`-t public_html/`) and deployment quick-start
- Updated `plans/project-plan.md` Section 8 and Section 11 to reflect `public_html/` structure and correct SiteGround document root behaviour
- Updated `.gitignore` to exclude `.env.*` (all env-specific files) while preserving `.env.example`

### Added
- `.env.production` — production environment file (gitignored); upload to server with `./deploy.sh --upload-env`
- `DEPLOYMENT.md` — reusable step-by-step installation guide covering local setup, GitHub, and SiteGround

### Files involved
- `public_html/` (renamed from `public/`), `deploy.sh`, `readme.md`, `DEPLOYMENT.md`
- `.env`, `.env.production`, `.env.example`, `.gitignore`
- `plans/project-plan.md`, `change-log.md`

---

## [Phase 0 — Environment & Scaffold] — 2026-04-08

### Added
- `.gitignore` covering `vendor/`, `.env`, `uploads/`, `*.log`
- `develop` branch created from `main`
- `change-log.md` stub (this file)
- Full project folder scaffold matching Section 8 of the project plan
- `composer.json` with mPDF, PhpSpreadsheet, PHPMailer, phpdotenv
- `.env.example` with all required environment keys (no values)
- Database migration files 001–015 (PostgreSQL schema matching Section 4)
- `database/migrate.php` runner script
- `public/index.php` front controller with `/healthcheck` route
- `public/.htaccess` Apache rewrite rules
- `deploy.sh` rsync-over-SSH deployment script

### Files involved
- `.gitignore`, `change-log.md`
- `composer.json`, `.env.example`
- `public/index.php`, `public/.htaccess`
- `database/migrate.php`, `database/migrations/001–015_*.sql`
- All stub files in `src/`, `templates/`, `public/assets/`
- `deploy.sh`

---
