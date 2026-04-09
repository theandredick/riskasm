-- Migration 007: matrix_risk_bands table
-- One record per risk band; stores full two-level descriptor, score range, and colour.

CREATE TABLE IF NOT EXISTS matrix_risk_bands (
    id                SERIAL PRIMARY KEY,
    matrix_id         INTEGER NOT NULL REFERENCES risk_matrices(id) ON DELETE CASCADE,
    band_label        TEXT NOT NULL,
    band_name         TEXT NOT NULL,
    score_min         SMALLINT,
    score_max         SMALLINT,
    colour_hex        CHAR(7) NOT NULL,
    short_description TEXT,
    full_description  TEXT,
    sort_order        SMALLINT NOT NULL DEFAULT 0,
    UNIQUE (matrix_id, band_label)
);

CREATE INDEX IF NOT EXISTS idx_mrb_matrix ON matrix_risk_bands (matrix_id, sort_order);
