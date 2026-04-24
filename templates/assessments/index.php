<?php
use App\Core\Session;
use App\Helpers\Csrf;
Session::start();

$assessments  = $assessments   ?? [];
$csrf         = $csrf          ?? Csrf::token();
$statusLabels = $statusLabels  ?? [];
$statusColors = $statusColors  ?? [];
$templateLabels = $templateLabels ?? [];
$userId       = (int) Session::get('user_id');
$userRole     = Session::userRole() ?? 'viewer';
$isViewer     = $userRole === 'viewer';

// Active filters/sort from controller
$sort         = $sort         ?? 'updated_at';
$dir          = $dir          ?? 'desc';
$search       = $search       ?? '';
$statusFilter = $statusFilter ?? '';

/**
 * Build a URL for a column sort link, toggling direction if already sorted by that column.
 */
function sortUrl(string $col, string $currentSort, string $currentDir, string $search, string $statusFilter): string
{
    $nextDir = ($currentSort === $col && $currentDir === 'asc') ? 'desc' : 'asc';
    $params  = array_filter([
        'sort'   => $col,
        'dir'    => $nextDir,
        'q'      => $search,
        'status' => $statusFilter,
    ], fn($v) => $v !== '');
    return '/assessments?' . http_build_query($params);
}

function sortIcon(string $col, string $currentSort, string $currentDir): string
{
    if ($currentSort !== $col) {
        return '<span class="icon is-small has-text-grey-light"><i class="fas fa-sort"></i></span>';
    }
    $icon = $currentDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
    return "<span class=\"icon is-small has-text-link\"><i class=\"fas {$icon}\"></i></span>";
}

function filterUrl(string $statusFilter, string $sort, string $dir, string $search): string
{
    $params = array_filter([
        'status' => $statusFilter,
        'sort'   => $sort !== 'updated_at' ? $sort : '',
        'dir'    => $dir !== 'desc'        ? $dir  : '',
        'q'      => $search,
    ], fn($v) => $v !== '');
    return '/assessments' . ($params ? '?' . http_build_query($params) : '');
}

/**
 * Return a CSS style string for readable text on a given hex background.
 */
function listRiskBadgeStyle(string $hex): string
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6) {
        return 'color:#1a2e3b;';
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    return $luminance > 0.55 ? 'color:#1a2e3b;' : 'color:#ffffff;';
}
?>

<!-- ── Page header ──────────────────────────────────────────────────────────── -->
<div class="level mb-4">
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

<!-- ── Search + Status filter bar ───────────────────────────────────────────── -->
<form method="GET" action="/assessments" id="assessment-filter-form" class="mb-4">
    <input type="hidden" name="sort"   value="<?= htmlspecialchars($sort) ?>">
    <input type="hidden" name="dir"    value="<?= htmlspecialchars($dir) ?>">
    <div class="is-flex is-flex-wrap-wrap" style="gap:0.75rem; align-items:flex-end;">
        <!-- Search input -->
        <div class="field mb-0" style="flex:1; min-width:220px; max-width:400px;">
            <label class="label is-small">Search</label>
            <div class="control has-icons-left has-icons-right">
                <input class="input is-small" type="text" name="q"
                       value="<?= htmlspecialchars($search) ?>"
                       placeholder="Title, reference or hazard text…"
                       id="search-input">
                <span class="icon is-left is-small"><i class="fas fa-magnifying-glass"></i></span>
                <?php if ($search !== ''): ?>
                <span class="icon is-right is-small" style="pointer-events:all;">
                    <a href="<?= filterUrl($statusFilter, $sort, $dir, '') ?>" title="Clear search" class="has-text-grey">
                        <i class="fas fa-xmark"></i>
                    </a>
                </span>
                <?php endif; ?>
            </div>
        </div>
        <!-- Submit -->
        <div class="field mb-0">
            <label class="label is-small">&nbsp;</label>
            <div class="control">
                <button class="button is-link is-small" type="submit">Search</button>
            </div>
        </div>
    </div>
</form>

<!-- ── Status filter tabs ───────────────────────────────────────────────────── -->
<div class="tabs is-small mb-4">
    <ul>
        <li class="<?= $statusFilter === '' ? 'is-active' : '' ?>">
            <a href="<?= filterUrl('', $sort, $dir, $search) ?>">All</a>
        </li>
        <li class="<?= $statusFilter === 'draft' ? 'is-active' : '' ?>">
            <a href="<?= filterUrl('draft', $sort, $dir, $search) ?>">
                <span class="icon is-small"><i class="fas fa-pen"></i></span>
                <span>Draft</span>
            </a>
        </li>
        <li class="<?= $statusFilter === 'in_review' ? 'is-active' : '' ?>">
            <a href="<?= filterUrl('in_review', $sort, $dir, $search) ?>">
                <span class="icon is-small"><i class="fas fa-eye"></i></span>
                <span>In Review</span>
            </a>
        </li>
        <li class="<?= $statusFilter === 'approved' ? 'is-active' : '' ?>">
            <a href="<?= filterUrl('approved', $sort, $dir, $search) ?>">
                <span class="icon is-small"><i class="fas fa-check-circle"></i></span>
                <span>Approved</span>
            </a>
        </li>
        <li class="<?= $statusFilter === 'archived' ? 'is-active' : '' ?>">
            <a href="<?= filterUrl('archived', $sort, $dir, $search) ?>">
                <span class="icon is-small"><i class="fas fa-archive"></i></span>
                <span>Archived</span>
            </a>
        </li>
    </ul>
</div>

<?php if (empty($assessments)): ?>
<!-- ── Empty state ──────────────────────────────────────────────────────────── -->
<div class="box has-text-centered py-6">
    <p class="has-text-grey-light mb-3">
        <span class="icon is-large"><i class="fas fa-clipboard-list fa-3x"></i></span>
    </p>
    <?php if ($search !== '' || $statusFilter !== ''): ?>
    <p class="title is-5 has-text-grey">No assessments match your filters</p>
    <p class="has-text-grey is-size-6 mb-4">Try clearing the search or changing the status tab.</p>
    <a class="button is-light is-small" href="/assessments">Clear filters</a>
    <?php elseif ($isViewer): ?>
    <p class="title is-5 has-text-grey">No assessments shared with you yet</p>
    <p class="has-text-grey is-size-6">Assessments shared with you will appear here.</p>
    <?php else: ?>
    <p class="title is-5 has-text-grey">No assessments yet</p>
    <p class="has-text-grey is-size-6 mb-4">Create your first risk assessment to get started.</p>
    <a class="button is-link" href="/assessments/new">
        <span class="icon"><i class="fas fa-plus"></i></span>
        <span>Create Assessment</span>
    </a>
    <?php endif; ?>
</div>

<?php else: ?>
<!-- ── Assessment table ──────────────────────────────────────────────────────── -->
<div class="box p-0" style="overflow-x:auto;">
    <table class="table is-fullwidth is-hoverable" id="assessment-list-table">
        <thead>
            <tr>
                <th>
                    <a href="<?= sortUrl('title', $sort, $dir, $search, $statusFilter) ?>" class="has-text-dark">
                        Title <?= sortIcon('title', $sort, $dir) ?>
                    </a>
                </th>
                <th>Reference</th>
                <th>Template</th>
                <th>Matrix</th>
                <th class="has-text-centered">
                    <a href="<?= sortUrl('row_count', $sort, $dir, $search, $statusFilter) ?>" class="has-text-dark">
                        Rows <?= sortIcon('row_count', $sort, $dir) ?>
                    </a>
                </th>
                <th>
                    <a href="<?= sortUrl('status', $sort, $dir, $search, $statusFilter) ?>" class="has-text-dark">
                        Status <?= sortIcon('status', $sort, $dir) ?>
                    </a>
                </th>
                <th>Highest Risk</th>
                <th>
                    <a href="<?= sortUrl('updated_at', $sort, $dir, $search, $statusFilter) ?>" class="has-text-dark">
                        Updated <?= sortIcon('updated_at', $sort, $dir) ?>
                    </a>
                </th>
                <th style="width:120px;"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($assessments as $a):
                $isOwned     = (int) $a['owner_id'] === $userId;
                $statusLabel = $statusLabels[$a['status']] ?? $a['status'];
                $statusColor = $statusColors[$a['status']] ?? 'is-light';
                $tLabel      = $templateLabels[$a['template_type']] ?? $a['template_type'];
                $updatedAt   = date('d M Y', strtotime($a['updated_at']));
                $riskCat     = $a['highest_risk_category'] ?? null;
                $riskColour  = $a['highest_risk_colour']   ?? null;
            ?>
            <tr>
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
                <td>
                    <?php if ($riskCat !== null): ?>
                    <span class="tag is-small risk-badge"
                          style="background-color:<?= htmlspecialchars($riskColour ?? '#ccc') ?>;<?= listRiskBadgeStyle($riskColour ?? '#ccc') ?>">
                        <?= htmlspecialchars($riskCat) ?>
                    </span>
                    <?php else: ?>
                    <span class="has-text-grey-light is-size-7">—</span>
                    <?php endif; ?>
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
<p class="has-text-grey is-size-7 mt-2">
    <?= count($assessments) ?> assessment<?= count($assessments) !== 1 ? 's' : '' ?>
    <?php if ($search !== '' || $statusFilter !== ''): ?>
    — <a href="/assessments" class="has-text-grey">clear filters</a>
    <?php endif; ?>
</p>
<?php endif; ?>
