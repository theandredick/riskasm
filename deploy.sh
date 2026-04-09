#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# deploy.sh — Smart Risk Assessment deployment script
#
# Syncs the project to SiteGround via rsync over SSH.
# Excludes: .env, vendor/, uploads/, .git/, *.log
#
# Usage:
#   ./deploy.sh                  — deploy to production
#   ./deploy.sh --dry-run        — preview changes without transferring files
#   ./deploy.sh --first-deploy   — deploy + remove SiteGround's default.html placeholder
#   ./deploy.sh --upload-env     — SCP .env.production to server as .env (run once per server)
#
# Prerequisites:
#   - SSH key added to SiteGround Site Tools → Security → SSH Keys
#   - DEPLOY_SSH_USER, DEPLOY_SSH_HOST, DEPLOY_REMOTE_PATH set in local .env
#
# SiteGround folder layout:
#   DEPLOY_REMOTE_PATH/              ← deploy root (NOT web-accessible)
#   ├── public_html/                 ← fixed document root (web-accessible)
#   │   ├── index.php
#   │   ├── .htaccess
#   │   └── assets/
#   ├── src/
#   ├── templates/
#   ├── vendor/
#   ├── database/
#   └── .env                         ← created via --upload-env, never via rsync
# ─────────────────────────────────────────────────────────────────────────────

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Load .env for deploy variables
if [[ -f "$SCRIPT_DIR/.env" ]]; then
    set -a
    # shellcheck disable=SC1091
    source "$SCRIPT_DIR/.env"
    set +a
fi

SSH_USER="${DEPLOY_SSH_USER:?DEPLOY_SSH_USER must be set in .env}"
SSH_HOST="${DEPLOY_SSH_HOST:?DEPLOY_SSH_HOST must be set in .env}"
SSH_PORT="${DEPLOY_SSH_PORT:-22}"
SSH_KEY="${DEPLOY_SSH_KEY:-}"
REMOTE_PATH="${DEPLOY_REMOTE_PATH:?DEPLOY_REMOTE_PATH must be set in .env}"

# Build SSH options (ssh/rsync use -p for port; scp uses -P)
SSH_KEY_EXPANDED="${SSH_KEY/#\~/$HOME}"
SSH_OPTS="-o StrictHostKeyChecking=no -p ${SSH_PORT}"
SCP_OPTS="-o StrictHostKeyChecking=no -P ${SSH_PORT}"
if [[ -n "$SSH_KEY_EXPANDED" ]]; then
    SSH_OPTS="${SSH_OPTS} -i ${SSH_KEY_EXPANDED}"
    SCP_OPTS="${SCP_OPTS} -i ${SSH_KEY_EXPANDED}"
fi

DRY_RUN=""
FIRST_DEPLOY=false
UPLOAD_ENV=false

for arg in "$@"; do
    case "$arg" in
        --dry-run)      DRY_RUN="--dry-run" ;;
        --first-deploy) FIRST_DEPLOY=true ;;
        --upload-env)   UPLOAD_ENV=true ;;
    esac
done

REMOTE="${SSH_USER}@${SSH_HOST}"

# ── Upload production .env ────────────────────────────────────────────────────
if [[ "$UPLOAD_ENV" == true ]]; then
    if [[ ! -f "$SCRIPT_DIR/.env.production" ]]; then
        echo "❌  .env.production not found in project root."
        echo "    Create it from .env.example and fill in production values first."
        exit 1
    fi
    echo ""
    echo "📤  Uploading .env.production → ${REMOTE}:${REMOTE_PATH}/.env"
    scp ${SCP_OPTS} \
        "$SCRIPT_DIR/.env.production" \
        "${REMOTE}:${REMOTE_PATH}/.env"
    echo "✅  Production .env uploaded."
    echo "    Verify: ssh ${REMOTE} 'ls -la ${REMOTE_PATH}/.env'"
    echo ""
    exit 0
fi

# ── Sync files ────────────────────────────────────────────────────────────────
if [[ -n "$DRY_RUN" ]]; then
    echo "⚠️   DRY RUN — no files will be transferred."
fi

echo ""
echo "🚀  Deploying Smart Risk Assessment"
echo "    Source : $SCRIPT_DIR"
echo "    Target : ${REMOTE}:${REMOTE_PATH}"
echo ""

rsync -avz --progress $DRY_RUN \
    --exclude='.env' \
    --exclude='.env.*' \
    --exclude='.git/' \
    --exclude='.gitignore' \
    --exclude='vendor/' \
    --exclude='uploads/' \
    --exclude='logs/' \
    --exclude='*.log' \
    --exclude='.DS_Store' \
    --exclude='node_modules/' \
    --exclude='deploy.sh' \
    --exclude='venv/' \
    --exclude='plans/' \
    --exclude='DEPLOYMENT.md' \
    -e "ssh ${SSH_OPTS}" \
    "$SCRIPT_DIR/" \
    "${REMOTE}:${REMOTE_PATH}/"

echo ""

if [[ -z "$DRY_RUN" ]]; then
    # ── First-deploy cleanup ──────────────────────────────────────────────────
    if [[ "$FIRST_DEPLOY" == true ]]; then
        echo "🧹  First-deploy: removing SiteGround placeholder file..."
        # SiteGround creates 'Default.html' (capital D) on Linux; remove both variants
        ssh ${SSH_OPTS} "${REMOTE}" \
            "rm -f '${REMOTE_PATH}/public_html/default.html' '${REMOTE_PATH}/public_html/Default.html'" \
            && echo "    Removed default.html / Default.html (or they were already gone)."
        echo ""
    fi

    echo "✅  Deploy complete."
    echo ""
    echo "📋  Post-deploy checklist:"
    echo "    1. Verify .env exists on server:"
    echo "       ssh ${SSH_OPTS} ${REMOTE} 'ls -la ${REMOTE_PATH}/.env'"
    echo "       (If missing, run: ./deploy.sh --upload-env)"
    echo ""
    echo "    2. Install/update Composer dependencies on server (if composer.json changed):"
    echo "       ssh ${SSH_OPTS} ${REMOTE} 'cd ${REMOTE_PATH} && composer install --no-dev --optimize-autoloader'"
    echo ""
    echo "    3. Apply any new migrations (uses admin DB user):"
    echo "       ssh ${SSH_OPTS} ${REMOTE} 'cd ${REMOTE_PATH} && php database/migrate.php'"
    echo ""
    echo "    4. Verify health check (tests PHP + DB connection):"
    echo "       curl https://andred19.sg-host.com/healthcheck"
    echo ""
    echo "    5. Check PHP error log if anything looks wrong:"
    echo "       ssh ${SSH_OPTS} ${REMOTE} 'tail -50 ~/logs/php_error.log 2>/dev/null || echo no log found'"
else
    echo "✅  Dry run complete. Run without --dry-run to deploy."
fi
echo ""
