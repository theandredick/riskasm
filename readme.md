# Risk Management
### Do risk assessments easily using standard and custom templates

## The problem - Why my own risk manager?
You want to have your risk assessments in one place so you can perform consistent analysis, copy and paste easily, auto-complete assessment components, reuse old assessments, export to other formats, and share with other users.

## The Solution - Make a visual, web-based tool I can access from anywhere, using SiteGround as my initial host.
Develop a multi-user, risk assessment web app that performs all levels of risk assessments (from simple to detailed) using standard or custom-designed risk matrices by looking at the lists of hazards associated with actions or conditions. The analysis can look at the hazard, its effect or impact, the controls in place, the severity, likelihood and thus the risk levels or categories, additional controls to reduce the risk, residual risk, and comments on the analysis.
Eventually, even use AI to suggest hazards, risk levels and their controls.
I have a viable workflow and many visual examples of risk assessment tables and matrices to share as inspiration.

## Local Development

**Requirements:** PHP 8.2, Composer, PostgreSQL 14 (Postgres.app)

```bash
# Install PHP dependencies
composer install

# Copy and configure environment
cp .env.example .env
# Edit .env with your local DB credentials

# Create local database and run migrations
createdb riskasm
php database/migrate.php

# Start dev server (web root is public_html/)
php -S localhost:10000 -t public_html/
```

Health check: http://localhost:10000/healthcheck

## Deployment

See [DEPLOYMENT.md](DEPLOYMENT.md) for the full step-by-step guide covering local setup, GitHub, and SiteGround.

```bash
./deploy.sh --dry-run    # preview changes
./deploy.sh              # deploy to production
```

