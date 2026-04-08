-- Migration 014: assessment_shares table

CREATE TABLE IF NOT EXISTS assessment_shares (
    id                   SERIAL PRIMARY KEY,
    assessment_id        INTEGER NOT NULL REFERENCES assessments(id) ON DELETE CASCADE,
    shared_with_user_id  INTEGER REFERENCES users(id) ON DELETE CASCADE,
    share_token          TEXT UNIQUE,
    permission           share_permission NOT NULL DEFAULT 'view',
    created_at           TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    expires_at           TIMESTAMPTZ
);

CREATE INDEX IF NOT EXISTS idx_assessment_shares_assessment ON assessment_shares (assessment_id);
CREATE INDEX IF NOT EXISTS idx_assessment_shares_user       ON assessment_shares (shared_with_user_id);
CREATE INDEX IF NOT EXISTS idx_assessment_shares_token      ON assessment_shares (share_token);
