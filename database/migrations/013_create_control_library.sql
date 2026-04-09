-- Migration 013: control_library table

CREATE TABLE IF NOT EXISTS control_library (
    id            SERIAL PRIMARY KEY,
    owner_id      INTEGER REFERENCES users(id) ON DELETE SET NULL,
    control_text  TEXT NOT NULL,
    control_type  TEXT,
    tags          TEXT,
    use_count     INTEGER NOT NULL DEFAULT 0,
    created_at    TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_control_library_owner ON control_library (owner_id);
CREATE INDEX IF NOT EXISTS idx_control_library_fts   ON control_library
    USING gin(to_tsvector('english', control_text));

-- Add deferred FK from row_controls.library_control_id → control_library(id)
ALTER TABLE row_controls
    ADD CONSTRAINT fk_row_controls_library
    FOREIGN KEY (library_control_id)
    REFERENCES control_library(id)
    ON DELETE SET NULL
    NOT VALID;

ALTER TABLE row_controls VALIDATE CONSTRAINT fk_row_controls_library;
