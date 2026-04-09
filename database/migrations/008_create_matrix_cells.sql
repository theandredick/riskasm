-- Migration 008: matrix_cells table
-- Maps each severity/likelihood pair to a risk band.
-- risk_category and colour_hex are denormalised from matrix_risk_bands for fast UI rendering.

CREATE TABLE IF NOT EXISTS matrix_cells (
    id               SERIAL PRIMARY KEY,
    matrix_id        INTEGER NOT NULL REFERENCES risk_matrices(id) ON DELETE CASCADE,
    severity_value   SMALLINT NOT NULL,
    likelihood_value SMALLINT NOT NULL,
    risk_band_id     INTEGER REFERENCES matrix_risk_bands(id) ON DELETE SET NULL,
    risk_category    TEXT NOT NULL,
    colour_hex       CHAR(7) NOT NULL,
    numeric_score    SMALLINT,
    UNIQUE (matrix_id, severity_value, likelihood_value)
);

CREATE INDEX IF NOT EXISTS idx_matrix_cells_matrix ON matrix_cells (matrix_id);
CREATE INDEX IF NOT EXISTS idx_matrix_cells_band   ON matrix_cells (risk_band_id);
