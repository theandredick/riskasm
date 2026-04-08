# Change Log — Smart Risk Assessment

All notable changes to this project are documented here.
Format: `## [version or milestone] — YYYY-MM-DD`

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
