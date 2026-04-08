-- Migration 015: audit_log table

CREATE TABLE IF NOT EXISTS audit_log (
    id             BIGSERIAL PRIMARY KEY,
    user_id        INTEGER REFERENCES users(id) ON DELETE SET NULL,
    assessment_id  INTEGER REFERENCES assessments(id) ON DELETE SET NULL,
    action         TEXT NOT NULL,
    detail         TEXT,
    created_at     TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_audit_log_user       ON audit_log (user_id);
CREATE INDEX IF NOT EXISTS idx_audit_log_assessment ON audit_log (assessment_id);
CREATE INDEX IF NOT EXISTS idx_audit_log_created    ON audit_log (created_at DESC);
