<?php
use App\Core\Session;
use App\Helpers\Csrf;
use App\Models\Assessment;
Session::start();

$assessment    = $assessment    ?? [];
$matrix        = $matrix        ?? [];
$rows          = $rows          ?? [];
$isOwner       = $isOwner       ?? false;
$canEdit       = $canEdit       ?? false;
$transitions   = $transitions   ?? [];
$controlTypes  = $controlTypes  ?? [];
$csrf          = $csrf          ?? Csrf::token();
$statusLabels  = $statusLabels  ?? Assessment::STATUS_LABELS;
$statusColors  = $statusColors  ?? Assessment::STATUS_COLORS;
$templateLabels= $templateLabels?? Assessment::TEMPLATE_LABELS;

$cc           = $assessment['column_config'] ?? [];
$statusLabel  = $statusLabels[$assessment['status'] ?? 'draft'] ?? 'Draft';
$statusColor  = $statusColors[$assessment['status'] ?? 'draft'] ?? 'is-light';
$templateLabel= $templateLabels[$assessment['template_type'] ?? 'simple'] ?? '';

// JSON-encode data for JavaScript
$rowsJson      = json_encode(array_values($rows), JSON_HEX_TAG);
$cellsJson     = json_encode($matrix['cells']            ?? [], JSON_HEX_TAG);
$sevJson       = json_encode($matrix['severity_levels']  ?? [], JSON_HEX_TAG);
$lhJson        = json_encode($matrix['likelihood_levels']?? [], JSON_HEX_TAG);
$ccJson        = json_encode($cc, JSON_HEX_TAG);
$ctJson        = json_encode($controlTypes, JSON_HEX_TAG);
$csrfEsc       = htmlspecialchars($csrf);
$showAccept    = !empty($cc['show_accept_yn']);
?>

<!-- ── Breadcrumb + page header ───────────────────────────────────────────── -->
<div class="level mb-3">
    <div class="level-left">
        <div class="level-item">
            <div>
                <nav class="breadcrumb is-small mb-1" aria-label="breadcrumbs">
                    <ul>
                        <li><a href="/assessments">Assessments</a></li>
                        <li class="is-active">
                            <a href="#" aria-current="page">
                                <?= htmlspecialchars($assessment['reference_number'] ?? $assessment['title']) ?>
                            </a>
                        </li>
                    </ul>
                </nav>
                <h1 class="title is-4 mb-1" style="line-height:1.2;">
                    <?= htmlspecialchars($assessment['title']) ?>
                </h1>
                <div class="tags">
                    <span class="tag <?= $statusColor ?>">
                        <?= htmlspecialchars($statusLabel) ?>
                    </span>
                    <span class="tag is-light">
                        <span class="icon is-small"><i class="fas fa-table-cells"></i></span>
                        <span><?= htmlspecialchars($assessment['matrix_name'] ?? '') ?></span>
                    </span>
                    <span class="tag is-light">
                        <?= htmlspecialchars($templateLabel) ?>
                    </span>
                    <?php if (!empty($assessment['reference_number'])): ?>
                    <span class="tag is-light has-text-grey">
                        <?= htmlspecialchars($assessment['reference_number']) ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="level-right">
        <div class="level-item">
            <div class="is-flex" style="gap:0.4rem;flex-wrap:wrap;justify-content:flex-end;">

                <!-- Save indicator + save button -->
                <?php if ($canEdit): ?>
                <span id="save-indicator" class="tag is-light" style="align-self:center;">No changes</span>
                <button id="save-btn" class="button is-link" type="button">
                    <span class="icon"><i class="fas fa-floppy-disk"></i></span>
                    <span>Save</span>
                </button>
                <?php endif; ?>

                <!-- Duplicate -->
                <?php if ($isOwner): ?>
                <form method="POST" action="/assessments/<?= $assessment['id'] ?>/copy" style="display:inline;">
                    <input type="hidden" name="_csrf" value="<?= $csrfEsc ?>">
                    <button type="submit" class="button is-light"
                            title="Duplicate this assessment"
                            onclick="return confirm('Duplicate this assessment?')">
                        <span class="icon"><i class="fas fa-copy"></i></span>
                        <span>Duplicate</span>
                    </button>
                </form>
                <?php endif; ?>

                <!-- Export dropdown -->
                <div class="dropdown is-right" id="exportDropdown">
                    <div class="dropdown-trigger">
                        <button class="button is-light" aria-haspopup="true" aria-controls="exportMenu">
                            <span class="icon"><i class="fas fa-download"></i></span>
                            <span>Export</span>
                            <span class="icon is-small"><i class="fas fa-angle-down"></i></span>
                        </button>
                    </div>
                    <div class="dropdown-menu" id="exportMenu" role="menu">
                        <div class="dropdown-content">
                            <a href="/assessments/<?= $assessment['id'] ?>/export/pdf"
                               class="dropdown-item">
                                <span class="icon-text">
                                    <span class="icon has-text-danger"><i class="fas fa-file-pdf"></i></span>
                                    <span>Export PDF</span>
                                </span>
                            </a>
                            <a href="/assessments/<?= $assessment['id'] ?>/export/csv"
                               class="dropdown-item">
                                <span class="icon-text">
                                    <span class="icon has-text-success"><i class="fas fa-file-csv"></i></span>
                                    <span>Export CSV</span>
                                </span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Status workflow + edit details button (owner only) -->
                <?php if ($isOwner): ?>
                <button class="button is-light" id="editDetailsBtn" type="button"
                        title="Edit assessment title and metadata">
                    <span class="icon"><i class="fas fa-pen-to-square"></i></span>
                    <span>Details</span>
                </button>
                <?php if (!empty($transitions)): ?>
                <div class="dropdown is-right" id="statusDropdown">
                    <div class="dropdown-trigger">
                        <button class="button is-light" aria-haspopup="true" aria-controls="statusMenu">
                            <span class="icon"><i class="fas fa-arrows-rotate"></i></span>
                            <span>Status</span>
                            <span class="icon is-small"><i class="fas fa-angle-down"></i></span>
                        </button>
                    </div>
                    <div class="dropdown-menu" id="statusMenu" role="menu">
                        <div class="dropdown-content">
                            <p class="dropdown-item is-size-7 has-text-grey">
                                Change status to:
                            </p>
                            <hr class="dropdown-divider">
                            <?php foreach ($transitions as $ts): ?>
                            <a class="dropdown-item js-status-change"
                               href="#"
                               data-status="<?= $ts ?>"
                               data-label="<?= htmlspecialchars($statusLabels[$ts] ?? $ts) ?>">
                                <?= htmlspecialchars($statusLabels[$ts] ?? $ts) ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Delete -->
                <form method="POST" action="/assessments/<?= $assessment['id'] ?>/delete" style="display:inline;">
                    <input type="hidden" name="_csrf" value="<?= $csrfEsc ?>">
                    <button type="submit" class="button is-danger-muted"
                            onclick="return confirm('Permanently delete this assessment and all its rows? This cannot be undone.')">
                        <span class="icon"><i class="fas fa-trash"></i></span>
                    </button>
                </form>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<!-- ── Local-changes banner (shown by JS if localStorage has newer data) ──── -->
<div id="local-draft-banner" class="notification is-warning is-light mb-3" style="display:none;">
    <span class="icon-text">
        <span class="icon"><i class="fas fa-triangle-exclamation"></i></span>
        <span>You have <strong>unsaved local changes</strong> from a previous session.</span>
    </span>
    <div class="mt-2 is-flex" style="gap:0.5rem;">
        <button id="keep-local-btn" class="button is-warning is-small" type="button">
            Keep Local Changes
        </button>
        <button id="load-server-btn" class="button is-light is-small" type="button">
            Discard &amp; Load Server Version
        </button>
    </div>
</div>

<!-- ── Assessment info strip ──────────────────────────────────────────────── -->
<div class="is-flex is-flex-wrap-wrap mb-3" style="gap:1rem;font-size:0.85rem;color:var(--text-muted);">
    <?php if (!empty($assessment['assessor_name'])): ?>
    <span>
        <span class="icon is-small"><i class="fas fa-user-pen"></i></span>
        <?= htmlspecialchars($assessment['assessor_name']) ?>
    </span>
    <?php endif; ?>
    <?php if (!empty($assessment['location'])): ?>
    <span>
        <span class="icon is-small"><i class="fas fa-location-dot"></i></span>
        <?= htmlspecialchars($assessment['location']) ?>
    </span>
    <?php endif; ?>
    <?php if (!empty($assessment['review_date'])): ?>
    <span>
        <span class="icon is-small"><i class="fas fa-calendar-check"></i></span>
        Review: <?= htmlspecialchars($assessment['review_date']) ?>
    </span>
    <?php endif; ?>
    <?php if (!empty($assessment['description'])): ?>
    <span>
        <span class="icon is-small"><i class="fas fa-circle-info"></i></span>
        <?= htmlspecialchars($assessment['description']) ?>
    </span>
    <?php endif; ?>
</div>

<!-- ── Assessment table wrapper ───────────────────────────────────────────── -->
<div class="box p-0 mb-4" id="table-wrapper">
    <table class="table is-fullwidth is-narrow assessment-table" id="assessment-table">
        <thead>
            <tr id="assessment-thead">
                <th class="drag-col" title="Drag to reorder"></th>
                <th class="num-col">#</th>
                <?php if (!empty($cc['show_activity_condition'])): ?>
                <th>Activity / Condition</th>
                <?php endif; ?>
                <th>Hazard</th>
                <?php if (!empty($cc['show_exposure_description'])): ?>
                <th>Exposure Description</th>
                <?php endif; ?>
                <?php if (!empty($cc['show_exposed_assets'])): ?>
                <th>Exposed Assets / Persons</th>
                <?php endif; ?>
                <th>Effect</th>

                <?php if (!empty($cc['show_natural_risk'])): ?>
                <th class="risk-group-header" colspan="<?= 2 + ($showAccept ? 1 : 0) + 1 ?>">
                    Natural Risk (before controls)
                </th>
                <?php endif; ?>

                <th>Existing Controls</th>

                <th class="risk-group-header" colspan="<?= 2 + ($showAccept ? 1 : 0) + 1 ?>">
                    Current Risk
                </th>

                <?php if (!empty($cc['show_proposed_controls'])): ?>
                <th>Proposed Controls</th>
                <?php endif; ?>

                <?php if (!empty($cc['show_residual_risk'])): ?>
                <th class="risk-group-header" colspan="<?= 2 + ($showAccept ? 1 : 0) + 1 ?>">
                    Residual Risk
                </th>
                <?php endif; ?>

                <?php if ($canEdit): ?>
                <th class="actions-col"></th>
                <?php endif; ?>
            </tr>
            <tr id="assessment-subhead">
                <th></th><th></th>
                <?php if (!empty($cc['show_activity_condition'])): ?><th></th><?php endif; ?>
                <th></th>
                <?php if (!empty($cc['show_exposure_description'])): ?><th></th><?php endif; ?>
                <?php if (!empty($cc['show_exposed_assets'])): ?><th></th><?php endif; ?>
                <th></th>

                <?php if (!empty($cc['show_natural_risk'])): ?>
                <th class="subhead-cell">S</th>
                <th class="subhead-cell">L</th>
                <th class="subhead-cell">Risk</th>
                <?php if ($showAccept): ?><th class="subhead-cell">Accept</th><?php endif; ?>
                <?php endif; ?>

                <th></th>

                <th class="subhead-cell">S</th>
                <th class="subhead-cell">L</th>
                <th class="subhead-cell">Risk</th>
                <?php if ($showAccept): ?><th class="subhead-cell">Accept</th><?php endif; ?>

                <?php if (!empty($cc['show_proposed_controls'])): ?><th></th><?php endif; ?>

                <?php if (!empty($cc['show_residual_risk'])): ?>
                <th class="subhead-cell">S</th>
                <th class="subhead-cell">L</th>
                <th class="subhead-cell">Risk</th>
                <?php if ($showAccept): ?><th class="subhead-cell">Accept</th><?php endif; ?>
                <?php endif; ?>

                <?php if ($canEdit): ?><th></th><?php endif; ?>
            </tr>
        </thead>
        <tbody id="assessment-tbody">
            <!-- Rows rendered by assessment-editor.js -->
        </tbody>
    </table>
</div>

<!-- ── Empty state (shown by JS when no rows) ─────────────────────────────── -->
<div id="empty-state" class="box has-text-centered py-6" style="display:none;">
    <p class="has-text-grey-light mb-3">
        <span class="icon is-large"><i class="fas fa-list-check fa-2x"></i></span>
    </p>
    <p class="has-text-grey">No hazard rows yet.</p>
    <?php if ($canEdit): ?>
    <p class="has-text-grey is-size-7 mt-1">Click <strong>Add Row</strong> below to start building your assessment.</p>
    <?php endif; ?>
</div>

<!-- ── Add row button ─────────────────────────────────────────────────────── -->
<?php if ($canEdit): ?>
<div class="is-flex is-align-items-center" style="gap:0.75rem;margin-top:0.5rem;">
    <button id="add-row-btn" class="button is-link is-outlined" type="button">
        <span class="icon"><i class="fas fa-plus"></i></span>
        <span>Add Row</span>
    </button>
    <span class="is-size-7 has-text-grey" id="row-count-label"></span>
</div>
<?php endif; ?>

<!-- ── Edit Details modal ─────────────────────────────────────────────────── -->
<?php if ($isOwner): ?>
<div id="editDetailsModal" class="modal">
    <div class="modal-background" id="editModalBg"></div>
    <div class="modal-card" style="max-width:560px;">
        <header class="modal-card-head" style="background:var(--ocean);border-bottom:none;">
            <p class="modal-card-title" style="color:#fff;font-family:'Montserrat',sans-serif;font-size:1rem;">
                <span class="icon-text">
                    <span class="icon"><i class="fas fa-pen-to-square"></i></span>
                    <span>Edit Assessment Details</span>
                </span>
            </p>
            <button class="delete js-close-edit-modal" aria-label="close"></button>
        </header>
        <form method="POST" action="/assessments/<?= $assessment['id'] ?>">
            <section class="modal-card-body">
                <input type="hidden" name="_csrf" value="<?= $csrfEsc ?>">

                <div class="field">
                    <label class="label">Title <span class="has-text-danger">*</span></label>
                    <div class="control">
                        <input class="input" type="text" name="title"
                               value="<?= htmlspecialchars($assessment['title']) ?>"
                               maxlength="200" required>
                    </div>
                </div>

                <div class="columns">
                    <div class="column">
                        <div class="field">
                            <label class="label">Reference Number</label>
                            <div class="control">
                                <input class="input" type="text" name="reference_number"
                                       value="<?= htmlspecialchars($assessment['reference_number'] ?? '') ?>"
                                       maxlength="60">
                            </div>
                        </div>
                    </div>
                    <div class="column">
                        <div class="field">
                            <label class="label">Review Date</label>
                            <div class="control">
                                <input class="input" type="date" name="review_date"
                                       value="<?= htmlspecialchars($assessment['review_date'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="field">
                    <label class="label">Description / Scope</label>
                    <div class="control">
                        <textarea class="textarea" name="description" rows="3"
                                  maxlength="1000"><?= htmlspecialchars($assessment['description'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="columns">
                    <div class="column">
                        <div class="field">
                            <label class="label">Location / Site</label>
                            <div class="control">
                                <input class="input" type="text" name="location"
                                       value="<?= htmlspecialchars($assessment['location'] ?? '') ?>"
                                       maxlength="120">
                            </div>
                        </div>
                    </div>
                    <div class="column">
                        <div class="field">
                            <label class="label">Assessor Name</label>
                            <div class="control">
                                <input class="input" type="text" name="assessor_name"
                                       value="<?= htmlspecialchars($assessment['assessor_name'] ?? '') ?>"
                                       maxlength="120">
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <footer class="modal-card-foot" style="justify-content:flex-end;gap:0.5rem;">
                <button type="button" class="button js-close-edit-modal">Cancel</button>
                <button type="submit" class="button is-link">
                    <span class="icon"><i class="fas fa-floppy-disk"></i></span>
                    <span>Save Details</span>
                </button>
            </footer>
        </form>
    </div>
</div>

<!-- Status change hidden form (submitted by JS) -->
<form id="statusForm" method="POST" action="/assessments/<?= $assessment['id'] ?>/status" style="display:none;">
    <input type="hidden" name="_csrf" value="<?= $csrfEsc ?>">
    <input type="hidden" name="status" id="statusFormValue" value="">
</form>
<?php endif; ?>

<!-- ── SortableJS + Editor ─────────────────────────────────────────────────── -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="/assets/js/assessment-editor.js" defer></script>

<script>
// Boot data passed from PHP to the editor
window.__EDITOR_CONFIG__ = {
    assessmentId : <?= (int) $assessment['id'] ?>,
    csrf         : <?= json_encode($csrf) ?>,
    canEdit      : <?= $canEdit ? 'true' : 'false' ?>,
    serverRows   : <?= $rowsJson ?>,
    matrixCells  : <?= $cellsJson ?>,
    severityLevels  : <?= $sevJson ?>,
    likelihoodLevels: <?= $lhJson ?>,
    columnConfig : <?= $ccJson ?>,
    controlTypes : <?= $ctJson ?>
};
</script>
