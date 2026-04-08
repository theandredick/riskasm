-- Migration 004: matrix_consequence_categories table
-- Named consequence/severity dimensions (e.g. Safety, Environmental, Asset Damage).

CREATE TABLE IF NOT EXISTS matrix_consequence_categories (
    id          SERIAL PRIMARY KEY,
    matrix_id   INTEGER NOT NULL REFERENCES risk_matrices(id) ON DELETE CASCADE,
    name        TEXT NOT NULL,
    sort_order  SMALLINT NOT NULL DEFAULT 0
);

CREATE INDEX IF NOT EXISTS idx_mcc_matrix ON matrix_consequence_categories (matrix_id);
