<?php
use App\Core\Session;
Session::start();

$matrices             = $matrices             ?? [];
$templateTypes        = $templateTypes        ?? [];
$templateLabels       = $templateLabels       ?? [];
$templateDescriptions = $templateDescriptions ?? [];
$errors               = $errors               ?? [];
$old                  = $old                  ?? [];
$csrf                 = $csrf                 ?? '';

function fieldError(array $errors, string $field): string
{
    if (!isset($errors[$field])) return '';
    return '<p class="help is-danger">' . htmlspecialchars($errors[$field][0]) . '</p>';
}

function inputClass(array $errors, string $field): string
{
    return isset($errors[$field]) ? 'input is-danger' : 'input';
}

$oldTitle     = htmlspecialchars($old['title']            ?? '');
$oldRef       = htmlspecialchars($old['reference_number'] ?? '');
$oldDesc      = htmlspecialchars($old['description']      ?? '');
$oldLocation  = htmlspecialchars($old['location']         ?? '');
$oldAssessor  = htmlspecialchars($old['assessor_name']    ?? '');
$oldDate      = htmlspecialchars($old['review_date']      ?? '');
$oldMatrix    = $old['matrix_id']     ?? '';
$oldTemplate  = $old['template_type'] ?? 'simple';
?>

<!-- ── Page header ──────────────────────────────────────────────────────────── -->
<div class="level mb-5">
    <div class="level-left">
        <div class="level-item">
            <div>
                <nav class="breadcrumb is-small mb-1" aria-label="breadcrumbs">
                    <ul>
                        <li><a href="/assessments">Assessments</a></li>
                        <li class="is-active"><a href="#" aria-current="page">New Assessment</a></li>
                    </ul>
                </nav>
                <h1 class="title is-4 mb-1">
                    <span class="icon-text">
                        <span class="icon has-text-teal"><i class="fas fa-plus-circle"></i></span>
                        <span>New Risk Assessment</span>
                    </span>
                </h1>
                <p class="subtitle is-6 has-text-grey">
                    Choose your risk matrix and template, then fill in the assessment details.
                </p>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="/assessments/new" id="newAssessmentForm">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">

    <div class="columns is-variable is-6">

        <!-- ── Left column: Matrix + Template ──────────────────────────────── -->
        <div class="column is-5">

            <!-- Matrix selection -->
            <div class="box mb-4">
                <h2 class="title is-6 mb-3">
                    <span class="icon-text">
                        <span class="icon has-text-teal"><i class="fas fa-table-cells"></i></span>
                        <span>Step 1 — Risk Matrix</span>
                    </span>
                </h2>

                <div class="field">
                    <label class="label" for="matrix_id">Select Matrix <span class="has-text-danger">*</span></label>
                    <div class="control">
                        <div class="select is-fullwidth <?= isset($errors['matrix_id']) ? 'is-danger' : '' ?>">
                            <select name="matrix_id" id="matrix_id" required>
                                <option value="">— choose a risk matrix —</option>

                                <?php
                                $system = array_filter($matrices, fn($m) => $m['is_system']);
                                $owned  = array_filter($matrices, fn($m) => !$m['is_system']);
                                ?>

                                <?php if (!empty($system)): ?>
                                <optgroup label="Standard Industry Matrices">
                                    <?php foreach ($system as $m): ?>
                                    <option value="<?= $m['id'] ?>"
                                            data-description="<?= htmlspecialchars($m['description'] ?? '') ?>"
                                            data-dimensions="<?= (int)$m['severity_count'] ?>×<?= (int)$m['likelihood_count'] ?>"
                                            <?= (string)$oldMatrix === (string)$m['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($m['name']) ?>
                                        (<?= (int)$m['severity_count'] ?>×<?= (int)$m['likelihood_count'] ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <?php endif; ?>

                                <?php if (!empty($owned)): ?>
                                <optgroup label="My Custom Matrices">
                                    <?php foreach ($owned as $m): ?>
                                    <option value="<?= $m['id'] ?>"
                                            data-description="<?= htmlspecialchars($m['description'] ?? '') ?>"
                                            data-dimensions="<?= (int)$m['severity_count'] ?>×<?= (int)$m['likelihood_count'] ?>"
                                            <?= (string)$oldMatrix === (string)$m['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($m['name']) ?>
                                        (<?= (int)$m['severity_count'] ?>×<?= (int)$m['likelihood_count'] ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <?= fieldError($errors, 'matrix_id') ?>
                    <p class="help" id="matrixHint">
                        <a href="/matrices" target="_blank">Browse all matrices</a> to preview before choosing.
                    </p>
                    <p id="matrixDesc" class="help has-text-grey mt-1" style="display:none;"></p>
                </div>
            </div>

            <!-- Template type selection -->
            <div class="box">
                <h2 class="title is-6 mb-3">
                    <span class="icon-text">
                        <span class="icon has-text-teal"><i class="fas fa-table-columns"></i></span>
                        <span>Step 2 — Assessment Template</span>
                    </span>
                </h2>
                <p class="is-size-7 has-text-grey mb-3">
                    Determines which columns appear in the hazard table. You cannot change this after creation.
                </p>

                <?php foreach ($templateTypes as $tt): ?>
                <label class="template-type-card <?= $oldTemplate === $tt ? 'is-selected' : '' ?>">
                    <input type="radio"
                           name="template_type"
                           value="<?= $tt ?>"
                           <?= $oldTemplate === $tt ? 'checked' : '' ?>
                           class="template-radio">
                    <div class="template-card-inner">
                        <div class="is-flex is-justify-content-space-between is-align-items-center">
                            <span class="has-text-weight-semibold is-size-6">
                                <?= htmlspecialchars($templateLabels[$tt] ?? $tt) ?>
                            </span>
                            <span class="tag is-teal is-small template-check" style="display:none;">
                                <span class="icon"><i class="fas fa-check"></i></span>
                            </span>
                        </div>
                        <p class="is-size-7 has-text-grey mt-1">
                            <?= htmlspecialchars($templateDescriptions[$tt] ?? '') ?>
                        </p>
                    </div>
                </label>
                <?php endforeach; ?>

                <?= fieldError($errors, 'template_type') ?>
            </div>
        </div>

        <!-- ── Right column: Assessment metadata ──────────────────────────── -->
        <div class="column is-7">
            <div class="box">
                <h2 class="title is-6 mb-4">
                    <span class="icon-text">
                        <span class="icon has-text-teal"><i class="fas fa-file-lines"></i></span>
                        <span>Step 3 — Assessment Details</span>
                    </span>
                </h2>

                <!-- Title -->
                <div class="field">
                    <label class="label" for="title">
                        Assessment Title <span class="has-text-danger">*</span>
                    </label>
                    <div class="control">
                        <input class="<?= inputClass($errors, 'title') ?>"
                               type="text"
                               id="title"
                               name="title"
                               value="<?= $oldTitle ?>"
                               maxlength="200"
                               placeholder="e.g. Warehouse Forklift Operations — 2026"
                               required
                               autofocus>
                    </div>
                    <?= fieldError($errors, 'title') ?>
                </div>

                <div class="columns">
                    <!-- Reference number -->
                    <div class="column">
                        <div class="field">
                            <label class="label" for="reference_number">Reference Number</label>
                            <div class="control has-icons-left">
                                <input class="input" type="text" id="reference_number"
                                       name="reference_number" value="<?= $oldRef ?>"
                                       maxlength="60" placeholder="e.g. RA-2026-001">
                                <span class="icon is-left"><i class="fas fa-hashtag"></i></span>
                            </div>
                        </div>
                    </div>
                    <!-- Review date -->
                    <div class="column">
                        <div class="field">
                            <label class="label" for="review_date">Review Date</label>
                            <div class="control has-icons-left">
                                <input class="input" type="date" id="review_date"
                                       name="review_date" value="<?= $oldDate ?>">
                                <span class="icon is-left"><i class="fas fa-calendar-days"></i></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="field">
                    <label class="label" for="description">Description / Scope</label>
                    <div class="control">
                        <textarea class="textarea" id="description" name="description"
                                  rows="3" maxlength="1000"
                                  placeholder="Brief description of what this assessment covers…"><?= $oldDesc ?></textarea>
                    </div>
                </div>

                <div class="columns">
                    <!-- Location -->
                    <div class="column">
                        <div class="field">
                            <label class="label" for="location">Location / Site</label>
                            <div class="control has-icons-left">
                                <input class="input" type="text" id="location"
                                       name="location" value="<?= $oldLocation ?>"
                                       maxlength="120" placeholder="e.g. Warehouse B, Level 1">
                                <span class="icon is-left"><i class="fas fa-location-dot"></i></span>
                            </div>
                        </div>
                    </div>
                    <!-- Assessor -->
                    <div class="column">
                        <div class="field">
                            <label class="label" for="assessor_name">Assessor Name</label>
                            <div class="control has-icons-left">
                                <input class="input" type="text" id="assessor_name"
                                       name="assessor_name" value="<?= $oldAssessor ?>"
                                       maxlength="120" placeholder="Full name">
                                <span class="icon is-left"><i class="fas fa-user-pen"></i></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="field mt-5">
                    <div class="control">
                        <button type="submit" class="button is-link is-medium" id="createBtn">
                            <span class="icon"><i class="fas fa-arrow-right"></i></span>
                            <span>Create Assessment &amp; Open Editor</span>
                        </button>
                        <a href="/assessments" id="cancelBtn" class="button is-light is-medium ml-2">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
.template-type-card {
    display: block;
    border: 2px solid #d0dfe6;
    border-radius: 8px;
    margin-bottom: 0.6rem;
    padding: 0.7rem 0.9rem;
    cursor: pointer;
    transition: border-color 0.15s, background 0.15s;
}
.template-type-card:hover {
    border-color: var(--teal);
    background: rgba(47,158,174,0.04);
}
.template-type-card.is-selected {
    border-color: var(--teal);
    background: rgba(47,158,174,0.08);
}
.template-type-card input[type="radio"] {
    display: none;
}
.template-type-card.is-selected .template-check {
    display: inline-flex !important;
}
</style>

<script>
(function () {
    var isDirty = false;
    var CONFIRM_MSG = 'You have unsaved changes. Leave this page and discard them?';

    function markDirty() { isDirty = true; }

    // Template type cards
    document.querySelectorAll('.template-type-card').forEach(function (card) {
        card.addEventListener('click', function () {
            document.querySelectorAll('.template-type-card').forEach(c => c.classList.remove('is-selected'));
            card.classList.add('is-selected');
            card.querySelector('.template-radio').checked = true;
            markDirty();
        });
    });

    // Watch all text/date/select/textarea fields for changes
    document.getElementById('newAssessmentForm')
        .querySelectorAll('input:not([type="hidden"]):not([type="radio"]), select, textarea')
        .forEach(function (el) {
            el.addEventListener('input',  markDirty);
            el.addEventListener('change', markDirty);
        });

    // Matrix description hint
    var matrixSel  = document.getElementById('matrix_id');
    var matrixDesc = document.getElementById('matrixDesc');
    function updateMatrixDesc() {
        var opt = matrixSel.options[matrixSel.selectedIndex];
        var desc = opt ? opt.dataset.description : '';
        if (desc) {
            matrixDesc.textContent = desc;
            matrixDesc.style.display = '';
        } else {
            matrixDesc.style.display = 'none';
        }
    }
    matrixSel.addEventListener('change', updateMatrixDesc);
    updateMatrixDesc();

    // Confirm before leaving when dirty — Cancel button and breadcrumb link
    function confirmLeave(e) {
        if (!isDirty) return;
        if (!window.confirm(CONFIRM_MSG)) {
            e.preventDefault();
        }
    }
    document.getElementById('cancelBtn').addEventListener('click', confirmLeave);
    document.querySelector('.breadcrumb a[href="/assessments"]').addEventListener('click', confirmLeave);

    // Browser back / tab close guard
    window.addEventListener('beforeunload', function (e) {
        if (!isDirty) return;
        e.preventDefault();
        e.returnValue = CONFIRM_MSG;
    });

    // Prevent double-submit; clear dirty so beforeunload doesn't fire after submit
    document.getElementById('newAssessmentForm').addEventListener('submit', function () {
        isDirty = false;
        var btn = document.getElementById('createBtn');
        btn.disabled = true;
        btn.classList.add('is-loading');
    });
}());
</script>
