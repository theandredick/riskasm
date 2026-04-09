# Deployment Guide — Smart Risk Assessment

Step-by-step installation guide for new SiteGround PHP projects.
Copy and adapt this file for future projects in the Smart Business family.

---

## Part 1 — Local Setup

### 1.1 Prerequisites

| Tool | Install | Verify |
|---|---|---|
| PHP 8.2 | `brew install php@8.2` | `php -v` |
| Composer | `brew install composer` | `composer -V` |
| PostgreSQL | Postgres.app (download from postgresapp.com) | `psql --version` |
| Git | pre-installed on macOS | `git --version` |

Add PHP 8.2 to your PATH if Homebrew doesn't do it automatically:

```bash
echo 'export PATH="/opt/homebrew/opt/php@8.2/bin:$PATH"' >> ~/.zshrc
source ~/.zshrc
```

### 1.2 Project Setup

```bash
# Clone or create the project
git clone <repo-url>
cd riskasm

# Install PHP dependencies
composer install

# Create local environment file
cp .env.example .env
```

Edit `.env` and set at minimum:

```ini
APP_URL=http://localhost:10000
APP_ENV=local
APP_DEBUG=true
APP_KEY=<generate with: openssl rand -base64 32>

DB_HOST=/tmp          # Postgres.app uses a Unix socket at /tmp
DB_PORT=5432
DB_NAME=riskasm
DB_USER=<your macOS username>
DB_PASSWORD=          # Postgres.app default: no password
```

### 1.3 Database Setup (local)

```bash
# Create the database
createdb riskasm

# Run all migrations
php database/migrate.php
```

To run migrations with the admin user (for schema changes):

```bash
DB_USER=<admin_user> DB_PASSWORD=<password> php database/migrate.php
```

### 1.4 Start the Dev Server

```bash
php -S localhost:10000 -t public_html/
```

Verify: open http://localhost:10000/healthcheck — you should see:

```json
{
  "status": "ok",
  "db": { "status": "ok", "version": "PostgreSQL 14.x ..." }
}
```

---

## Part 2 — GitHub Setup

### 2.1 Create Repository

```bash
git init
git add .
git commit -m "Initial scaffold"
```

Create a new repo on GitHub (no README, no .gitignore — project already has both).

```bash
git remote add origin git@github.com:<org>/<repo>.git
git branch -M main
git push -u origin main

# Create develop branch
git checkout -b develop
git push -u origin develop
```

### 2.2 Branch Strategy

| Branch | Purpose |
|---|---|
| `main` | Production-ready code — deploy from here |
| `develop` | Integration branch |
| `feature/*` | One branch per milestone or feature |

Deploy to production: merge `develop` → `main`, then run `./deploy.sh`.

### 2.3 What Is (and Is Not) in Git

**Committed:**
- All PHP source (`src/`, `templates/`, `public_html/`, `database/`)
- `composer.json` and `composer.lock`
- `.env.example` (keys only, no values)
- `deploy.sh`, `readme.md`, `DEPLOYMENT.md`

**NOT committed (gitignored):**
- `.env`, `.env.*` — all environment/secrets files
- `vendor/` — install with `composer install`
- `uploads/`, `logs/`, `*.log`

---

## Part 3 — SiteGround Setup

### 3.1 Understand the Server File Structure

SiteGround shared hosting (GrowBig) has a **fixed** document root. You cannot change it.

```
/home/<ssh-user>/www/<domain>/          ← deploy root (NOT web-accessible)
├── public_html/                        ← FIXED document root (web-accessible)
│   ├── index.php                       ← front controller
│   ├── .htaccess
│   └── assets/
├── src/                                ← app source (safe — not web-accessible)
├── templates/
├── vendor/
├── database/
└── .env                                ← secrets (safe — not web-accessible)
```

`DEPLOY_REMOTE_PATH` in your local `.env` must point to the **deploy root** (the folder
ABOVE `public_html/`), not to `public_html/` itself.

**File Manager tip:** SiteGround's file manager may open to `public_html/` by default.
Navigate one level UP to reach the deploy root where `.env` lives. Use the "Show Hidden
Files" toggle to see dotfiles.

### 3.2 SSH Key Setup

1. Generate a key pair (if you don't already have one):
   ```bash
   ssh-keygen -t ed25519 -C "siteground-riskasm"
   cat ~/.ssh/id_ed25519.pub
   ```
2. In SiteGround Site Tools → Security → SSH Keys: paste the public key, give it a name, click Add.
3. Test the connection:
   ```bash
   ssh <ssh-user>@<ssh-host> "pwd"
   ```
   Expected output: `/home/<ssh-user>`

### 3.3 Verify the Remote Folder Structure

```bash
ssh <ssh-user>@<ssh-host> "ls -la ~/www/<domain>/"
```

You should see `public_html/` listed. That is your document root.

### 3.4 PHP Version

In SiteGround Site Tools → Devs → PHP Manager: set PHP version to **8.2**.

### 3.5 PostgreSQL Database

In SiteGround Site Tools → Databases → PostgreSQL:

- Create a database (note the database name)
- Create an **admin user** (for running migrations)
- Create a **restricted app user** (for the running app — SELECT, INSERT, UPDATE, DELETE only)
- Note the **host** shown on the PostgreSQL page (e.g. `andred19.sg-host.com`)

### 3.6 Local `.env` — Deploy Variables

Add these to your **local** `.env` (the `.env` on your Mac, not the server):

```ini
DEPLOY_SSH_USER=<ssh-username>
DEPLOY_SSH_HOST=<ssh-hostname>          # e.g. ssh.andred19.sg-host.com
DEPLOY_REMOTE_PATH=/home/<ssh-user>/www/<domain>
```

### 3.7 Production `.env.production`

Create `.env.production` locally (this file is gitignored):

```ini
APP_NAME="Smart Risk Assessment"
APP_URL=https://<your-domain>
APP_ENV=production
APP_DEBUG=false
APP_KEY=<generate: openssl rand -base64 32>

DB_HOST=<postgresql-host-from-site-tools>
DB_PORT=5432
DB_NAME=<database-name>
DB_USER=<restricted-app-user>
DB_PASSWORD=<restricted-app-user-password>

SESSION_NAME=riskasm_session
SESSION_LIFETIME=120

MAIL_HOST=<smtp-host>
MAIL_PORT=587
MAIL_USERNAME=<smtp-user>
MAIL_PASSWORD=<smtp-password>
MAIL_FROM_ADDRESS=<from-address>
MAIL_FROM_NAME="Smart Risk Assessment"
MAIL_ENCRYPTION=tls

# DEPLOY_* vars NOT needed on server — they are local-only
```

### 3.8 Upload the Production `.env`

```bash
./deploy.sh --upload-env
```

Verify it arrived:

```bash
ssh <ssh-user>@<ssh-host> "ls -la /home/<ssh-user>/www/<domain>/.env"
```

### 3.9 First Deploy

```bash
# Preview what will be transferred
./deploy.sh --dry-run

# Deploy files and remove SiteGround's default.html placeholder
./deploy.sh --first-deploy
```

### 3.10 Install Composer Dependencies on Server

```bash
ssh <ssh-user>@<ssh-host> \
  "cd /home/<ssh-user>/www/<domain> && composer install --no-dev --optimize-autoloader"
```

SiteGround GrowBig has Composer available in the SSH shell. If it is not found, try:

```bash
ssh <ssh-user>@<ssh-host> "which composer || php -r \"readfile('https://getcomposer.org/installer');\" | php"
```

### 3.11 Run Migrations

Migrations use the **admin DB user** (full schema privileges):

```bash
ssh <ssh-user>@<ssh-host> \
  "cd /home/<ssh-user>/www/<domain> && \
   DB_USER=<admin-user> DB_PASSWORD=<admin-password> php database/migrate.php"
```

### 3.12 Verify the Deployment

```bash
curl https://<your-domain>/healthcheck
```

Expected response:

```json
{
  "status": "ok",
  "php_version": "8.2.x",
  "env_mode": "production",
  "db": { "status": "ok", "version": "PostgreSQL 14.x ..." }
}
```

If `db.status` is `"error"`, the message shows the exact PDO error — check DB host,
name, user, and password in the server-side `.env`.

### 3.13 Subsequent Deploys

For every deploy after the first:

```bash
./deploy.sh
```

That is it. The script excludes `.env`, `vendor/`, and logs.
Re-run Composer and migrations only when those have changed.

---

## Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| Browser shows SiteGround default page | `default.html` still in `public_html/` | Run `./deploy.sh --first-deploy` |
| `.env` not found in file manager | File manager opens at `public_html/`; dotfiles may be hidden | Navigate one level up; toggle "Show Hidden Files" |
| `healthcheck` returns `db: error` | Wrong DB host/credentials in production `.env` | SSH in and check/edit the `.env` file |
| `Class not found` PHP error | `vendor/` not installed on server | Run `composer install --no-dev` via SSH |
| 500 error with no detail | `APP_DEBUG=false` hides errors | SSH and check `~/www/<domain>/../logs/php_error.log` |
| rsync: permission denied | SSH key not added or wrong user | Re-check Site Tools → SSH Keys |
