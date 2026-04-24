/**
 * Assessment Editor — vanilla JS.
 *
 * Bootstrapped from window.__EDITOR_CONFIG__ set in the editor template.
 * Depends on SortableJS (loaded before this file via CDN).
 *
 * State management strategy:
 *   - All edits are stored immediately to localStorage.
 *   - Server sync happens on: manual Save, page unload, tab visibility hidden.
 *   - On page load, if localStorage has a newer state than the server snapshot,
 *     a banner is shown letting the user keep local or reload from server.
 */

(function () {
    'use strict';

    var cfg = window.__EDITOR_CONFIG__;
    if (!cfg) return; // not on the editor page

    // ── State ─────────────────────────────────────────────────────────────────

    var STORAGE_KEY   = 'assessment_' + cfg.assessmentId + '_state';
    var state         = { rows: [], deletedIds: [] };
    var isDirty       = false;
    var isSaving      = false;
    var nextTempId    = -1;  // negative IDs for rows not yet saved to server
    var canEdit       = cfg.canEdit;

    // ── Helpers ───────────────────────────────────────────────────────────────

    function intOrNull(v) {
        var n = parseInt(v, 10);
        return (isNaN(n) || n <= 0) ? null : n;
    }

    function boolOrNull(v) {
        if (v === null || v === undefined || v === '') return null;
        return !!v;
    }

    function nullStr(v) {
        if (v === null || v === undefined) return null;
        var s = String(v).trim();
        return s === '' ? null : s;
    }

    function deepClone(obj) {
        return JSON.parse(JSON.stringify(obj));
    }

    /** Look up the risk cell by severity + likelihood from the preloaded matrix. */
    function lookupCell(s, l) {
        if (!s || !l) return null;
        return cfg.matrixCells[s + '_' + l] || null;
    }

    /**
     * Determine a readable text colour (black or white) that contrasts
     * sufficiently with the given hex background.
     */
    function contrastColor(hex) {
        if (!hex || hex.length < 7) return '#363636';
        var r = parseInt(hex.slice(1, 3), 16);
        var g = parseInt(hex.slice(3, 5), 16);
        var b = parseInt(hex.slice(5, 7), 16);
        var lum = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
        return lum > 0.55 ? '#363636' : '#ffffff';
    }

    // ── localStorage ──────────────────────────────────────────────────────────

    function saveToStorage() {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        } catch (e) { /* quota exceeded — ignore */ }
        markDirty(true);
    }

    function loadFromStorage() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            if (raw) return JSON.parse(raw);
        } catch (e) { /* corrupted — ignore */ }
        return null;
    }

    function clearStorage() {
        localStorage.removeItem(STORAGE_KEY);
    }

    // ── Dirty indicator ───────────────────────────────────────────────────────

    function markDirty(dirty) {
        isDirty = dirty;
        var el = document.getElementById('save-indicator');
        if (!el) return;
        if (dirty) {
            el.textContent = 'Unsaved changes';
            el.className   = 'tag is-warning';
        } else {
            el.textContent = 'Saved';
            el.className   = 'tag is-success';
        }
    }

    // ── Sticky header fix ────────────────────────────────────────────────────
    /**
     * Measure the rendered height of the first thead row (#assessment-thead)
     * and apply that as the CSS top value for the sub-header row (#assessment-subhead).
     * This is more reliable than the hard-coded "2rem" in the stylesheet because
     * the actual pixel height depends on fonts, zoom level, and browser defaults.
     */
    function fixStickySubhead() {
        var row1 = document.getElementById('assessment-thead');
        var row2 = document.getElementById('assessment-subhead');
        if (!row1 || !row2) return;
        var h = row1.getBoundingClientRect().height;
        if (h > 0) {
            row2.querySelectorAll('th').forEach(function (th) {
                th.style.top = h + 'px';
            });
        }
    }

    // ── Row model helpers ─────────────────────────────────────────────────────

    function emptyRow() {
        return {
            id                       : nextTempId--,
            sort_order               : state.rows.length,
            activity_condition       : null,
            hazard                   : null,
            exposure_description     : null,
            exposed_assets           : null,
            effect                   : null,
            existing_controls        : [],   // array of {description, control_type}
            natural_severity_value   : null,
            natural_likelihood_value : null,
            natural_risk_category    : null,
            natural_colour_hex       : null,
            natural_risk_accept      : null,
            severity_value           : null,
            likelihood_value         : null,
            risk_category            : null,
            colour_hex               : null,
            current_risk_accept      : null,
            proposed_controls        : [],   // array of {description, control_type}
            residual_severity_value  : null,
            residual_likelihood_value: null,
            residual_risk_category   : null,
            residual_colour_hex      : null,
            residual_risk_accept     : null,
            comments                 : null
        };
    }

    /**
     * Ensure a row uses the new array format for controls.
     * Migrates old localStorage data where controls were plain strings.
     */
    function normalizeRow(row) {
        if (!Array.isArray(row.existing_controls)) {
            var desc = (typeof row.existing_controls === 'string') ? row.existing_controls.trim() : '';
            var type = row.existing_controls_type || null;
            row.existing_controls = desc ? [{ description: desc, control_type: type }] : [];
        }
        delete row.existing_controls_type;

        if (!Array.isArray(row.proposed_controls)) {
            var pdesc = (typeof row.proposed_controls === 'string') ? row.proposed_controls.trim() : '';
            row.proposed_controls = pdesc ? [{ description: pdesc, control_type: null }] : [];
        }
        return row;
    }

    function updateRiskFields(row, prefix) {
        var sKey   = prefix === 'current' ? 'severity_value'   : prefix + '_severity_value';
        var lKey   = prefix === 'current' ? 'likelihood_value' : prefix + '_likelihood_value';
        var catKey = prefix === 'current' ? 'risk_category'    : prefix + '_risk_category';
        var hexKey = prefix === 'current' ? 'colour_hex'       : prefix + '_colour_hex';

        var cell = lookupCell(row[sKey], row[lKey]);
        row[catKey] = cell ? cell.risk_category : null;
        row[hexKey] = cell ? cell.colour_hex    : null;
    }

    // ── DOM builders ──────────────────────────────────────────────────────────

    function makeInput(row, field, placeholder) {
        var td    = document.createElement('td');
        var input = document.createElement('input');
        input.type        = 'text';
        input.className   = 'input is-small';
        input.value       = row[field] || '';
        input.placeholder = placeholder || '';
        if (!canEdit) {
            input.readOnly = true;
        } else {
            input.addEventListener('change', function () {
                row[field] = nullStr(input.value);
                saveToStorage();
            });
        }
        td.appendChild(input);
        return td;
    }

    function makeTextarea(row, field, placeholder) {
        var td = document.createElement('td');
        var ta = document.createElement('textarea');
        ta.className   = 'textarea is-small';
        ta.rows        = 1;
        ta.value       = row[field] || '';
        ta.placeholder = placeholder || '';
        if (!canEdit) {
            ta.readOnly = true;
        } else {
            ta.addEventListener('change', function () {
                row[field] = nullStr(ta.value);
                saveToStorage();
            });
        }
        td.appendChild(ta);
        return td;
    }

    function makeSeveritySelect(row, field, callback) {
        return makeLevelSelect(row, field, cfg.severityLevels, 'S', callback);
    }

    function makeLikelihoodSelect(row, field, callback) {
        return makeLevelSelect(row, field, cfg.likelihoodLevels, 'L', callback);
    }

    function makeLevelSelect(row, field, levels, placeholder, callback) {
        var td  = document.createElement('td');
        var sel = document.createElement('select');
        sel.className = 'select is-small is-level-select';

        var optBlank = document.createElement('option');
        optBlank.value = '';
        optBlank.textContent = placeholder;
        sel.appendChild(optBlank);

        levels.forEach(function (lev) {
            var opt = document.createElement('option');
            opt.value       = lev.level_value;
            opt.textContent = lev.level_value + ' — ' + lev.label;
            if (String(row[field]) === String(lev.level_value)) opt.selected = true;
            sel.appendChild(opt);
        });

        if (!canEdit) {
            sel.disabled = true;
        } else {
            sel.addEventListener('change', function () {
                row[field] = intOrNull(sel.value);
                if (callback) callback();
                saveToStorage();
            });
        }
        td.appendChild(sel);
        return td;
    }

    function makeControlTypeSelect(row, field) {
        var td  = document.createElement('td');
        var sel = document.createElement('select');
        sel.className = 'select is-small';

        var optBlank = document.createElement('option');
        optBlank.value = '';
        optBlank.textContent = '— Type —';
        sel.appendChild(optBlank);

        Object.keys(cfg.controlTypes).forEach(function (key) {
            var opt = document.createElement('option');
            opt.value       = key;
            opt.textContent = cfg.controlTypes[key];
            if (row[field] === key) opt.selected = true;
            sel.appendChild(opt);
        });

        if (!canEdit) {
            sel.disabled = true;
        } else {
            sel.addEventListener('change', function () {
                row[field] = sel.value || null;
                saveToStorage();
            });
        }
        td.appendChild(sel);
        return td;
    }

    /**
     * Build a table cell containing a list of control measures for one phase.
     * Each item has a description textarea, an optional type select, and a remove button.
     * An "Add" button appends a new blank item to the list.
     */
    function makeControlsCell(row, phase, showType) {
        var field = phase === 'existing' ? 'existing_controls' : 'proposed_controls';
        var td = document.createElement('td');
        td.className = 'controls-td';

        var list = document.createElement('div');
        list.className = 'control-list';
        td.appendChild(list);

        function renderItems() {
            list.innerHTML = '';
            var controls = row[field];
            if (!Array.isArray(controls)) controls = [];

            controls.forEach(function (ctrl, idx) {
                var item = document.createElement('div');
                item.className = 'control-item';

                var ta = document.createElement('textarea');
                ta.className   = 'textarea is-small';
                ta.rows        = 1;
                ta.value       = ctrl.description || '';
                ta.placeholder = 'Describe control measure…';
                if (!canEdit) {
                    ta.readOnly = true;
                } else {
                    ta.addEventListener('change', function () {
                        row[field][idx].description = ta.value.trim() || '';
                        saveToStorage();
                    });
                }
                item.appendChild(ta);

                if (showType) {
                    var sel = document.createElement('select');
                    sel.className = 'select is-small control-type-select';

                    var optBlank = document.createElement('option');
                    optBlank.value       = '';
                    optBlank.textContent = '— Type —';
                    sel.appendChild(optBlank);

                    Object.keys(cfg.controlTypes).forEach(function (key) {
                        var opt = document.createElement('option');
                        opt.value       = key;
                        opt.textContent = cfg.controlTypes[key];
                        if (ctrl.control_type === key) opt.selected = true;
                        sel.appendChild(opt);
                    });

                    if (!canEdit) {
                        sel.disabled = true;
                    } else {
                        sel.addEventListener('change', function () {
                            row[field][idx].control_type = sel.value || null;
                            saveToStorage();
                        });
                    }
                    item.appendChild(sel);
                }

                if (canEdit) {
                    var delBtn = document.createElement('button');
                    delBtn.type      = 'button';
                    delBtn.className = 'button is-danger-muted is-small control-del-btn';
                    delBtn.title     = 'Remove this control';
                    delBtn.innerHTML = '<span class="icon"><i class="fas fa-times"></i></span>';
                    delBtn.addEventListener('click', function () {
                        row[field].splice(idx, 1);
                        saveToStorage();
                        renderItems();
                    });
                    item.appendChild(delBtn);
                }

                list.appendChild(item);
            });
        }

        renderItems();

        if (canEdit) {
            var addBtn = document.createElement('button');
            addBtn.type      = 'button';
            addBtn.className = 'button is-small is-light add-control-btn';
            addBtn.title     = 'Add a control measure';
            addBtn.innerHTML = '<span class="icon"><i class="fas fa-plus"></i></span><span>Add</span>';
            addBtn.addEventListener('click', function () {
                if (!Array.isArray(row[field])) row[field] = [];
                row[field].push({ description: '', control_type: null });
                saveToStorage();
                renderItems();
            });
            td.appendChild(addBtn);
        }

        return td;
    }

    /**
     * Build the hazard cell.
     * showAddBtn — when true (last row of a named-activity group) renders the
     *              "+ Add Hazard" button; false for all other rows.
     */
    function makeHazardCell(row, showAddBtn) {
        var td = document.createElement('td');
        td.className = 'hazard-td';

        var ta = document.createElement('textarea');
        ta.className   = 'textarea is-small';
        ta.rows        = 1;
        ta.value       = row.hazard || '';
        ta.placeholder = 'Describe the hazard…';
        if (!canEdit) {
            ta.readOnly = true;
        } else {
            ta.addEventListener('change', function () {
                row.hazard = nullStr(ta.value);
                saveToStorage();
            });
        }
        td.appendChild(ta);

        if (canEdit && showAddBtn) {
            var addBtn = document.createElement('button');
            addBtn.type      = 'button';
            addBtn.className = 'button is-small is-light add-hazard-btn';
            addBtn.title     = 'Add another hazard for the same activity';
            addBtn.innerHTML = '<span class="icon"><i class="fas fa-plus"></i></span><span>Add Hazard</span>';
            addBtn.addEventListener('click', function () {
                addHazardForActivity(row);
            });
            td.appendChild(addBtn);
        }

        return td;
    }

    /** Build the activity/condition cell (just the editable textarea). */
    function makeActivityCell(row) {
        var td = document.createElement('td');
        td.className = 'activity-td';

        var ta = document.createElement('textarea');
        ta.className   = 'textarea is-small';
        ta.rows        = 1;
        ta.value       = row.activity_condition || '';
        ta.placeholder = 'Activity / condition';
        if (!canEdit) {
            ta.readOnly = true;
        } else {
            ta.addEventListener('change', function () {
                row.activity_condition = nullStr(ta.value);
                saveToStorage();
            });
        }
        td.appendChild(ta);

        return td;
    }

    /** Insert a new blank hazard row immediately after `afterRow`, pre-filling the activity. */
    function addHazardForActivity(afterRow) {
        var newRow = emptyRow();
        newRow.activity_condition = afterRow.activity_condition;

        var idx = state.rows.findIndex(function (r) { return r.id === afterRow.id; });
        if (idx === -1) {
            state.rows.push(newRow);
        } else {
            state.rows.splice(idx + 1, 0, newRow);
        }
        state.rows.forEach(function (r, i) { r.sort_order = i; });

        saveToStorage();
        render();

        // Focus the hazard textarea (second textarea) of the newly inserted row
        var tbody = document.getElementById('assessment-tbody');
        if (!tbody) return;
        var newTr = tbody.querySelectorAll('tr')[idx + 1];
        if (newTr) {
            var tas = newTr.querySelectorAll('textarea');
            var target = tas[1] || tas[0]; // [0] = activity, [1] = hazard
            if (target) {
                target.focus();
                target.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
    }

    function makeRiskBadgeTd(row, catField, hexField) {
        var td   = document.createElement('td');
        td.className = 'risk-badge-td';
        td.appendChild(buildBadge(row[catField], row[hexField]));
        td.dataset.catField = catField;
        td.dataset.hexField = hexField;
        td.dataset.rowId    = row.id;
        return td;
    }

    function buildBadge(category, hex) {
        var span = document.createElement('span');
        span.className = 'risk-cell';
        if (category && hex) {
            span.textContent         = category;
            span.style.backgroundColor = hex;
            span.style.color           = contrastColor(hex);
        } else {
            span.textContent         = '—';
            span.style.backgroundColor = '#e0e0e0';
            span.style.color           = '#888';
        }
        return span;
    }

    function refreshBadgesForRow(row) {
        // Update all risk badge TDs for this row in the DOM
        var tbody = document.getElementById('assessment-tbody');
        if (!tbody) return;
        tbody.querySelectorAll('.risk-badge-td[data-row-id="' + row.id + '"]').forEach(function (td) {
            td.innerHTML = '';
            td.appendChild(buildBadge(row[td.dataset.catField], row[td.dataset.hexField]));
        });
    }

    function makeCheckbox(row, field) {
        var td    = document.createElement('td');
        td.className = 'has-text-centered';
        var label = document.createElement('label');
        label.className = 'checkbox';
        var cb = document.createElement('input');
        cb.type    = 'checkbox';
        cb.checked = !!row[field];
        if (!canEdit) {
            cb.disabled = true;
        } else {
            cb.addEventListener('change', function () {
                row[field] = cb.checked;
                saveToStorage();
            });
        }
        label.appendChild(cb);
        td.appendChild(label);
        return td;
    }

    // ── Row rendering ─────────────────────────────────────────────────────────

    /**
     * isGroupFirst: first (or only) row of an activity group — shows editable activity textarea + bracket
     * isGroupMember: subsequent row in a group               — shows empty bracketed activity cell
     * isGroupLast:  last (or only) row of a named-activity group — shows "+ Add Hazard" button
     */
    function renderRow(row, rowNum, isGroupFirst, isGroupMember, isGroupLast) {
        var tr = document.createElement('tr');
        tr.dataset.id = row.id;
        if (isGroupMember) tr.classList.add('is-hazard-sibling');
        if (isGroupLast)   tr.classList.add('is-group-last');

        var cc = cfg.columnConfig;

        // Drag handle — appearance varies by role in a group
        var tdDrag = document.createElement('td');
        if (!canEdit) {
            tdDrag.className = 'drag-col';
        } else if (isGroupFirst && !isGroupLast) {
            // Multi-hazard group leader: drags the whole group
            tdDrag.className = 'drag-handle drag-handle--group';
            tdDrag.title     = 'Drag to move entire activity group';
            tdDrag.innerHTML = '<span class="icon"><i class="fas fa-grip-vertical"></i></span>';
        } else if (isGroupMember) {
            // Hazard sibling: reorder within the group only
            tdDrag.className = 'drag-handle drag-handle--member';
            tdDrag.title     = 'Drag to reorder within this group';
            tdDrag.innerHTML = '<span class="icon"><i class="fas fa-grip-lines"></i></span>';
        } else {
            // Single-hazard row (with or without activity)
            tdDrag.className = 'drag-handle';
            tdDrag.title     = 'Drag to reorder';
            tdDrag.innerHTML = '<span class="icon has-text-grey-light"><i class="fas fa-grip-vertical"></i></span>';
        }
        tr.appendChild(tdDrag);

        // Row number
        var tdNum = document.createElement('td');
        tdNum.className = 'has-text-grey is-size-7 has-text-centered row-num';
        tdNum.textContent = rowNum;
        tr.appendChild(tdNum);

        // Activity column
        if (cc.show_activity_condition) {
            if (isGroupMember) {
                // Bracket continuation — no editable content, just the visual left border
                var tdEmpty = document.createElement('td');
                tdEmpty.className = 'activity-group-member-td';
                tr.appendChild(tdEmpty);
            } else {
                var actTd = makeActivityCell(row);
                if (isGroupFirst) actTd.classList.add('is-group-first');
                tr.appendChild(actTd);
            }
        }
        tr.appendChild(makeHazardCell(row, !!isGroupLast));
        if (cc.show_exposure_description) tr.appendChild(makeTextarea(row, 'exposure_description', 'Exposure description'));
        if (cc.show_exposed_assets)       tr.appendChild(makeTextarea(row, 'exposed_assets', 'Persons / assets at risk'));
        tr.appendChild(makeTextarea(row, 'effect', 'Potential effect'));

        // Natural risk columns
        if (cc.show_natural_risk) {
            var updateNatural = function () {
                updateRiskFields(row, 'natural');
                refreshBadgesForRow(row);
            };
            tr.appendChild(makeSeveritySelect(row, 'natural_severity_value', updateNatural));
            tr.appendChild(makeLikelihoodSelect(row, 'natural_likelihood_value', updateNatural));
            tr.appendChild(makeRiskBadgeTd(row, 'natural_risk_category', 'natural_colour_hex'));
            if (cc.show_accept_yn) tr.appendChild(makeCheckbox(row, 'natural_risk_accept'));
        }

        // Existing controls — multi-item list; type dropdown embedded when show_control_type is on
        tr.appendChild(makeControlsCell(row, 'existing', !!cc.show_control_type));

        // Current risk
        var updateCurrent = function () {
            updateRiskFields(row, 'current');
            refreshBadgesForRow(row);
        };
        tr.appendChild(makeSeveritySelect(row, 'severity_value', updateCurrent));
        tr.appendChild(makeLikelihoodSelect(row, 'likelihood_value', updateCurrent));
        tr.appendChild(makeRiskBadgeTd(row, 'risk_category', 'colour_hex'));
        if (cc.show_accept_yn) tr.appendChild(makeCheckbox(row, 'current_risk_accept'));

        // Proposed controls + residual risk
        if (cc.show_proposed_controls) {
            tr.appendChild(makeControlsCell(row, 'proposed', !!cc.show_control_type));
        }
        if (cc.show_residual_risk) {
            var updateResidual = function () {
                updateRiskFields(row, 'residual');
                refreshBadgesForRow(row);
            };
            tr.appendChild(makeSeveritySelect(row, 'residual_severity_value', updateResidual));
            tr.appendChild(makeLikelihoodSelect(row, 'residual_likelihood_value', updateResidual));
            tr.appendChild(makeRiskBadgeTd(row, 'residual_risk_category', 'residual_colour_hex'));
            if (cc.show_accept_yn) tr.appendChild(makeCheckbox(row, 'residual_risk_accept'));
        }

        // Actions: delete only (Add Hazard is now inside the hazard cell)
        if (canEdit) {
            var tdDel = document.createElement('td');
            var delBtn = document.createElement('button');
            delBtn.type      = 'button';
            delBtn.className = 'button is-danger-muted is-small';
            delBtn.title     = 'Delete row';
            delBtn.innerHTML = '<span class="icon"><i class="fas fa-trash"></i></span>';
            delBtn.addEventListener('click', function () {
                if (!confirm('Delete this row?')) return;
                deleteRow(row);
            });
            tdDel.appendChild(delBtn);
            tr.appendChild(tdDel);
        }

        return tr;
    }

    // ── Full table render ─────────────────────────────────────────────────────

    var sortableInstance = null;

    function render() {
        var tbody = document.getElementById('assessment-tbody');
        if (!tbody) return;

        if (sortableInstance) {
            sortableInstance.destroy();
            sortableInstance = null;
        }

        var cc = cfg.columnConfig;
        var showActivity = !!cc.show_activity_condition;

        tbody.innerHTML = '';
        var rowNum = 0;
        var i = 0;
        while (i < state.rows.length) {
            var row = state.rows[i];
            var activity = row.activity_condition; // may be null

            // Find the span of consecutive rows with the same non-empty activity
            var groupEnd = i;
            if (activity) {
                while (
                    groupEnd + 1 < state.rows.length &&
                    state.rows[groupEnd + 1].activity_condition === activity
                ) {
                    groupEnd++;
                }
            }

            var groupSize = groupEnd - i + 1;

            for (var j = i; j <= groupEnd; j++) {
                rowNum++;
                var isGroupFirst  = showActivity && (j === i) && !!activity;
                var isGroupMember = showActivity && (j !== i);
                // Only the last row of a group gets the "+ Add Hazard" button.
                // We do NOT gate this on !!activity: the button must appear even when the
                // activity field is still blank, because isGroupLast is computed once at
                // render time and typing into the activity textarea does not trigger a
                // re-render.
                var isGroupLast   = showActivity && canEdit && (j === groupEnd);
                tbody.appendChild(renderRow(state.rows[j], rowNum, isGroupFirst, isGroupMember, isGroupLast));
            }

            i = groupEnd + 1;
        }

        updateEmptyState();
        updateRowCountLabel();

        if (canEdit) {
            initSortable(tbody);
        }
    }

    function updateEmptyState() {
        var emptyEl = document.getElementById('empty-state');
        if (!emptyEl) return;
        emptyEl.style.display = state.rows.length === 0 ? '' : 'none';
    }

    function updateRowCountLabel() {
        var el = document.getElementById('row-count-label');
        if (!el) return;
        var n = state.rows.length;
        el.textContent = n + ' row' + (n !== 1 ? 's' : '');
    }

    // ── Row CRUD ──────────────────────────────────────────────────────────────

    function addRow() {
        var row = emptyRow();
        state.rows.push(row);
        saveToStorage();
        render();
        // Focus the hazard field of the new row
        var tbody   = document.getElementById('assessment-tbody');
        var lastTr  = tbody ? tbody.querySelector('tr:last-child') : null;
        var firstIn = lastTr ? lastTr.querySelector('input, textarea') : null;
        if (firstIn) {
            firstIn.focus();
            firstIn.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    function deleteRow(row) {
        if (row.id > 0) {
            state.deletedIds.push(row.id);
        }
        state.rows = state.rows.filter(function (r) { return r.id !== row.id; });
        saveToStorage();
        render();
    }

    // ── Drag-to-reorder ───────────────────────────────────────────────────────

    function initSortable(tbody) {
        if (typeof Sortable === 'undefined') return;

        /** Return the row object for a given <tr> element, or null. */
        function rowForEl(el) {
            if (!el || !el.dataset || !el.dataset.id) return null;
            var id = parseInt(el.dataset.id, 10);
            for (var i = 0; i < state.rows.length; i++) {
                if (state.rows[i].id === id) return state.rows[i];
            }
            return null;
        }

        /**
         * True when this row is a non-first member of an activity group,
         * i.e. the row directly above it shares the same activity_condition.
         */
        function isGroupMemberRow(row) {
            if (!row || !row.activity_condition) return false;
            var idx = state.rows.findIndex(function (r) { return r.id === row.id; });
            if (idx <= 0) return false;
            return state.rows[idx - 1].activity_condition === row.activity_condition;
        }

        /**
         * dragContext is set in onStart when the user begins dragging a
         * multi-hazard group leader.  It records the leader row and all its
         * consecutive sibling rows so onEnd can re-attach them after the drop.
         * null means we are doing an ordinary single-row drag.
         */
        var dragContext = null;

        sortableInstance = Sortable.create(tbody, {
            handle    : '.drag-handle',
            draggable : 'tr',
            animation : 150,
            ghostClass: 'sortable-ghost',

            onStart: function (evt) {
                dragContext = null;
                var draggedRow = rowForEl(evt.item);
                if (!draggedRow || !draggedRow.activity_condition) return;

                var idx = state.rows.findIndex(function (r) { return r.id === draggedRow.id; });
                var prevRow = idx > 0 ? state.rows[idx - 1] : null;
                var isFirst = !prevRow || prevRow.activity_condition !== draggedRow.activity_condition;
                if (!isFirst) return; // sibling being dragged, not the group leader

                // Collect consecutive siblings (rows that immediately follow with the same activity)
                var siblings = [];
                for (var k = idx + 1; k < state.rows.length; k++) {
                    if (state.rows[k].activity_condition === draggedRow.activity_condition) {
                        siblings.push(state.rows[k]);
                    } else {
                        break;
                    }
                }
                if (siblings.length === 0) return; // single-row group — no special handling

                dragContext = { row: draggedRow, siblings: siblings };
            },

            onMove: function (evt) {
                var draggedRow = rowForEl(evt.dragged);
                var relatedRow = rowForEl(evt.related);
                if (!draggedRow || !relatedRow) return true;

                if (dragContext) {
                    // Multi-hazard group leader being dragged.
                    // Block insertion BEFORE a non-first group member (would split another group).
                    // Inserting AFTER a member (evt.willInsertAfter) is fine — it lands after the group.
                    return !(isGroupMemberRow(relatedRow) && !evt.willInsertAfter);
                }

                // Hazard sibling (non-leader): restrict to within the same group only.
                if (isGroupMemberRow(draggedRow)) {
                    return draggedRow.activity_condition === relatedRow.activity_condition;
                }

                // Single-row group or null-activity row: freely movable, but do not
                // allow insertion before a non-first group member (would split a group).
                return !(isGroupMemberRow(relatedRow) && !evt.willInsertAfter);
            },

            onEnd: function () {
                // Re-sync state.rows order from the current DOM order.
                // For a group-leader drag SortableJS only moved the leader element;
                // siblings are still in their original DOM positions.
                var trs = tbody.querySelectorAll('tr');
                var newOrder = [];
                trs.forEach(function (tr) {
                    var id = parseInt(tr.dataset.id, 10);
                    if (!isNaN(id)) newOrder.push(id);
                });
                var rowMap = {};
                state.rows.forEach(function (r) { rowMap[r.id] = r; });
                state.rows = newOrder.map(function (id) { return rowMap[id]; }).filter(Boolean);

                if (dragContext) {
                    // Pull siblings out of wherever they ended up in the re-synced order
                    // and place them immediately after their group leader.
                    var siblingIds = {};
                    dragContext.siblings.forEach(function (s) { siblingIds[s.id] = true; });
                    state.rows = state.rows.filter(function (r) { return !siblingIds[r.id]; });

                    var leaderIdx = state.rows.findIndex(function (r) { return r.id === dragContext.row.id; });
                    if (leaderIdx !== -1) {
                        // Insert siblings in order right after the leader
                        Array.prototype.splice.apply(
                            state.rows,
                            [leaderIdx + 1, 0].concat(dragContext.siblings)
                        );
                    }
                    dragContext = null;
                }

                state.rows.forEach(function (r, i) { r.sort_order = i; });
                saveToStorage();
                render(); // recalculates grouping classes and row numbers
            }
        });
    }

    // ── Server sync ───────────────────────────────────────────────────────────

    function syncToServer(beacon) {
        if (isSaving) return Promise.resolve();
        if (!isDirty && !beacon) return Promise.resolve();
        isSaving = true;

        var payload = JSON.stringify({
            _csrf      : cfg.csrf,
            rows       : state.rows,
            deleted_ids: state.deletedIds
        });

        var url = '/api/assessments/' + cfg.assessmentId + '/sync';

        // Use sendBeacon for unload events (fire-and-forget)
        if (beacon && navigator.sendBeacon) {
            navigator.sendBeacon(url, new Blob([payload], { type: 'application/json' }));
            isSaving = false;
            return Promise.resolve();
        }

        return fetch(url, {
            method : 'POST',
            headers: { 'Content-Type': 'application/json' },
            body   : payload
        })
        .then(function (resp) { return resp.json(); })
        .then(function (data) {
            if (data.success && Array.isArray(data.rows)) {
                state.rows       = data.rows.map(normalizeRow);
                state.deletedIds = [];
                // Server is now the source of truth — remove the local draft so
                // the "unsaved changes" banner does not reappear on the next visit.
                clearStorage();
                markDirty(false);
                render();
                var banner = document.getElementById('local-draft-banner');
                if (banner) banner.style.display = 'none';
            }
        })
        .catch(function (e) {
            console.error('Sync failed:', e);
        })
        .finally(function () {
            isSaving = false;
        });
    }

    // ── Initialisation ────────────────────────────────────────────────────────

    function init() {
        // Determine whether to use localStorage or server state
        var stored = loadFromStorage();

        if (stored && stored.rows) {
            // Show the draft banner and let the user decide
            stored.rows = stored.rows.map(normalizeRow);
            state = stored;
            var banner = document.getElementById('local-draft-banner');
            if (banner) {
                banner.style.display = '';
            }
            markDirty(true);
        } else {
            // Fresh load from server
            state = { rows: deepClone(cfg.serverRows).map(normalizeRow), deletedIds: [] };
        }

        render();
        fixStickySubhead();
        setupEvents();
    }

    function setupEvents() {
        // Add row
        var addBtn = document.getElementById('add-row-btn');
        if (addBtn) addBtn.addEventListener('click', addRow);

        // Save button
        var saveBtn = document.getElementById('save-btn');
        if (saveBtn) saveBtn.addEventListener('click', function () {
            saveBtn.classList.add('is-loading');
            saveBtn.disabled = true;
            syncToServer(false).finally(function () {
                saveBtn.classList.remove('is-loading');
                saveBtn.disabled = false;
            });
        });

        // Draft banner: keep local
        var keepBtn = document.getElementById('keep-local-btn');
        if (keepBtn) keepBtn.addEventListener('click', function () {
            var banner = document.getElementById('local-draft-banner');
            if (banner) banner.style.display = 'none';
        });

        // Draft banner: discard local, reload
        var loadServerBtn = document.getElementById('load-server-btn');
        if (loadServerBtn) loadServerBtn.addEventListener('click', function () {
            if (!confirm('Discard all local changes and reload from the server?')) return;
            clearStorage();
            location.reload();
        });

        // Edit Details modal
        var editDetailsBtn = document.getElementById('editDetailsBtn');
        var editModal      = document.getElementById('editDetailsModal');
        var editModalBg    = document.getElementById('editModalBg');
        if (editDetailsBtn && editModal) {
            editDetailsBtn.addEventListener('click', function () {
                editModal.classList.add('is-active');
            });
        }
        if (editModalBg) {
            editModalBg.addEventListener('click', function () {
                editModal.classList.remove('is-active');
            });
        }
        document.querySelectorAll('.js-close-edit-modal').forEach(function (el) {
            el.addEventListener('click', function () {
                if (editModal) editModal.classList.remove('is-active');
            });
        });

        // Status change dropdown items
        document.querySelectorAll('.js-status-change').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                var label = link.dataset.label || link.dataset.status;
                if (!confirm('Change status to "' + label + '"?')) return;
                var form = document.getElementById('statusForm');
                var val  = document.getElementById('statusFormValue');
                if (form && val) {
                    val.value = link.dataset.status;
                    form.submit();
                }
            });
        });

        // Export dropdown toggle
        var exportDropdown = document.getElementById('exportDropdown');
        if (exportDropdown) {
            exportDropdown.querySelector('.dropdown-trigger button').addEventListener('click', function (e) {
                e.stopPropagation();
                exportDropdown.classList.toggle('is-active');
            });
        }

        // Status dropdown toggle
        var statusDropdown = document.getElementById('statusDropdown');
        if (statusDropdown) {
            statusDropdown.querySelector('.dropdown-trigger button').addEventListener('click', function (e) {
                e.stopPropagation();
                statusDropdown.classList.toggle('is-active');
            });
        }

        // Close dropdowns on outside click
        document.addEventListener('click', function () {
            document.querySelectorAll('.dropdown.is-active').forEach(function (dd) {
                dd.classList.remove('is-active');
            });
        });

        // Auto-sync on page unload (sendBeacon)
        window.addEventListener('beforeunload', function () {
            if (isDirty) syncToServer(true);
        });

        // Auto-sync when tab becomes hidden
        document.addEventListener('visibilitychange', function () {
            if (document.hidden && isDirty) syncToServer(false);
        });
    }

    // ── Boot ─────────────────────────────────────────────────────────────────
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

}());
