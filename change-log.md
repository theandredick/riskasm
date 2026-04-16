# Change Log — Smart Risk Assessment

All notable changes to this project are documented here.
Format: `## [version or milestone] — YYYY-MM-DD`

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
