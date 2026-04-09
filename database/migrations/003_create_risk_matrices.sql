-- Migration 003: risk_matrices table

CREATE TABLE IF NOT EXISTS risk_matrices (
    id                      SERIAL PRIMARY KEY,
    owner_id                INTEGER REFERENCES users(id) ON DELETE SET NULL,
    name                    TEXT NOT NULL,
    description             TEXT,
    severity_axis_label     TEXT NOT NULL DEFAULT 'Severity',
    likelihood_axis_label   TEXT NOT NULL DEFAULT 'Likelihood',
    is_public               BOOLEAN NOT NULL DEFAULT FALSE,
    is_system               BOOLEAN NOT NULL DEFAULT FALSE,
    created_at              TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_risk_matrices_owner ON risk_matrices (owner_id);
CREATE INDEX IF NOT EXISTS idx_risk_matrices_system ON risk_matrices (is_system);
