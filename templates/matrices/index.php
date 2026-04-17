<?php
use App\Core\Session;
use App\Helpers\Csrf;

Session::start();

$matrices = $matrices ?? [];

// Partition into system and user-owned
$systemMatrices = array_filter($matrices, fn($m) => $m['is_system']);
$ownedMatrices  = array_filter($matrices, fn($m) => !$m['is_system'] && (int)$m['owner_id'] === (int)Session::get('user_id'));

function dimensionBadge(array $m): string
{
    $s = (int) $m['severity_count'];
    $l = (int) $m['likelihood_count'];
    return '<span class="tag is-light is-info">' . $s . '×' . $l . '</span>';
}
?>

<!-- ── Page header ─────────────────────────────────────────────────────────── -->
<div class="level mb-5">
    <div class="level-left">
        <div class="level-item">
            <div>
                <h1 class="title is-4 mb-1">
                    <span class="icon-text">
                        <span class="icon has-text-teal"><i class="fas fa-table-cells"></i></span>
                        <span>Risk Matrices</span>
                    </span>
                </h1>
                <p class="subtitle is-6 has-text-grey">
                    Explore built-in standard matrices. Clone any matrix to create your own editable version.
                </p>
            </div>
        </div>
    </div>
    <div class="level-right">
        <div class="level-item">
            <a class="button is-link is-light" title="Custom matrix builder — coming in Phase 2" href="/matrices/new">
                <span class="icon"><i class="fas fa-plus"></i></span>
                <span>New Matrix</span>
            </a>
        </div>
    </div>
</div>

<!-- ── System matrices ─────────────────────────────────────────────────────── -->
<h2 class="title is-5 mt-2 mb-3">
    <span class="icon-text">
        <span class="icon has-text-grey"><i class="fas fa-shield-halved"></i></span>
        <span>Standard Industry Matrices</span>
    </span>
</h2>

<div class="columns is-multiline mb-5">
    <?php foreach ($systemMatrices as $m): ?>
    <div class="column is-one-third-widescreen is-half-desktop is-full-tablet">
        <div class="box matrix-card h-100" style="display:flex;flex-direction:column;">
            <div style="flex:1;">
                <div class="is-flex is-justify-content-space-between is-align-items-flex-start mb-2">
                    <p class="title is-6 mb-1" style="line-height:1.3;">
                        <?= htmlspecialchars($m['name']) ?>
                    </p>
                    <div class="tags ml-2" style="flex-shrink:0;">
                        <?= dimensionBadge($m) ?>
                        <span class="tag is-ocean is-small">System</span>
                    </div>
                </div>
                <p class="is-size-7 has-text-grey-dark" style="line-height:1.5;">
                    <?= htmlspecialchars($m['description'] ?? '') ?>
                </p>
                <p class="mt-2 is-size-7">
                    <span class="has-text-weight-semibold has-text-ocean">
                        <?= htmlspecialchars($m['severity_axis_label']) ?>
                    </span>
                    <span class="has-text-grey-dark mx-1">×</span>
                    <span class="has-text-weight-semibold has-text-ocean">
                        <?= htmlspecialchars($m['likelihood_axis_label']) ?>
                    </span>
                </p>
            </div>
            <div class="mt-4 is-flex" style="gap:0.5rem;">
                <a class="button is-link is-small is-outlined" href="/matrices/<?= $m['id'] ?>">
                    <span class="icon"><i class="fas fa-eye"></i></span>
                    <span>View</span>
                </a>
                <button class="button is-teal is-small js-clone-btn"
                        type="button"
                        data-matrix-id="<?= $m['id'] ?>"
                        data-matrix-name="<?= htmlspecialchars($m['name'], ENT_QUOTES) ?>"
                        title="Create your own editable copy of this matrix">
                    <span class="icon"><i class="fas fa-copy"></i></span>
                    <span>Clone</span>
                </button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ── User-owned matrices ─────────────────────────────────────────────────── -->
<?php if (!empty($ownedMatrices)): ?>
<h2 class="title is-5 mb-3">
    <span class="icon-text">
        <span class="icon has-text-grey"><i class="fas fa-user-gear"></i></span>
        <span>My Custom Matrices</span>
    </span>
    <span class="tag is-teal ml-2"><?= count($ownedMatrices) ?></span>
</h2>

<div class="columns is-multiline">
    <?php foreach ($ownedMatrices as $m): ?>
    <div class="column is-one-third-widescreen is-half-desktop is-full-tablet">
        <div class="box matrix-card h-100" style="display:flex;flex-direction:column;">
            <div style="flex:1;">
                <div class="is-flex is-justify-content-space-between is-align-items-flex-start mb-2">
                    <p class="title is-6 mb-1" style="line-height:1.3;">
                        <?= htmlspecialchars($m['name']) ?>
                    </p>
                    <div class="tags ml-2" style="flex-shrink:0;">
                        <?= dimensionBadge($m) ?>
                        <?php if ($m['is_public']): ?>
                        <span class="tag is-info is-small">Public</span>
                        <?php else: ?>
                        <span class="tag is-light is-small">Private</span>
                        <?php endif; ?>
                    </div>
                </div>
                <p class="is-size-7 has-text-grey-dark" style="line-height:1.5;">
                    <?= htmlspecialchars($m['description'] ?? '—') ?>
                </p>
            </div>
            <div class="mt-4 is-flex" style="gap:0.5rem;flex-wrap:wrap;">
                <a class="button is-link is-small is-outlined" href="/matrices/<?= $m['id'] ?>">
                    <span class="icon"><i class="fas fa-eye"></i></span>
                    <span>View</span>
                </a>
                <a class="button is-info is-small is-outlined" href="/matrices/<?= $m['id'] ?>/edit">
                    <span class="icon"><i class="fas fa-pencil"></i></span>
                    <span>Edit</span>
                </a>
                <button class="button is-small js-clone-btn"
                        type="button"
                        data-matrix-id="<?= $m['id'] ?>"
                        data-matrix-name="<?= htmlspecialchars($m['name'], ENT_QUOTES) ?>">
                    <span class="icon"><i class="fas fa-copy"></i></span>
                    <span>Clone</span>
                </button>
                <form method="POST" action="/matrices/<?= $m['id'] ?>/delete"
                      style="display:inline;"
                      onsubmit="return confirm('Delete this matrix? This cannot be undone.');">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">
                    <button class="button is-danger-muted is-small" type="submit">
                        <span class="icon"><i class="fas fa-trash"></i></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="box has-background-light has-text-centered py-5">
    <p class="has-text-grey mb-2">
        <span class="icon is-large"><i class="fas fa-wand-magic-sparkles fa-2x has-text-grey-light"></i></span>
    </p>
    <p class="has-text-grey is-size-6">You haven't created any custom matrices yet.</p>
    <p class="has-text-grey is-size-7 mt-1">
        Clone a standard matrix above to get started, or use
        <a href="/matrices/new">New Matrix</a> when the builder is available.
    </p>
</div>
<?php endif; ?>

<!-- ── Clone naming modal ──────────────────────────────────────────────────── -->
<div id="cloneNameModal" class="modal">
    <div class="modal-background" id="cloneModalBg"></div>
    <div class="modal-card" style="max-width:480px;">
        <header class="modal-card-head" style="background-color:var(--ocean);border-bottom:none;">
            <p class="modal-card-title" style="color:#fff;font-family:'Montserrat',system-ui,sans-serif;font-size:1rem;">
                <span class="icon-text">
                    <span class="icon"><i class="fas fa-copy"></i></span>
                    <span>Clone Matrix</span>
                </span>
            </p>
            <button class="delete js-close-clone-modal" aria-label="close"></button>
        </header>
        <section class="modal-card-body">
            <p class="is-size-6 mb-4" style="color:var(--text);">
                What would you like to name your custom version?
            </p>
            <div class="field">
                <label class="label" for="cloneNameInput">Matrix Name</label>
                <div class="control has-icons-left">
                    <input id="cloneNameInput"
                           class="input"
                           type="text"
                           maxlength="120"
                           autocomplete="off"
                           placeholder="e.g. My 5×5 Project Matrix">
                    <span class="icon is-left"><i class="fas fa-tag"></i></span>
                </div>
                <p class="help">You can rename it again later.</p>
            </div>
        </section>
        <footer class="modal-card-foot" style="justify-content:flex-end;gap:0.5rem;">
            <button class="button js-close-clone-modal" type="button">Cancel</button>
            <button id="cloneConfirmBtn" class="button is-link" type="button">
                <span class="icon"><i class="fas fa-copy"></i></span>
                <span>Create Clone</span>
            </button>
        </footer>
    </div>
</div>

<!-- Hidden form used by the modal to submit the clone request -->
<form id="cloneSubmitForm" method="POST" style="display:none;">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">
    <input type="hidden" id="cloneNameHidden" name="clone_name" value="">
</form>

<style>
.button.is-teal {
    background-color: var(--teal);
    border-color: transparent;
    color: #fff;
    font-weight: 600;
}
.button.is-teal:hover {
    background-color: #268898;
    color: #fff;
}
.matrix-card {
    border: 1px solid #e4ecf0;
    transition: box-shadow 0.15s;
}
.matrix-card:hover {
    box-shadow: 0 4px 16px rgba(0,63,92,0.12);
}
</style>

<script>
(function () {
    const modal       = document.getElementById('cloneNameModal');
    const nameInput   = document.getElementById('cloneNameInput');
    const hiddenName  = document.getElementById('cloneNameHidden');
    const confirmBtn  = document.getElementById('cloneConfirmBtn');
    const submitForm  = document.getElementById('cloneSubmitForm');

    function openModal(matrixId, matrixName) {
        const suggested = 'Copy of ' + matrixName;
        nameInput.value = suggested;
        submitForm.action = '/matrices/' + matrixId + '/copy';
        modal.classList.add('is-active');
        // Select all so the user can immediately type a new name
        nameInput.select();
    }

    function closeModal() {
        modal.classList.remove('is-active');
        nameInput.value = '';
    }

    // Open modal on any Clone button click
    document.querySelectorAll('.js-clone-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openModal(btn.dataset.matrixId, btn.dataset.matrixName);
        });
    });

    // Close on background / X / Cancel
    document.getElementById('cloneModalBg').addEventListener('click', closeModal);
    document.querySelectorAll('.js-close-clone-modal').forEach(function (el) {
        el.addEventListener('click', closeModal);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('is-active')) closeModal();
    });

    // Confirm: copy name to hidden field then submit
    confirmBtn.addEventListener('click', function () {
        const name = nameInput.value.trim();
        if (!name) {
            nameInput.classList.add('is-danger');
            nameInput.focus();
            return;
        }
        nameInput.classList.remove('is-danger');
        hiddenName.value = name;
        submitForm.submit();
    });

    // Allow Enter key in the name field to confirm
    nameInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); confirmBtn.click(); }
    });
    nameInput.addEventListener('input', function () {
        nameInput.classList.remove('is-danger');
    });
}());
</script>
