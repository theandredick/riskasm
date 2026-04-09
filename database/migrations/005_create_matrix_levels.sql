-- Migration 005: matrix_levels table
-- Labels and ordering for each axis value within a matrix.

CREATE TABLE IF NOT EXISTS matrix_levels (
    id                  SERIAL PRIMARY KEY,
    matrix_id           INTEGER NOT NULL REFERENCES risk_matrices(id) ON DELETE CASCADE,
    axis                matrix_axis NOT NULL,
    level_value         SMALLINT NOT NULL,
    label               TEXT NOT NULL,
    one_word            TEXT,
    quantitative_range  TEXT,
    description         TEXT,
    sort_order          SMALLINT NOT NULL DEFAULT 0,
    UNIQUE (matrix_id, axis, level_value)
);

CREATE INDEX IF NOT EXISTS idx_matrix_levels_matrix ON matrix_levels (matrix_id, axis);
