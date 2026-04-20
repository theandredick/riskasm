<?php
use App\Core\Session;
use App\Helpers\Csrf;
Session::start();

$assessments   = $assessments   ?? [];
$csrf          = $csrf          ?? Csrf::token();
$statusLabels  = $statusLabels  ?? [];
$statusColors  = $statusColors  ?? [];
$templateLabels= $templateLabels?? [];
$userId        = (int) Session::get('user_id');
$userRole      = Session::userRole() ?? 'viewer';
$isViewer      = $userRole === 'viewer';
?>

<!-- ── Page header ──────────────────────────────────────────────────────────── -->
<div class="level mb-5">
    <div class="level-left">
        <div class="level-item">
            <div>
                <h1 class="title is-4 mb-1">
                    <span class="icon-text">
                        <span class="icon has-text-teal"><i class="fas fa-clipboard-list"></i></span>
                        <span>My Assessments</span>
                    </span>
                </h1>
                <p class="subtitle is-6 has-text-grey">
                    <?= $isViewer ? 'Assessments shared with you.' : 'Your risk assessments — owned and shared with you.' ?>
                </p>
            </div>
        </div>
    </div>
    <?php if (!$isViewer): ?>
    <div class="level-right">
        <div class="level-item">
            <a class="button is-link" href="/assessments/new">
                <span class="icon"><i class="fas fa-plus"></i></span>
                <span>New Assessment</span>
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (empty($assessments)): ?>
<!-- ── Empty state ─────────────────────────────────────────────────────────── -->
<div class="box has-text-centered py-6">
    <p class="has-text-grey-light mb-3">
        <span class="icon is-large"><i class="fas fa-clipboard-list fa-3x"></i></span>
    </p>
    <?php if ($isViewer): ?>
    <p class="title is-5 has-text-grey">No assessments shared with you yet</p>
    <p class="has-text-grey is-size-6 mb-4">
        Assessments shared with you will appear here.
    </p>
    <?php else: ?>
    <p class="title is-5 has-text-grey">No assessments yet</p>
    <p class="has-text-grey is-size-6 mb-4">
        Create your first risk assessment to get started.
    </p>
    <a class="button is-link" href="/assessments/new">
        <span class="icon"><i class="fas fa-plus"></i></span>
        <span>Create Assessment</span>
    </a>
    <?php endif; ?>
</div>

<?php else: ?>
<!-- ── Search filter ────────────────────────────────────────────────────────── -->
<?php if (count($assessments) > 5): ?>
<div class="field mb-4" style="max-width:380px;">
    <div class="control has-icons-left">
        <input id="assessment-search" class="input" type="text"
               placeholder="Filter by title, reference or status…">
        <span class="icon is-left"><i class="fas fa-magnifying-glass"></i></span>
    </div>
</div>
<?php endif; ?>

<!-- ── Assessment table ──────────────────────────────────────────────────────── -->
<div class="box p-0" style="overflow-x:auto;">
    <table class="table is-fullwidth is-hoverable" id="assessment-list-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Reference</th>
                <th>Template</th>
                <th>Matrix</th>
                <th>Rows</th>
                <th>Status</th>
                <th>Updated</th>
                <th style="width:120px;"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($assessments as $a):
                $isOwned     = (int)$a['owner_id'] === $userId;
                $statusLabel = $statusLabels[$a['status']] ?? $a['status'];
                $statusColor = $statusColors[$a['status']] ?? 'is-light';
                $tLabel      = $templateLabels[$a['template_type']] ?? $a['template_type'];
                $updatedAt   = date('d M Y', strtotime($a['updated_at']));
            ?>
            <tr class="assessment-row"
                data-search="<?= htmlspecialchars(strtolower(
                    ($a['title'] ?? '') . ' ' .
                    ($a['reference_number'] ?? '') . ' ' .
                    $statusLabel
                )) ?>">
                <td>
                    <a href="/assessments/<?= $a['id'] ?>" class="has-text-weight-semibold">
                        <?= htmlspecialchars($a['title']) ?>
                    </a>
                    <?php if (!$isOwned): ?>
                    <span class="tag is-light is-small ml-1" title="Shared with you">shared</span>
                    <?php endif; ?>
                </td>
                <td class="has-text-grey is-size-7">
                    <?= htmlspecialchars($a['reference_number'] ?? '—') ?>
                </td>
                <td class="is-size-7">
                    <?= htmlspecialchars($tLabel) ?>
                </td>
                <td class="is-size-7 has-text-grey">
                    <?= htmlspecialchars($a['matrix_name'] ?? '') ?>
                </td>
                <td class="has-text-centered is-size-7">
                    <?= (int) ($a['row_count'] ?? 0) ?>
                </td>
                <td>
                    <span class="tag <?= $statusColor ?> is-small">
                        <?= htmlspecialchars($statusLabel) ?>
                    </span>
                </td>
                <td class="has-text-grey is-size-7">
                    <?= $updatedAt ?>
                </td>
                <td>
                    <div class="is-flex" style="gap:0.3rem;">
                        <a class="button is-link is-outlined is-small"
                           href="/assessments/<?= $a['id'] ?>"
                           title="<?= $isViewer ? 'View' : 'Open editor' ?>">
                            <span class="icon"><i class="fas <?= $isViewer ? 'fa-eye' : 'fa-pen-to-square' ?>"></i></span>
                        </a>
                        <?php if ($isOwned && !$isViewer): ?>
                        <form method="POST" action="/assessments/<?= $a['id'] ?>/copy" style="display:inline;">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                            <button type="submit" class="button is-light is-small"
                                    title="Duplicate"
                                    onclick="return confirm('Duplicate this assessment?')">
                                <span class="icon"><i class="fas fa-copy"></i></span>
                            </button>
                        </form>
                        <form method="POST" action="/assessments/<?= $a['id'] ?>/delete" style="display:inline;">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                            <button type="submit" class="button is-danger-muted is-small"
                                    title="Delete"
                                    onclick="return confirm('Delete \'<?= htmlspecialchars(addslashes($a['title'])) ?>\'? This cannot be undone.')">
                                <span class="icon"><i class="fas fa-trash"></i></span>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<p class="has-text-grey is-size-7 mt-2"><?= count($assessments) ?> assessment<?= count($assessments) !== 1 ? 's' : '' ?></p>
<?php endif; ?>

<?php if (count($assessments) > 5): ?>
<script>
(function () {
    var input = document.getElementById('assessment-search');
    if (!input) return;
    input.addEventListener('input', function () {
        var q = input.value.toLowerCase().trim();
        document.querySelectorAll('.assessment-row').forEach(function (row) {
            var match = !q || row.dataset.search.includes(q);
            row.style.display = match ? '' : 'none';
        });
    });
}());
</script>
<?php endif; ?>
