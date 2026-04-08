-- Migration 010: assessment_rows table
-- One row per hazard. All optional columns nullable; active set controlled by column_config.
-- Controls are NOT stored here — see row_controls (migration 011).

CREATE TABLE IF NOT EXISTS assessment_rows (
    id                       SERIAL PRIMARY KEY,
    assessment_id            INTEGER NOT NULL REFERENCES assessments(id) ON DELETE CASCADE,
    sort_order               SMALLINT NOT NULL DEFAULT 0,

    -- Hazard description columns
    activity_condition       TEXT,
    hazard                   TEXT,
    exposure_description     TEXT,
    exposed_assets           TEXT,
    effect                   TEXT,

    -- Natural risk (before any controls) — optional
    natural_severity_value   SMALLINT,
    natural_likelihood_value SMALLINT,
    natural_risk_category    TEXT,
    natural_colour_hex       CHAR(7),
    natural_risk_accept      BOOLEAN,

    -- Current risk (after existing controls)
    severity_value           SMALLINT,
    likelihood_value         SMALLINT,
    risk_category            TEXT,
    colour_hex               CHAR(7),
    current_risk_accept      BOOLEAN,

    -- Residual risk (after proposed controls) — optional
    residual_severity_value   SMALLINT,
    residual_likelihood_value SMALLINT,
    residual_risk_category    TEXT,
    residual_colour_hex       CHAR(7),
    residual_risk_accept      BOOLEAN,

    comments                 TEXT,
    created_at               TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at               TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_assessment_rows_assessment ON assessment_rows (assessment_id, sort_order);

-- Full-text search on hazard and effect text
CREATE INDEX IF NOT EXISTS idx_assessment_rows_fts ON assessment_rows
    USING gin(to_tsvector('english', coalesce(hazard, '') || ' ' || coalesce(effect, '')));
