-- Migration 006: matrix_level_category_descriptions table
-- Per-(severity level, consequence category) description text.

CREATE TABLE IF NOT EXISTS matrix_level_category_descriptions (
    id                   SERIAL PRIMARY KEY,
    matrix_id            INTEGER NOT NULL REFERENCES risk_matrices(id) ON DELETE CASCADE,
    severity_level_value SMALLINT NOT NULL,
    category_id          INTEGER NOT NULL REFERENCES matrix_consequence_categories(id) ON DELETE CASCADE,
    description          TEXT NOT NULL,
    UNIQUE (matrix_id, severity_level_value, category_id)
);

CREATE INDEX IF NOT EXISTS idx_mlcd_matrix ON matrix_level_category_descriptions (matrix_id, severity_level_value);
