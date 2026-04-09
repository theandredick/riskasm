-- Migration 011: row_controls table
-- Each record is one control measure attached to a hazard row.
-- A row can have any number of controls per phase (existing / proposed).

CREATE TABLE IF NOT EXISTS row_controls (
    id                  SERIAL PRIMARY KEY,
    assessment_row_id   INTEGER NOT NULL REFERENCES assessment_rows(id) ON DELETE CASCADE,
    phase               control_phase NOT NULL,
    description         TEXT NOT NULL,
    control_type        TEXT,
    library_control_id  INTEGER,  -- FK to control_library added after migration 013
    sort_order          SMALLINT NOT NULL DEFAULT 0,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_row_controls_row   ON row_controls (assessment_row_id, phase, sort_order);
CREATE INDEX IF NOT EXISTS idx_row_controls_lib   ON row_controls (library_control_id);
