-- Migration 018: Seed 10 built-in system risk matrices
-- All matrices are read-only (is_system = TRUE). Users can clone any to customise.
-- Each DO block is idempotent: skipped if the matrix already exists.

-- ── 1. Simple 3×3 ─────────────────────────────────────────────────────────────
DO $m1$
DECLARE
    m_id   INTEGER;
    b_low  INTEGER;
    b_med  INTEGER;
    b_high INTEGER;
BEGIN
    IF EXISTS (SELECT 1 FROM risk_matrices WHERE name = 'Simple 3×3' AND is_system) THEN RETURN; END IF;

    INSERT INTO risk_matrices (owner_id, name, description, severity_axis_label, likelihood_axis_label, is_public, is_system)
    VALUES (NULL, 'Simple 3×3',
        'A straightforward three-level matrix for everyday workplace hazard assessments.',
        'Severity', 'Likelihood', TRUE, TRUE)
    RETURNING id INTO m_id;

    INSERT INTO matrix_levels (matrix_id, axis, level_value, label, one_word, description, sort_order) VALUES
        (m_id, 'severity',   1, 'Low',    'Low',    'Minor inconvenience or negligible damage. No injury expected.',    0),
        (m_id, 'severity',   2, 'Medium', 'Medium', 'Noticeable injury or moderate damage requiring attention.',        1),
        (m_id, 'severity',   3, 'High',   'High',   'Severe injury, significant property damage or major disruption.', 2),
        (m_id, 'likelihood', 1, 'Unlikely',  'Unlikely',  'Remote chance. Not expected to occur under normal conditions.',      0),
        (m_id, 'likelihood', 2, 'Possible',  'Possible',  'Could occur in some circumstances. Happens occasionally.',          1),
        (m_id, 'likelihood', 3, 'Likely',    'Likely',    'Expected to occur regularly. History of occurrence in similar tasks.', 2);

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'I',   'Low',    1, 2, '#27ae60',
        'Low Risk', 'Risk is broadly acceptable. Manage with standard procedures and routine monitoring.', 0)
    RETURNING id INTO b_low;

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'II',  'Medium', 3, 4, '#f1c40f',
        'Medium Risk', 'Risk requires attention. Review controls and implement additional measures where practicable.', 1)
    RETURNING id INTO b_med;

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'III', 'High',   5, 9, '#c0392b',
        'High Risk', 'Risk is unacceptable without significant control measures. Do not proceed until risk is adequately reduced.', 2)
    RETURNING id INTO b_high;

    -- Cells: severity_value × likelihood_value
    INSERT INTO matrix_cells (matrix_id, severity_value, likelihood_value, risk_band_id, risk_category, colour_hex, numeric_score) VALUES
        (m_id, 1, 1, b_low,  'Low',    '#27ae60', 1),
        (m_id, 1, 2, b_low,  'Low',    '#27ae60', 2),
        (m_id, 1, 3, b_med,  'Medium', '#f1c40f', 3),
        (m_id, 2, 1, b_low,  'Low',    '#27ae60', 2),
        (m_id, 2, 2, b_med,  'Medium', '#f1c40f', 4),
        (m_id, 2, 3, b_high, 'High',   '#c0392b', 6),
        (m_id, 3, 1, b_med,  'Medium', '#f1c40f', 3),
        (m_id, 3, 2, b_high, 'High',   '#c0392b', 6),
        (m_id, 3, 3, b_high, 'High',   '#c0392b', 9);
END $m1$;


-- ── 2. Standard 4×4 ───────────────────────────────────────────────────────────
DO $m2$
DECLARE
    m_id   INTEGER;
    b_low  INTEGER;
    b_med  INTEGER;
    b_high INTEGER;
    b_ext  INTEGER;
BEGIN
    IF EXISTS (SELECT 1 FROM risk_matrices WHERE name = 'Standard 4×4' AND is_system) THEN RETURN; END IF;

    INSERT INTO risk_matrices (owner_id, name, description, severity_axis_label, likelihood_axis_label, is_public, is_system)
    VALUES (NULL, 'Standard 4×4',
        'A four-level matrix suited for business and operational risk assessments.',
        'Severity', 'Likelihood', TRUE, TRUE)
    RETURNING id INTO m_id;

    INSERT INTO matrix_levels (matrix_id, axis, level_value, label, one_word, description, sort_order) VALUES
        (m_id, 'severity',   1, 'Minor',        'Minor',        'First aid treatment only. Minor disruption, easily corrected.',                        0),
        (m_id, 'severity',   2, 'Moderate',      'Moderate',     'Medical treatment required. Moderate disruption or reversible damage.',                1),
        (m_id, 'severity',   3, 'Major',         'Major',        'Serious injury or permanent incapacity. Major disruption or significant loss.',        2),
        (m_id, 'severity',   4, 'Catastrophic',  'Catastrophic', 'Single fatality or multiple serious injuries. Major system failure or permanent loss.', 3),
        (m_id, 'likelihood', 1, 'Rare',     'Rare',     'May occur only in exceptional circumstances. Less than once in five years.',           0),
        (m_id, 'likelihood', 2, 'Unlikely', 'Unlikely', 'Could occur in some circumstances. Once in one to five years.',                        1),
        (m_id, 'likelihood', 3, 'Possible', 'Possible', 'Might occur occasionally. Could happen once or twice per year.',                       2),
        (m_id, 'likelihood', 4, 'Likely',   'Likely',   'Will probably occur in most circumstances. Expected several times per year.',          3);

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'I',   'Low',     1, 3,  '#27ae60',
        'Low Risk', 'Acceptable. Manage through routine procedures. Review periodically.', 0)
    RETURNING id INTO b_low;

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'II',  'Medium',  4, 8,  '#f1c40f',
        'Medium Risk', 'Requires management attention. Implement additional controls. Supervise closely and review within 30 days.', 1)
    RETURNING id INTO b_med;

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'III', 'High',    9, 12, '#e67e22',
        'High Risk', 'Significant risk requiring prompt action. Senior management approval needed. Implement controls before or during work.', 2)
    RETURNING id INTO b_high;

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'IV',  'Extreme', 13, 16, '#c0392b',
        'Extreme Risk', 'Unacceptable. Do not proceed. Stop work if already in progress. Requires immediate executive action and comprehensive controls.', 3)
    RETURNING id INTO b_ext;

    INSERT INTO matrix_cells (matrix_id, severity_value, likelihood_value, risk_band_id, risk_category, colour_hex, numeric_score) VALUES
        (m_id, 1, 1, b_low,  'Low',     '#27ae60', 1),
        (m_id, 1, 2, b_low,  'Low',     '#27ae60', 2),
        (m_id, 1, 3, b_low,  'Low',     '#27ae60', 3),
        (m_id, 1, 4, b_med,  'Medium',  '#f1c40f', 4),
        (m_id, 2, 1, b_low,  'Low',     '#27ae60', 2),
        (m_id, 2, 2, b_med,  'Medium',  '#f1c40f', 4),
        (m_id, 2, 3, b_med,  'Medium',  '#f1c40f', 6),
        (m_id, 2, 4, b_med,  'Medium',  '#f1c40f', 8),
        (m_id, 3, 1, b_low,  'Low',     '#27ae60', 3),
        (m_id, 3, 2, b_med,  'Medium',  '#f1c40f', 6),
        (m_id, 3, 3, b_high, 'High',    '#e67e22', 9),
        (m_id, 3, 4, b_high, 'High',    '#e67e22', 12),
        (m_id, 4, 1, b_med,  'Medium',  '#f1c40f', 4),
        (m_id, 4, 2, b_med,  'Medium',  '#f1c40f', 8),
        (m_id, 4, 3, b_high, 'High',    '#e67e22', 12),
        (m_id, 4, 4, b_ext,  'Extreme', '#c0392b', 16);
END $m2$;


-- ── 3. Detailed 5×5 (AS/NZS ISO 31000) ───────────────────────────────────────
DO $m3$
DECLARE
    m_id   INTEGER;
    b_low  INTEGER;
    b_med  INTEGER;
    b_high INTEGER;
    b_ext  INTEGER;
    c_saf  INTEGER;
    c_env  INTEGER;
    c_asd  INTEGER;
    c_bi   INTEGER;
BEGIN
    IF EXISTS (SELECT 1 FROM risk_matrices WHERE name = 'Detailed 5×5 (AS/NZS)' AND is_system) THEN RETURN; END IF;

    INSERT INTO risk_matrices (owner_id, name, description, severity_axis_label, likelihood_axis_label, is_public, is_system)
    VALUES (NULL, 'Detailed 5×5 (AS/NZS)',
        'Comprehensive five-level matrix aligned with AS/NZS ISO 31000. Includes multi-category consequence descriptions for Safety, Environment, Asset Damage and Business Interruption.',
        'Consequence', 'Likelihood', TRUE, TRUE)
    RETURNING id INTO m_id;

    INSERT INTO matrix_levels (matrix_id, axis, level_value, label, one_word, description, sort_order) VALUES
        (m_id, 'severity', 1, 'Insignificant', 'Insignificant', 'No injuries. Negligible financial loss. No impact on objectives.',             0),
        (m_id, 'severity', 2, 'Minor',         'Minor',         'First aid treatment. Minor disruption. Fully reversible impact.',              1),
        (m_id, 'severity', 3, 'Moderate',       'Moderate',      'Lost time injury. Moderate financial impact. Medium-term remediation.',        2),
        (m_id, 'severity', 4, 'Major',          'Major',         'Single permanent disability or hospitalisation. Large financial impact.',      3),
        (m_id, 'severity', 5, 'Catastrophic',   'Catastrophic',  'Single fatality or multiple permanent disabilities. Permanent consequences.',  4),
        (m_id, 'likelihood', 1, 'Rare',          'Rare',          'May occur only in exceptional circumstances. Less than once in 10 years.',       0),
        (m_id, 'likelihood', 2, 'Unlikely',      'Unlikely',      'Not expected but possible. Could occur once in 3–10 years.',                    1),
        (m_id, 'likelihood', 3, 'Possible',      'Possible',      'Might occur occasionally. Could happen once per year.',                         2),
        (m_id, 'likelihood', 4, 'Likely',        'Likely',        'Will probably occur. Expected several times per year.',                         3),
        (m_id, 'likelihood', 5, 'Almost Certain','Certain',       'Expected to occur in most circumstances. Monthly or more frequently.',          4);

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'I',   'Low',    1, 4,  '#27ae60',
        'Low Risk', 'Acceptable. Manage with standard procedures. Monitor and review annually.', 0)
    RETURNING id INTO b_low;

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'II',  'Medium', 5, 9,  '#f1c40f',
        'Medium Risk', 'Requires management responsibility to be specified. Implement additional controls and review within 30 days.', 1)
    RETURNING id INTO b_med;

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'III', 'High',   10, 15, '#e67e22',
        'High Risk', 'Senior management attention required. Implement controls to reduce risk before proceeding. Review within 7 days.', 2)
    RETURNING id INTO b_high;

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'IV',  'Extreme', 16, 25, '#c0392b',
        'Extreme Risk', 'Unacceptable. Do not proceed. Requires executive sign-off. Implement comprehensive controls or abandon the activity.', 3)
    RETURNING id INTO b_ext;

    INSERT INTO matrix_cells (matrix_id, severity_value, likelihood_value, risk_band_id, risk_category, colour_hex, numeric_score) VALUES
        (m_id, 1, 1, b_low,  'Low',     '#27ae60', 1),
        (m_id, 1, 2, b_low,  'Low',     '#27ae60', 2),
        (m_id, 1, 3, b_low,  'Low',     '#27ae60', 3),
        (m_id, 1, 4, b_low,  'Low',     '#27ae60', 4),
        (m_id, 1, 5, b_med,  'Medium',  '#f1c40f', 5),
        (m_id, 2, 1, b_low,  'Low',     '#27ae60', 2),
        (m_id, 2, 2, b_low,  'Low',     '#27ae60', 4),
        (m_id, 2, 3, b_med,  'Medium',  '#f1c40f', 6),
        (m_id, 2, 4, b_med,  'Medium',  '#f1c40f', 8),
        (m_id, 2, 5, b_high, 'High',    '#e67e22', 10),
        (m_id, 3, 1, b_low,  'Low',     '#27ae60', 3),
        (m_id, 3, 2, b_med,  'Medium',  '#f1c40f', 6),
        (m_id, 3, 3, b_med,  'Medium',  '#f1c40f', 9),
        (m_id, 3, 4, b_high, 'High',    '#e67e22', 12),
        (m_id, 3, 5, b_high, 'High',    '#e67e22', 15),
        (m_id, 4, 1, b_low,  'Low',     '#27ae60', 4),
        (m_id, 4, 2, b_med,  'Medium',  '#f1c40f', 8),
        (m_id, 4, 3, b_high, 'High',    '#e67e22', 12),
        (m_id, 4, 4, b_ext,  'Extreme', '#c0392b', 16),
        (m_id, 4, 5, b_ext,  'Extreme', '#c0392b', 20),
        (m_id, 5, 1, b_med,  'Medium',  '#f1c40f', 5),
        (m_id, 5, 2, b_high, 'High',    '#e67e22', 10),
        (m_id, 5, 3, b_high, 'High',    '#e67e22', 15),
        (m_id, 5, 4, b_ext,  'Extreme', '#c0392b', 20),
        (m_id, 5, 5, b_ext,  'Extreme', '#c0392b', 25);

    -- Consequence categories
    INSERT INTO matrix_consequence_categories (matrix_id, name, sort_order) VALUES
        (m_id, 'Safety',                 0) RETURNING id INTO c_saf;
    INSERT INTO matrix_consequence_categories (matrix_id, name, sort_order) VALUES
        (m_id, 'Environmental Impact',   1) RETURNING id INTO c_env;
    INSERT INTO matrix_consequence_categories (matrix_id, name, sort_order) VALUES
        (m_id, 'Asset Damage',           2) RETURNING id INTO c_asd;
    INSERT INTO matrix_consequence_categories (matrix_id, name, sort_order) VALUES
        (m_id, 'Business Interruption',  3) RETURNING id INTO c_bi;

    INSERT INTO matrix_level_category_descriptions (matrix_id, severity_level_value, category_id, description) VALUES
        -- Safety
        (m_id, 1, c_saf, 'No injury. No health effects observed.'),
        (m_id, 2, c_saf, 'First aid treatment only. No lost time.'),
        (m_id, 3, c_saf, 'Lost time injury (LTI) or restricted work case.'),
        (m_id, 4, c_saf, 'Single permanent disability or hospitalisation.'),
        (m_id, 5, c_saf, 'Single fatality or multiple permanent disabilities.'),
        -- Environmental
        (m_id, 1, c_env, 'Negligible release, contained on-site immediately.'),
        (m_id, 2, c_env, 'Minor localised impact, short-term and fully reversible.'),
        (m_id, 3, c_env, 'Moderate off-site impact. Medium-term cleanup required.'),
        (m_id, 4, c_env, 'Major off-site impact. Long-term remediation required.'),
        (m_id, 5, c_env, 'Catastrophic widespread contamination. Irreversible damage.'),
        -- Asset Damage
        (m_id, 1, c_asd, 'Damage < $10,000.'),
        (m_id, 2, c_asd, 'Damage $10,000 – $100,000.'),
        (m_id, 3, c_asd, 'Damage $100,000 – $1,000,000.'),
        (m_id, 4, c_asd, 'Damage $1,000,000 – $10,000,000.'),
        (m_id, 5, c_asd, 'Damage > $10,000,000 or total loss.'),
        -- Business Interruption
        (m_id, 1, c_bi, 'Less than 4 hours downtime. Fully operational within shift.'),
        (m_id, 2, c_bi, '4 hours – 1 day downtime.'),
        (m_id, 3, c_bi, '1 day – 1 week downtime.'),
        (m_id, 4, c_bi, '1 week – 1 month downtime.'),
        (m_id, 5, c_bi, 'Greater than 1 month downtime or permanent closure.');
END $m3$;


-- ── 4. ISO 31010 5×5 ──────────────────────────────────────────────────────────
DO $m4$
DECLARE
    m_id   INTEGER;
    b_low  INTEGER;
    b_mod  INTEGER;
    b_high INTEGER;
    b_ext  INTEGER;
    c_saf  INTEGER;
    c_env  INTEGER;
    c_rep  INTEGER;
BEGIN
    IF EXISTS (SELECT 1 FROM risk_matrices WHERE name = 'ISO 31010 5×5' AND is_system) THEN RETURN; END IF;

    INSERT INTO risk_matrices (owner_id, name, description, severity_axis_label, likelihood_axis_label, is_public, is_system)
    VALUES (NULL, 'ISO 31010 5×5',
        'International standard 5×5 risk matrix per ISO 31010:2019 for enterprise and project risk management. Commonly used in corporate risk registers and ISO-compliant safety programs.',
        'Severity', 'Likelihood', TRUE, TRUE)
    RETURNING id INTO m_id;

    INSERT INTO matrix_levels (matrix_id, axis, level_value, label, one_word, description, sort_order) VALUES
        (m_id, 'severity', 1, 'Insignificant', 'Insignificant', 'No injuries, negligible loss. No impact on programme or objectives.',                0),
        (m_id, 'severity', 2, 'Minor',         'Minor',         'First aid needed. Minor disruption or damage. Short-term, reversible effects.',      1),
        (m_id, 'severity', 3, 'Moderate',       'Moderate',      'Injury with lost time. Moderate cost or schedule impact.',                           2),
        (m_id, 'severity', 4, 'Major',          'Major',         'Single serious injury or large impact on budget or schedule.',                       3),
        (m_id, 'severity', 5, 'Catastrophic',   'Catastrophic',  'Death or major system failure. Permanent and irreversible consequences.',            4),
        (m_id, 'likelihood', 1, 'Rare',          'Rare',          'Happens only in exceptional circumstances. Approximately once in 5+ years.',          0),
        (m_id, 'likelihood', 2, 'Unlikely',      'Unlikely',      'Not expected but possible. Could occur once in 1–5 years.',                          1),
        (m_id, 'likelihood', 3, 'Possible',      'Possible',      'Might occur occasionally. Could happen once per year.',                              2),
        (m_id, 'likelihood', 4, 'Likely',        'Likely',        'Expected to occur regularly. Several times per year.',                               3),
        (m_id, 'likelihood', 5, 'Almost Certain','Certain',       'Occurs frequently. Monthly or more.',                                               4);

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'I',   'Low',      1, 4,  '#27ae60',
        'Low', 'Acceptable risk. Manage with normal controls and procedures. Review annually.', 0)
    RETURNING id INTO b_low;

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'II',  'Moderate', 5, 9,  '#f1c40f',
        'Moderate', 'Risk is tolerable but requires management attention. Specify controls and responsibilities. Monitor and review within 30 days.', 1)
    RETURNING id INTO b_mod;

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'III', 'High',     10, 15, '#e67e22',
        'High', 'Risk must be reduced. Senior management notification required. Additional controls must be implemented before proceeding.', 2)
    RETURNING id INTO b_high;

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'IV',  'Extreme',  16, 25, '#c0392b',
        'Extreme', 'Risk is unacceptable. Immediate action required. Do not proceed. Requires executive decision and comprehensive risk treatment plan.', 3)
    RETURNING id INTO b_ext;

    INSERT INTO matrix_cells (matrix_id, severity_value, likelihood_value, risk_band_id, risk_category, colour_hex, numeric_score) VALUES
        (m_id, 1, 1, b_low,  'Low',      '#27ae60', 1),
        (m_id, 1, 2, b_low,  'Low',      '#27ae60', 2),
        (m_id, 1, 3, b_low,  'Low',      '#27ae60', 3),
        (m_id, 1, 4, b_low,  'Low',      '#27ae60', 4),
        (m_id, 1, 5, b_mod,  'Moderate', '#f1c40f', 5),
        (m_id, 2, 1, b_low,  'Low',      '#27ae60', 2),
        (m_id, 2, 2, b_low,  'Low',      '#27ae60', 4),
        (m_id, 2, 3, b_mod,  'Moderate', '#f1c40f', 6),
        (m_id, 2, 4, b_mod,  'Moderate', '#f1c40f', 8),
        (m_id, 2, 5, b_high, 'High',     '#e67e22', 10),
        (m_id, 3, 1, b_low,  'Low',      '#27ae60', 3),
        (m_id, 3, 2, b_mod,  'Moderate', '#f1c40f', 6),
        (m_id, 3, 3, b_mod,  'Moderate', '#f1c40f', 9),
        (m_id, 3, 4, b_high, 'High',     '#e67e22', 12),
        (m_id, 3, 5, b_high, 'High',     '#e67e22', 15),
        (m_id, 4, 1, b_low,  'Low',      '#27ae60', 4),
        (m_id, 4, 2, b_mod,  'Moderate', '#f1c40f', 8),
        (m_id, 4, 3, b_high, 'High',     '#e67e22', 12),
        (m_id, 4, 4, b_ext,  'Extreme',  '#c0392b', 16),
        (m_id, 4, 5, b_ext,  'Extreme',  '#c0392b', 20),
        (m_id, 5, 1, b_mod,  'Moderate', '#f1c40f', 5),
        (m_id, 5, 2, b_high, 'High',     '#e67e22', 10),
        (m_id, 5, 3, b_high, 'High',     '#e67e22', 15),
        (m_id, 5, 4, b_ext,  'Extreme',  '#c0392b', 20),
        (m_id, 5, 5, b_ext,  'Extreme',  '#c0392b', 25);

    INSERT INTO matrix_consequence_categories (matrix_id, name, sort_order) VALUES
        (m_id, 'Safety / Health', 0) RETURNING id INTO c_saf;
    INSERT INTO matrix_consequence_categories (matrix_id, name, sort_order) VALUES
        (m_id, 'Environmental',   1) RETURNING id INTO c_env;
    INSERT INTO matrix_consequence_categories (matrix_id, name, sort_order) VALUES
        (m_id, 'Reputation',      2) RETURNING id INTO c_rep;

    INSERT INTO matrix_level_category_descriptions (matrix_id, severity_level_value, category_id, description) VALUES
        (m_id, 1, c_saf, 'No injury or health effect.'),
        (m_id, 2, c_saf, 'First aid treatment. No lost work time.'),
        (m_id, 3, c_saf, 'Medical treatment. Lost work time injury.'),
        (m_id, 4, c_saf, 'Serious injury; temporary or permanent incapacity.'),
        (m_id, 5, c_saf, 'Fatality or permanent total disability.'),
        (m_id, 1, c_env, 'Negligible environmental impact.'),
        (m_id, 2, c_env, 'Minor, isolated impact — fully reversible.'),
        (m_id, 3, c_env, 'Localised off-site impact; short-term cleanup.'),
        (m_id, 4, c_env, 'Widespread impact; long-term remediation needed.'),
        (m_id, 5, c_env, 'Permanent widespread environmental damage.'),
        (m_id, 1, c_rep, 'Internal concern only.'),
        (m_id, 2, c_rep, 'Local community or stakeholder concern.'),
        (m_id, 3, c_rep, 'Regional media and public attention.'),
        (m_id, 4, c_rep, 'National media coverage; regulatory scrutiny.'),
        (m_id, 5, c_rep, 'International media; long-term reputational damage.');
END $m4$;


-- ── 5. Oil & Gas Shell/BP 6×6 ─────────────────────────────────────────────────
DO $m5$
DECLARE
    m_id   INTEGER;
    b_low  INTEGER;
    b_med  INTEGER;
    b_high INTEGER;
    b_vhi  INTEGER;
    c_ppl  INTEGER;
    c_env  INTEGER;
    c_ast  INTEGER;
    c_rep  INTEGER;
BEGIN
    IF EXISTS (SELECT 1 FROM risk_matrices WHERE name = 'Oil & Gas 6×6 (Shell/BP)' AND is_system) THEN RETURN; END IF;

    INSERT INTO risk_matrices (owner_id, name, description, severity_axis_label, likelihood_axis_label, is_public, is_system)
    VALUES (NULL, 'Oil & Gas 6×6 (Shell/BP)',
        'Process safety matrix used in the oil & gas industry. Six-level severity and likelihood scales covering People, Environment, Asset and Reputation consequence categories. Designed for QRA, bowtie and safety case modelling.',
        'Severity', 'Frequency', TRUE, TRUE)
    RETURNING id INTO m_id;

    INSERT INTO matrix_levels (matrix_id, axis, level_value, label, one_word, quantitative_range, description, sort_order) VALUES
        (m_id, 'severity', 1, 'Negligible',   'Negligible',   NULL, 'No harm or damage. No environmental release. No impact on business.',                                0),
        (m_id, 'severity', 2, 'Minor',        'Minor',        NULL, 'First aid injury. Minor asset loss. Minor local environmental release, fully reversible.',            1),
        (m_id, 'severity', 3, 'Moderate',     'Moderate',     NULL, 'Lost time injury or restricted work. Moderate repairs. Localised off-site environmental impact.',     2),
        (m_id, 'severity', 4, 'Serious',      'Serious',      NULL, 'Single fatality or permanent disability. Major disruption. Significant environmental damage.',        3),
        (m_id, 'severity', 5, 'Major',        'Major',        NULL, 'Multiple serious injuries or permanent disabilities. High cost incident. Extensive environmental harm.',4),
        (m_id, 'severity', 6, 'Catastrophic', 'Catastrophic', NULL, 'Multiple fatalities. Disaster-level incident. Catastrophic and irreversible environmental damage.',   5),
        (m_id, 'likelihood', 1, 'Remote',     'Remote',     '< 10⁻⁴/year',           'Extremely unlikely; less than 1 in 10,000 years.',                             0),
        (m_id, 'likelihood', 2, 'Unlikely',   'Unlikely',   '10⁻⁴ – 10⁻³/year',     'Not expected but conceivable during asset lifetime.',                           1),
        (m_id, 'likelihood', 3, 'Possible',   'Possible',   '10⁻³ – 10⁻²/year',     'May occur during operational lifetime of the asset.',                           2),
        (m_id, 'likelihood', 4, 'Likely',     'Likely',     '10⁻² – 10⁻¹/year',     'Expected to occur during asset operating life.',                                3),
        (m_id, 'likelihood', 5, 'Frequent',   'Frequent',   '> 10⁻¹/year',           'Occurs repeatedly; expected multiple times per year.',                          4),
        (m_id, 'likelihood', 6, 'Continuous', 'Continuous', 'Ongoing',               'Occurs frequently on an ongoing basis. Part of normal operations.',             5);

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'I',   'Low',       1, 6,  '#27ae60',
        'Low', 'Acceptable. Manage with standard operating procedures. Review during routine safety audits.', 0)
    RETURNING id INTO b_low;

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'II',  'Medium',    7, 12, '#f1c40f',
        'Medium', 'Risk requires management attention. Implement additional engineering or administrative controls. Review within 60 days.', 1)
    RETURNING id INTO b_med;

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'III', 'High',      13, 20, '#e67e22',
        'High', 'Significant risk. Senior management responsibility. Implement prevention and mitigation controls with high priority. Stop work unless controls are in place.', 2)
    RETURNING id INTO b_high;

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'IV',  'Very High', 21, 36, '#c0392b',
        'Very High', 'Unacceptable risk. Activity must not proceed. Requires Board-level or VP-level authorisation. Comprehensive risk elimination or avoidance required.', 3)
    RETURNING id INTO b_vhi;

    INSERT INTO matrix_cells (matrix_id, severity_value, likelihood_value, risk_band_id, risk_category, colour_hex, numeric_score) VALUES
        (m_id, 1, 1, b_low,  'Low',       '#27ae60', 1),
        (m_id, 1, 2, b_low,  'Low',       '#27ae60', 2),
        (m_id, 1, 3, b_low,  'Low',       '#27ae60', 3),
        (m_id, 1, 4, b_low,  'Low',       '#27ae60', 4),
        (m_id, 1, 5, b_low,  'Low',       '#27ae60', 5),
        (m_id, 1, 6, b_low,  'Low',       '#27ae60', 6),
        (m_id, 2, 1, b_low,  'Low',       '#27ae60', 2),
        (m_id, 2, 2, b_low,  'Low',       '#27ae60', 4),
        (m_id, 2, 3, b_low,  'Low',       '#27ae60', 6),
        (m_id, 2, 4, b_med,  'Medium',    '#f1c40f', 8),
        (m_id, 2, 5, b_med,  'Medium',    '#f1c40f', 10),
        (m_id, 2, 6, b_med,  'Medium',    '#f1c40f', 12),
        (m_id, 3, 1, b_low,  'Low',       '#27ae60', 3),
        (m_id, 3, 2, b_low,  'Low',       '#27ae60', 6),
        (m_id, 3, 3, b_med,  'Medium',    '#f1c40f', 9),
        (m_id, 3, 4, b_med,  'Medium',    '#f1c40f', 12),
        (m_id, 3, 5, b_high, 'High',      '#e67e22', 15),
        (m_id, 3, 6, b_high, 'High',      '#e67e22', 18),
        (m_id, 4, 1, b_low,  'Low',       '#27ae60', 4),
        (m_id, 4, 2, b_med,  'Medium',    '#f1c40f', 8),
        (m_id, 4, 3, b_med,  'Medium',    '#f1c40f', 12),
        (m_id, 4, 4, b_high, 'High',      '#e67e22', 16),
        (m_id, 4, 5, b_high, 'High',      '#e67e22', 20),
        (m_id, 4, 6, b_vhi,  'Very High', '#c0392b', 24),
        (m_id, 5, 1, b_low,  'Low',       '#27ae60', 5),
        (m_id, 5, 2, b_med,  'Medium',    '#f1c40f', 10),
        (m_id, 5, 3, b_high, 'High',      '#e67e22', 15),
        (m_id, 5, 4, b_high, 'High',      '#e67e22', 20),
        (m_id, 5, 5, b_vhi,  'Very High', '#c0392b', 25),
        (m_id, 5, 6, b_vhi,  'Very High', '#c0392b', 30),
        (m_id, 6, 1, b_low,  'Low',       '#27ae60', 6),
        (m_id, 6, 2, b_med,  'Medium',    '#f1c40f', 12),
        (m_id, 6, 3, b_high, 'High',      '#e67e22', 18),
        (m_id, 6, 4, b_vhi,  'Very High', '#c0392b', 24),
        (m_id, 6, 5, b_vhi,  'Very High', '#c0392b', 30),
        (m_id, 6, 6, b_vhi,  'Very High', '#c0392b', 36);

    INSERT INTO matrix_consequence_categories (matrix_id, name, sort_order) VALUES
        (m_id, 'People (Safety)',  0) RETURNING id INTO c_ppl;
    INSERT INTO matrix_consequence_categories (matrix_id, name, sort_order) VALUES
        (m_id, 'Environment',      1) RETURNING id INTO c_env;
    INSERT INTO matrix_consequence_categories (matrix_id, name, sort_order) VALUES
        (m_id, 'Asset / Financial',2) RETURNING id INTO c_ast;
    INSERT INTO matrix_consequence_categories (matrix_id, name, sort_order) VALUES
        (m_id, 'Reputation',       3) RETURNING id INTO c_rep;

    INSERT INTO matrix_level_category_descriptions (matrix_id, severity_level_value, category_id, description) VALUES
        (m_id, 1, c_ppl, 'No harm. No health effects.'),
        (m_id, 2, c_ppl, 'First aid only. No lost time.'),
        (m_id, 3, c_ppl, 'Lost time injury or restricted work.'),
        (m_id, 4, c_ppl, 'Single fatality or permanent disability.'),
        (m_id, 5, c_ppl, 'Multiple serious injuries or permanent disabilities.'),
        (m_id, 6, c_ppl, 'Multiple fatalities.'),
        (m_id, 1, c_env, 'Negligible. Fully contained on-site.'),
        (m_id, 2, c_env, 'Minor local effect. Fully reversible.'),
        (m_id, 3, c_env, 'Localised off-site. Short-term cleanup.'),
        (m_id, 4, c_env, 'Significant off-site damage. Long-term remediation.'),
        (m_id, 5, c_env, 'Extensive impact. Long-term remediation.'),
        (m_id, 6, c_env, 'Catastrophic. Irreversible damage.'),
        (m_id, 1, c_ast, 'Trivial. No financial impact.'),
        (m_id, 2, c_ast, 'Minor asset loss or small repair cost.'),
        (m_id, 3, c_ast, 'Moderate repair or replacement cost.'),
        (m_id, 4, c_ast, 'Major asset loss or high-cost incident.'),
        (m_id, 5, c_ast, 'Very major asset loss or extended downtime.'),
        (m_id, 6, c_ast, 'Catastrophic total loss.'),
        (m_id, 1, c_rep, 'No external impact.'),
        (m_id, 2, c_rep, 'Local community concern.'),
        (m_id, 3, c_rep, 'Regional media attention.'),
        (m_id, 4, c_rep, 'National media. Regulatory scrutiny.'),
        (m_id, 5, c_rep, 'International media coverage.'),
        (m_id, 6, c_rep, 'Sustained international damage. Licence to operate threatened.');
END $m5$;


-- ── 6. FAA / ICAO Aviation 5×5 ────────────────────────────────────────────────
DO $m6$
DECLARE
    m_id   INTEGER;
    b_acc  INTEGER;
    b_rev  INTEGER;
    b_mit  INTEGER;
    b_una  INTEGER;
    c_saf  INTEGER;
BEGIN
    IF EXISTS (SELECT 1 FROM risk_matrices WHERE name = 'FAA / ICAO Aviation 5×5' AND is_system) THEN RETURN; END IF;

    INSERT INTO risk_matrices (owner_id, name, description, severity_axis_label, likelihood_axis_label, is_public, is_system)
    VALUES (NULL, 'FAA / ICAO Aviation 5×5',
        'Standard aviation safety management system (SMS) matrix per FAA AC 150/5200-37 and ICAO Doc 9859. Used by airports, airlines and regulators for operational risk decisions.',
        'Severity', 'Likelihood', TRUE, TRUE)
    RETURNING id INTO m_id;

    INSERT INTO matrix_levels (matrix_id, axis, level_value, label, one_word, quantitative_range, description, sort_order) VALUES
        (m_id, 'severity', 1, 'Negligible',  'Negligible',  NULL, 'No safety effect. Nuisance-level consequences only.',                                   0),
        (m_id, 'severity', 2, 'Minor',       'Minor',       NULL, 'Slight reduction in safety margins. Minor operational restrictions.',                   1),
        (m_id, 'severity', 3, 'Major',       'Major',       NULL, 'Significant reduction in safety margin. Injury to persons.',                            2),
        (m_id, 'severity', 4, 'Hazardous',   'Hazardous',   NULL, 'Large reduction in safety margin. Serious incident with severe operational impact.',    3),
        (m_id, 'severity', 5, 'Catastrophic','Catastrophic',NULL, 'Aircraft destruction or multiple fatalities.',                                          4),
        (m_id, 'likelihood', 1, 'Improbable', 'Improbable', NULL, 'So unlikely it can almost be considered it will not occur.',                            0),
        (m_id, 'likelihood', 2, 'Remote',     'Remote',     NULL, 'Unlikely to occur. Has not occurred in similar operations.',                            1),
        (m_id, 'likelihood', 3, 'Occasional', 'Occasional', NULL, 'Unlikely but possible. Has occurred in similar operations.',                            2),
        (m_id, 'likelihood', 4, 'Probable',   'Probable',   NULL, 'Likely to occur many times. Has occurred frequently.',                                  3),
        (m_id, 'likelihood', 5, 'Frequent',   'Frequent',   NULL, 'Likely to occur many times. Expected to occur regularly under normal operations.',       4);

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'I',   'Acceptable',                1, 5,  '#27ae60',
        'Acceptable', 'Risk is acceptable. No further action required beyond routine monitoring.', 0)
    RETURNING id INTO b_acc;

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'II',  'Acceptable with Review',    6, 10, '#f1c40f',
        'Acceptable with Review', 'Risk may be acceptable. Requires review by accountable manager. Document controls and monitoring measures.', 1)
    RETURNING id INTO b_rev;

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'III', 'Mitigation Required',       11, 15, '#e67e22',
        'Mitigation Required', 'Risk must be mitigated. Activity requires safety mitigations to be implemented and verified before proceeding.', 2)
    RETURNING id INTO b_mit;

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'IV',  'Unacceptable',              16, 25, '#c0392b',
        'Unacceptable', 'Risk is unacceptable. Activity must not proceed. Requires immediate executive or safety authority action.', 3)
    RETURNING id INTO b_una;

    INSERT INTO matrix_cells (matrix_id, severity_value, likelihood_value, risk_band_id, risk_category, colour_hex, numeric_score) VALUES
        (m_id, 1, 1, b_acc, 'Acceptable',             '#27ae60', 1),
        (m_id, 1, 2, b_acc, 'Acceptable',             '#27ae60', 2),
        (m_id, 1, 3, b_acc, 'Acceptable',             '#27ae60', 3),
        (m_id, 1, 4, b_acc, 'Acceptable',             '#27ae60', 4),
        (m_id, 1, 5, b_acc, 'Acceptable',             '#27ae60', 5),
        (m_id, 2, 1, b_acc, 'Acceptable',             '#27ae60', 2),
        (m_id, 2, 2, b_acc, 'Acceptable',             '#27ae60', 4),
        (m_id, 2, 3, b_rev, 'Acceptable with Review', '#f1c40f', 6),
        (m_id, 2, 4, b_rev, 'Acceptable with Review', '#f1c40f', 8),
        (m_id, 2, 5, b_rev, 'Acceptable with Review', '#f1c40f', 10),
        (m_id, 3, 1, b_acc, 'Acceptable',             '#27ae60', 3),
        (m_id, 3, 2, b_rev, 'Acceptable with Review', '#f1c40f', 6),
        (m_id, 3, 3, b_rev, 'Acceptable with Review', '#f1c40f', 9),
        (m_id, 3, 4, b_mit, 'Mitigation Required',    '#e67e22', 12),
        (m_id, 3, 5, b_mit, 'Mitigation Required',    '#e67e22', 15),
        (m_id, 4, 1, b_acc, 'Acceptable',             '#27ae60', 4),
        (m_id, 4, 2, b_rev, 'Acceptable with Review', '#f1c40f', 8),
        (m_id, 4, 3, b_mit, 'Mitigation Required',    '#e67e22', 12),
        (m_id, 4, 4, b_una, 'Unacceptable',           '#c0392b', 16),
        (m_id, 4, 5, b_una, 'Unacceptable',           '#c0392b', 20),
        (m_id, 5, 1, b_acc, 'Acceptable',             '#27ae60', 5),
        (m_id, 5, 2, b_mit, 'Mitigation Required',    '#e67e22', 10),
        (m_id, 5, 3, b_mit, 'Mitigation Required',    '#e67e22', 15),
        (m_id, 5, 4, b_una, 'Unacceptable',           '#c0392b', 20),
        (m_id, 5, 5, b_una, 'Unacceptable',           '#c0392b', 25);

    INSERT INTO matrix_consequence_categories (matrix_id, name, sort_order) VALUES
        (m_id, 'Aviation Safety', 0) RETURNING id INTO c_saf;

    INSERT INTO matrix_level_category_descriptions (matrix_id, severity_level_value, category_id, description) VALUES
        (m_id, 1, c_saf, 'No safety effect. Nuisance only.'),
        (m_id, 2, c_saf, 'Slight reduction in safety margin. Flight operations unaffected.'),
        (m_id, 3, c_saf, 'Significant reduction in safety margin. Physical distress to crew. Possible injury.'),
        (m_id, 4, c_saf, 'Large reduction in safety margin. Serious or fatal injury. Unsafe operation of aircraft.'),
        (m_id, 5, c_saf, 'Aircraft destruction. Multiple fatalities.');
END $m6$;


-- ── 7. NORSOK Z-013 5×5 ───────────────────────────────────────────────────────
DO $m7$
DECLARE
    m_id   INTEGER;
    b_low  INTEGER;
    b_mod  INTEGER;
    b_high INTEGER;
    b_int  INTEGER;
BEGIN
    IF EXISTS (SELECT 1 FROM risk_matrices WHERE name = 'NORSOK Z-013 5×5' AND is_system) THEN RETURN; END IF;

    INSERT INTO risk_matrices (owner_id, name, description, severity_axis_label, likelihood_axis_label, is_public, is_system)
    VALUES (NULL, 'NORSOK Z-013 5×5',
        'Norwegian offshore standard quantitative risk assessment matrix. Mandatory on Norwegian offshore platforms. Uses frequency-based likelihood estimates and lettered consequence scale A–E.',
        'Consequence', 'Frequency', TRUE, TRUE)
    RETURNING id INTO m_id;

    INSERT INTO matrix_levels (matrix_id, axis, level_value, label, one_word, quantitative_range, description, sort_order) VALUES
        (m_id, 'severity', 1, 'A', 'None',     NULL, 'No injury. No harm to persons or environment.',                              0),
        (m_id, 'severity', 2, 'B', 'Minor',    NULL, 'Minor injury. No lost time. Small local environmental release.',             1),
        (m_id, 'severity', 3, 'C', 'Moderate', NULL, 'Medical treatment or restricted duty. Moderate environmental release.',      2),
        (m_id, 'severity', 4, 'D', 'Fatal',    NULL, 'Single fatality. Major environmental damage.',                              3),
        (m_id, 'severity', 5, 'E', 'Disaster', NULL, 'Multiple fatalities. Widespread and long-term environmental harm.',          4),
        (m_id, 'likelihood', 1, 'Very Rare',  'Very Rare',  '< 1×10⁻⁵/year',              'Extremely unlikely. Less than 1 in 100,000 per year.',           0),
        (m_id, 'likelihood', 2, 'Rare',       'Rare',       '1×10⁻⁵ – 1×10⁻⁴/year',      'Rarely occurs. 1 in 10,000 to 100,000 per year.',               1),
        (m_id, 'likelihood', 3, 'Occasional', 'Occasional', '1×10⁻⁴ – 1×10⁻³/year',      'Occasional occurrence. 1 in 1,000 to 10,000 per year.',          2),
        (m_id, 'likelihood', 4, 'Likely',     'Likely',     '1×10⁻³ – 1×10⁻²/year',      'Expected to occur during operating life. 1 in 100 to 1,000/yr.', 3),
        (m_id, 'likelihood', 5, 'Frequent',   'Frequent',   '> 1×10⁻²/year',              'Occurs frequently. More than 1 in 100 per year.',               4);

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'I',   'Low',         1, 5,  '#27ae60',
        'Low (Acceptable)', 'Risk is acceptable. Manage with standard safety management procedures.', 0)
    RETURNING id INTO b_low;

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'II',  'Moderate',    6, 10, '#f1c40f',
        'Moderate (ALARP)', 'Risk is in the ALARP region. Cost-benefit ALARP justification and additional controls required.', 1)
    RETURNING id INTO b_mod;

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'III', 'High',        11, 20, '#e67e22',
        'High', 'Risk must be reduced. Controls are essential. Do not proceed until risk is reduced to ALARP or below.', 2)
    RETURNING id INTO b_high;

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'IV',  'Intolerable', 21, 25, '#c0392b',
        'Intolerable', 'Risk must be eliminated or avoided. Cannot be justified on any grounds. Activity must not proceed.', 3)
    RETURNING id INTO b_int;

    INSERT INTO matrix_cells (matrix_id, severity_value, likelihood_value, risk_band_id, risk_category, colour_hex, numeric_score) VALUES
        (m_id, 1, 1, b_low,  'Low',         '#27ae60', 1),
        (m_id, 1, 2, b_low,  'Low',         '#27ae60', 2),
        (m_id, 1, 3, b_low,  'Low',         '#27ae60', 3),
        (m_id, 1, 4, b_low,  'Low',         '#27ae60', 4),
        (m_id, 1, 5, b_low,  'Low',         '#27ae60', 5),
        (m_id, 2, 1, b_low,  'Low',         '#27ae60', 2),
        (m_id, 2, 2, b_low,  'Low',         '#27ae60', 4),
        (m_id, 2, 3, b_mod,  'Moderate',    '#f1c40f', 6),
        (m_id, 2, 4, b_mod,  'Moderate',    '#f1c40f', 8),
        (m_id, 2, 5, b_mod,  'Moderate',    '#f1c40f', 10),
        (m_id, 3, 1, b_low,  'Low',         '#27ae60', 3),
        (m_id, 3, 2, b_mod,  'Moderate',    '#f1c40f', 6),
        (m_id, 3, 3, b_mod,  'Moderate',    '#f1c40f', 9),
        (m_id, 3, 4, b_high, 'High',        '#e67e22', 12),
        (m_id, 3, 5, b_high, 'High',        '#e67e22', 15),
        (m_id, 4, 1, b_low,  'Low',         '#27ae60', 4),
        (m_id, 4, 2, b_mod,  'Moderate',    '#f1c40f', 8),
        (m_id, 4, 3, b_high, 'High',        '#e67e22', 12),
        (m_id, 4, 4, b_high, 'High',        '#e67e22', 16),
        (m_id, 4, 5, b_high, 'High',        '#e67e22', 20),
        (m_id, 5, 1, b_low,  'Low',         '#27ae60', 5),
        (m_id, 5, 2, b_mod,  'Moderate',    '#f1c40f', 10),
        (m_id, 5, 3, b_high, 'High',        '#e67e22', 15),
        (m_id, 5, 4, b_high, 'High',        '#e67e22', 20),
        (m_id, 5, 5, b_int,  'Intolerable', '#c0392b', 25);
END $m7$;


-- ── 8. HSE UK Offshore 5×5 ────────────────────────────────────────────────────
DO $m8$
DECLARE
    m_id   INTEGER;
    b_ba   INTEGER;
    b_alp  INTEGER;
    b_una  INTEGER;
BEGIN
    IF EXISTS (SELECT 1 FROM risk_matrices WHERE name = 'HSE UK Offshore 5×5' AND is_system) THEN RETURN; END IF;

    INSERT INTO risk_matrices (owner_id, name, description, severity_axis_label, likelihood_axis_label, is_public, is_system)
    VALUES (NULL, 'HSE UK Offshore 5×5',
        'UK Health & Safety Executive matrix for demonstrating ALARP (As Low As Reasonably Practicable). Used in COMAH safety cases and offshore regulatory compliance.',
        'Severity', 'Likelihood', TRUE, TRUE)
    RETURNING id INTO m_id;

    INSERT INTO matrix_levels (matrix_id, axis, level_value, label, one_word, quantitative_range, description, sort_order) VALUES
        (m_id, 'severity', 1, 'Negligible',  'Negligible',  NULL, 'No injury. No environmental release. No operational impact.',                    0),
        (m_id, 'severity', 2, 'Minor',       'Minor',       NULL, 'First aid only. Minor isolated environmental release. Short downtime.',           1),
        (m_id, 'severity', 3, 'Moderate',    'Moderate',    NULL, 'Major injuries. Localised off-site environmental release. Moderate business hit.', 2),
        (m_id, 'severity', 4, 'Major',       'Major',       NULL, 'Single fatality. Significant off-site environmental impact. Major business loss.',  3),
        (m_id, 'severity', 5, 'Catastrophic','Catastrophic',NULL, 'Multiple fatalities. Catastrophic environmental damage. Business-threatening loss.',4),
        (m_id, 'likelihood', 1, 'Remote',    'Remote',    '< 10⁻⁵/year', 'Rarely occurs. Frequency less than 1 in 100,000 per year.',            0),
        (m_id, 'likelihood', 2, 'Unlikely',  'Unlikely',  '10⁻⁵ – 10⁻⁴/year', 'Infrequent. 1 in 10,000 to 100,000 per year.',                1),
        (m_id, 'likelihood', 3, 'Possible',  'Possible',  '10⁻⁴ – 10⁻³/year', 'Could occur occasionally. 1 in 1,000 to 10,000 per year.',     2),
        (m_id, 'likelihood', 4, 'Likely',    'Likely',    '10⁻³ – 10⁻²/year', 'Could happen regularly. 1 in 100 to 1,000 per year.',          3),
        (m_id, 'likelihood', 5, 'Frequent',  'Frequent',  '> 10⁻²/year',       'Happens often. More than 1 in 100 per year.',                  4);

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'I',   'Broadly Acceptable', 1, 6,  '#27ae60',
        'Broadly Acceptable', 'Risk is negligible or broadly acceptable. Manage with standard HSE procedures. Review at normal audit cycle.', 0)
    RETURNING id INTO b_ba;

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'II',  'ALARP Zone',         7, 15, '#f1c40f',
        'ALARP Zone', 'Risk is tolerable only if reduction is impracticable or the costs grossly disproportionate to the benefits. Must demonstrate ALARP.', 1)
    RETURNING id INTO b_alp;

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'III', 'Unacceptable',       16, 25, '#c0392b',
        'Unacceptable', 'Risk cannot be justified except in extraordinary circumstances. Activity must be halted and risk eliminated or fundamentally reduced.', 2)
    RETURNING id INTO b_una;

    INSERT INTO matrix_cells (matrix_id, severity_value, likelihood_value, risk_band_id, risk_category, colour_hex, numeric_score) VALUES
        (m_id, 1, 1, b_ba,  'Broadly Acceptable', '#27ae60', 1),
        (m_id, 1, 2, b_ba,  'Broadly Acceptable', '#27ae60', 2),
        (m_id, 1, 3, b_ba,  'Broadly Acceptable', '#27ae60', 3),
        (m_id, 1, 4, b_ba,  'Broadly Acceptable', '#27ae60', 4),
        (m_id, 1, 5, b_ba,  'Broadly Acceptable', '#27ae60', 5),
        (m_id, 2, 1, b_ba,  'Broadly Acceptable', '#27ae60', 2),
        (m_id, 2, 2, b_ba,  'Broadly Acceptable', '#27ae60', 4),
        (m_id, 2, 3, b_ba,  'Broadly Acceptable', '#27ae60', 6),
        (m_id, 2, 4, b_alp, 'ALARP Zone',         '#f1c40f', 8),
        (m_id, 2, 5, b_alp, 'ALARP Zone',         '#f1c40f', 10),
        (m_id, 3, 1, b_ba,  'Broadly Acceptable', '#27ae60', 3),
        (m_id, 3, 2, b_ba,  'Broadly Acceptable', '#27ae60', 6),
        (m_id, 3, 3, b_alp, 'ALARP Zone',         '#f1c40f', 9),
        (m_id, 3, 4, b_alp, 'ALARP Zone',         '#f1c40f', 12),
        (m_id, 3, 5, b_alp, 'ALARP Zone',         '#f1c40f', 15),
        (m_id, 4, 1, b_ba,  'Broadly Acceptable', '#27ae60', 4),
        (m_id, 4, 2, b_alp, 'ALARP Zone',         '#f1c40f', 8),
        (m_id, 4, 3, b_alp, 'ALARP Zone',         '#f1c40f', 12),
        (m_id, 4, 4, b_una, 'Unacceptable',        '#c0392b', 16),
        (m_id, 4, 5, b_una, 'Unacceptable',        '#c0392b', 20),
        (m_id, 5, 1, b_ba,  'Broadly Acceptable', '#27ae60', 5),
        (m_id, 5, 2, b_alp, 'ALARP Zone',         '#f1c40f', 10),
        (m_id, 5, 3, b_alp, 'ALARP Zone',         '#f1c40f', 15),
        (m_id, 5, 4, b_una, 'Unacceptable',        '#c0392b', 20),
        (m_id, 5, 5, b_una, 'Unacceptable',        '#c0392b', 25);
END $m8$;


-- ── 9. NFPA Fire Risk 5×3 ─────────────────────────────────────────────────────
DO $m9$
DECLARE
    m_id   INTEGER;
    b_low  INTEGER;
    b_med  INTEGER;
    b_high INTEGER;
BEGIN
    IF EXISTS (SELECT 1 FROM risk_matrices WHERE name = 'NFPA Fire Risk 5×3' AND is_system) THEN RETURN; END IF;

    INSERT INTO risk_matrices (owner_id, name, description, severity_axis_label, likelihood_axis_label, is_public, is_system)
    VALUES (NULL, 'NFPA Fire Risk 5×3',
        'Simplified fire hazard matrix used in U.S. facility safety, hot work planning and permit-to-work programs. Five severity levels with three likelihood categories (1–15 scoring).',
        'Severity', 'Likelihood', TRUE, TRUE)
    RETURNING id INTO m_id;

    INSERT INTO matrix_levels (matrix_id, axis, level_value, label, one_word, quantitative_range, description, sort_order) VALUES
        (m_id, 'severity', 1, 'Minor',        'Minor',        NULL, 'Minimal fire damage. No injuries. Contained to point of origin.',                              0),
        (m_id, 'severity', 2, 'Moderate',     'Moderate',     NULL, 'Local damage, some smoke. Minor injuries possible. Extinguished rapidly.',                      1),
        (m_id, 'severity', 3, 'Serious',      'Serious',      NULL, 'Equipment destroyed, serious emergency response required. Injuries probable.',                  2),
        (m_id, 'severity', 4, 'Severe',       'Severe',       NULL, 'Large fire. Major downtime and structural damage. Multiple injuries possible.',                  3),
        (m_id, 'severity', 5, 'Catastrophic', 'Catastrophic', NULL, 'Facility loss or major structural collapse. Fatality possible. Extended business interruption.', 4),
        (m_id, 'likelihood', 1, 'Rare',       'Rare',         NULL, 'Very unlikely ignition conditions. Strong existing fire prevention controls.',                   0),
        (m_id, 'likelihood', 2, 'Occasional', 'Occasional',   NULL, 'May occur during maintenance, hot work or abnormal operations.',                                 1),
        (m_id, 'likelihood', 3, 'Frequent',   'Frequent',     NULL, 'Ignition conditions regularly present. Combustibles or ignition sources frequently exposed.',    2);

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'I',   'Low',    1, 3,  '#27ae60',
        'Low Fire Risk', 'Acceptable fire risk. Manage with standard fire prevention procedures and routine inspection.', 0)
    RETURNING id INTO b_low;

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'II',  'Medium', 4, 6,  '#f1c40f',
        'Medium Fire Risk', 'Requires additional fire prevention and detection controls. Hot work permit required. Review controls before activity.', 1)
    RETURNING id INTO b_med;

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'III', 'High',   7, 15, '#c0392b',
        'High Fire Risk', 'Unacceptable without major additional fire prevention controls. Requires fire watch, engineering controls and management approval.', 2)
    RETURNING id INTO b_high;

    INSERT INTO matrix_cells (matrix_id, severity_value, likelihood_value, risk_band_id, risk_category, colour_hex, numeric_score) VALUES
        (m_id, 1, 1, b_low,  'Low',    '#27ae60', 1),
        (m_id, 1, 2, b_low,  'Low',    '#27ae60', 2),
        (m_id, 1, 3, b_low,  'Low',    '#27ae60', 3),
        (m_id, 2, 1, b_low,  'Low',    '#27ae60', 2),
        (m_id, 2, 2, b_med,  'Medium', '#f1c40f', 4),
        (m_id, 2, 3, b_med,  'Medium', '#f1c40f', 6),
        (m_id, 3, 1, b_low,  'Low',    '#27ae60', 3),
        (m_id, 3, 2, b_med,  'Medium', '#f1c40f', 6),
        (m_id, 3, 3, b_high, 'High',   '#c0392b', 9),
        (m_id, 4, 1, b_med,  'Medium', '#f1c40f', 4),
        (m_id, 4, 2, b_high, 'High',   '#c0392b', 8),
        (m_id, 4, 3, b_high, 'High',   '#c0392b', 12),
        (m_id, 5, 1, b_med,  'Medium', '#f1c40f', 5),
        (m_id, 5, 2, b_high, 'High',   '#c0392b', 10),
        (m_id, 5, 3, b_high, 'High',   '#c0392b', 15);
END $m9$;


-- ── 10. U.S. Army ATP 5-19 4×5 ────────────────────────────────────────────────
DO $m10$
DECLARE
    m_id   INTEGER;
    b_low  INTEGER;
    b_mod  INTEGER;
    b_high INTEGER;
    b_eh   INTEGER;
BEGIN
    IF EXISTS (SELECT 1 FROM risk_matrices WHERE name = 'U.S. Army ATP 5-19 4×5' AND is_system) THEN RETURN; END IF;

    INSERT INTO risk_matrices (owner_id, name, description, severity_axis_label, likelihood_axis_label, is_public, is_system)
    VALUES (NULL, 'U.S. Army ATP 5-19 4×5',
        'U.S. Army risk management matrix per ATP 5-19. Uses ordinal severity and likelihood scales. Documented on DD Form 2977 for mission and training risk planning.',
        'Severity', 'Probability', TRUE, TRUE)
    RETURNING id INTO m_id;

    INSERT INTO matrix_levels (matrix_id, axis, level_value, label, one_word, quantitative_range, description, sort_order) VALUES
        (m_id, 'severity', 1, 'Negligible',  'Negligible',  NULL, 'First aid or minor administrative inconvenience. No mission degradation.',                      0),
        (m_id, 'severity', 2, 'Marginal',    'Marginal',    NULL, 'Minor injury or minor system degradation. Mission accomplishable with degraded capability.',      1),
        (m_id, 'severity', 3, 'Critical',    'Critical',    NULL, 'Permanent partial disability, significant damage or mission-critical degradation.',               2),
        (m_id, 'severity', 4, 'Catastrophic','Catastrophic',NULL, 'Death or major system loss. Mission failure.',                                                    3),
        (m_id, 'likelihood', 1, 'Unlikely',  'Unlikely',    NULL, 'Very unlikely. Assume will not occur except in rare circumstances.',                              0),
        (m_id, 'likelihood', 2, 'Seldom',    'Seldom',      NULL, 'Unlikely to occur but possible. May occur under adverse conditions.',                             1),
        (m_id, 'likelihood', 3, 'Occasional','Occasional',  NULL, 'Likely to occur at some time. Has occurred in similar operations.',                               2),
        (m_id, 'likelihood', 4, 'Likely',    'Likely',      NULL, 'Will occur several times during mission or activity lifecycle.',                                  3),
        (m_id, 'likelihood', 5, 'Frequent',  'Frequent',    NULL, 'Occurs very often. Continuously experienced under normal conditions.',                            4);

    -- Ordinal bands — score_min/max used for ordering
    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'L',  'Low',            1, 1, '#27ae60',
        'Low', 'Residual risk is acceptable. Proceed with normal supervision. Commander/supervisor can approve.', 0)
    RETURNING id INTO b_low;

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'M',  'Moderate',       2, 2, '#f1c40f',
        'Moderate', 'Company or battalion commander decision required. Additional controls must be implemented and verified.', 1)
    RETURNING id INTO b_mod;

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'H',  'High',           3, 3, '#e67e22',
        'High', 'Risk decision required at brigade, group or equivalent level. Significant controls required.', 2)
    RETURNING id INTO b_high;

    INSERT INTO matrix_risk_bands (matrix_id, band_label, band_name, score_min, score_max, colour_hex, short_description, full_description, sort_order)
    VALUES (m_id, 'EH', 'Extremely High', 4, 4, '#c0392b',
        'Extremely High', 'Mission failure likely. Risk decision at division or corps level. Activity must not proceed without explicit command authorisation and comprehensive controls.', 3)
    RETURNING id INTO b_eh;

    -- Ordinal cell assignments per ATP 5-19 doctrine
    -- numeric_score = ordinal rank (1=L, 2=M, 3=H, 4=EH) for ordering
    INSERT INTO matrix_cells (matrix_id, severity_value, likelihood_value, risk_band_id, risk_category, colour_hex, numeric_score) VALUES
        -- Negligible severity
        (m_id, 1, 1, b_low,  'Low',            '#27ae60', 1),
        (m_id, 1, 2, b_low,  'Low',            '#27ae60', 1),
        (m_id, 1, 3, b_low,  'Low',            '#27ae60', 1),
        (m_id, 1, 4, b_low,  'Low',            '#27ae60', 1),
        (m_id, 1, 5, b_mod,  'Moderate',       '#f1c40f', 2),
        -- Marginal severity
        (m_id, 2, 1, b_low,  'Low',            '#27ae60', 1),
        (m_id, 2, 2, b_low,  'Low',            '#27ae60', 1),
        (m_id, 2, 3, b_mod,  'Moderate',       '#f1c40f', 2),
        (m_id, 2, 4, b_mod,  'Moderate',       '#f1c40f', 2),
        (m_id, 2, 5, b_high, 'High',           '#e67e22', 3),
        -- Critical severity
        (m_id, 3, 1, b_low,  'Low',            '#27ae60', 1),
        (m_id, 3, 2, b_mod,  'Moderate',       '#f1c40f', 2),
        (m_id, 3, 3, b_high, 'High',           '#e67e22', 3),
        (m_id, 3, 4, b_high, 'High',           '#e67e22', 3),
        (m_id, 3, 5, b_eh,   'Extremely High', '#c0392b', 4),
        -- Catastrophic severity
        (m_id, 4, 1, b_mod,  'Moderate',       '#f1c40f', 2),
        (m_id, 4, 2, b_high, 'High',           '#e67e22', 3),
        (m_id, 4, 3, b_eh,   'Extremely High', '#c0392b', 4),
        (m_id, 4, 4, b_eh,   'Extremely High', '#c0392b', 4),
        (m_id, 4, 5, b_eh,   'Extremely High', '#c0392b', 4);
END $m10$;
