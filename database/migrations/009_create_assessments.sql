-- Migration 009: assessments table

CREATE TABLE IF NOT EXISTS assessments (
    id               SERIAL PRIMARY KEY,
    owner_id         INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    matrix_id        INTEGER NOT NULL REFERENCES risk_matrices(id),
    title            TEXT NOT NULL,
    description      TEXT,
    reference_number TEXT,
    location         TEXT,
    assessor_name    TEXT,
    review_date      DATE,
    status           assessment_status NOT NULL DEFAULT 'draft',
    template_type    assessment_template NOT NULL DEFAULT 'simple',
    column_config    JSONB NOT NULL DEFAULT '{
        "show_activity_condition": true,
        "show_exposure_description": false,
        "show_exposed_assets": false,
        "show_natural_risk": false,
        "show_control_type": true,
        "show_accept_yn": true,
        "show_proposed_controls": false,
        "show_residual_risk": false
    }'::jsonb,
    copied_from_id   INTEGER REFERENCES assessments(id) ON DELETE SET NULL,
    created_at       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    approved_at      TIMESTAMPTZ,
    approved_by_id   INTEGER REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_assessments_owner  ON assessments (owner_id);
CREATE INDEX IF NOT EXISTS idx_assessments_status ON assessments (status);
CREATE INDEX IF NOT EXISTS idx_assessments_matrix ON assessments (matrix_id);

-- Full-text search index on title and reference_number
CREATE INDEX IF NOT EXISTS idx_assessments_fts ON assessments
    USING gin(to_tsvector('english', coalesce(title, '') || ' ' || coalesce(reference_number, '')));
