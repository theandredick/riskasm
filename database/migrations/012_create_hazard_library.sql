-- Migration 012: hazard_library table

CREATE TABLE IF NOT EXISTS hazard_library (
    id           SERIAL PRIMARY KEY,
    owner_id     INTEGER REFERENCES users(id) ON DELETE SET NULL,
    hazard_text  TEXT NOT NULL,
    effect_text  TEXT,
    tags         TEXT,
    use_count    INTEGER NOT NULL DEFAULT 0,
    created_at   TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_hazard_library_owner ON hazard_library (owner_id);
CREATE INDEX IF NOT EXISTS idx_hazard_library_fts   ON hazard_library
    USING gin(to_tsvector('english', hazard_text || ' ' || coalesce(effect_text, '')));
