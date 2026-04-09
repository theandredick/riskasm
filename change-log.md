# Change Log — Smart Risk Assessment

All notable changes to this project are documented here.
Format: `## [version or milestone] — YYYY-MM-DD`

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
