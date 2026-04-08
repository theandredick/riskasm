#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# deploy.sh — Smart Risk Assessment deployment script
#
# Syncs the project to SiteGround via rsync over SSH.
# Excludes: .env, vendor/, uploads/, .git/, *.log
#
# Usage:
#   ./deploy.sh             — deploy to production
#   ./deploy.sh --dry-run   — preview changes without transferring files
#
# Prerequisites:
#   - SSH key already added to SiteGround Site Tools → SSH Keys
#   - DEPLOY_SSH_USER, DEPLOY_SSH_HOST, DEPLOY_REMOTE_PATH set in .env
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
REMOTE_PATH="${DEPLOY_REMOTE_PATH:?DEPLOY_REMOTE_PATH must be set in .env}"

DRY_RUN=""
if [[ "${1:-}" == "--dry-run" ]]; then
    DRY_RUN="--dry-run"
    echo "⚠️   DRY RUN — no files will be transferred."
fi

REMOTE="${SSH_USER}@${SSH_HOST}:${REMOTE_PATH}"

echo ""
echo "🚀  Deploying Smart Risk Assessment"
echo "    Source : $SCRIPT_DIR"
echo "    Target : $REMOTE"
echo ""

rsync -avz --progress $DRY_RUN \
    --exclude='.env' \
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
    -e "ssh -o StrictHostKeyChecking=no" \
    "$SCRIPT_DIR/" \
    "$REMOTE/"

echo ""
if [[ -z "$DRY_RUN" ]]; then
    echo "✅  Deploy complete."
    echo ""
    echo "📋  Post-deploy checklist:"
    echo "    1. Verify production .env exists on server: ssh ${SSH_USER}@${SSH_HOST} 'ls ${REMOTE_PATH}/.env'"
    echo "    2. Run composer on server (if composer.json changed):"
    echo "       ssh ${SSH_USER}@${SSH_HOST} 'cd ${REMOTE_PATH} && composer install --no-dev --optimize-autoloader'"
    echo "    3. Apply any new migrations:"
    echo "       ssh ${SSH_USER}@${SSH_HOST} 'cd ${REMOTE_PATH} && php database/migrate.php'"
    echo "    4. Verify health check: curl https://YOUR_DOMAIN/healthcheck"
else
    echo "✅  Dry run complete. Run without --dry-run to deploy."
fi
echo ""
