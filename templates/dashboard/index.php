<?php
use App\Core\Session;
Session::start();

$userName     = Session::get('user_name', 'there');
$userRole     = Session::userRole() ?? 'viewer';
$isViewer     = $userRole === 'viewer';

$stats        = $stats        ?? ['total' => 0, 'drafts' => 0, 'shared_with_me' => 0, 'overdue' => 0];
$recent       = $recent       ?? [];
$statusLabels = $statusLabels ?? [];
$statusColors = $statusColors ?? [];
?>

<!-- ── Page header ─────────────────────────────────────────────────────────── -->
<div class="level mb-5">
    <div class="level-left">
        <div class="level-item">
            <div>
                <h1 class="title is-4 mb-1">Dashboard</h1>
                <p class="subtitle is-6 has-text-grey">Welcome back, <?= htmlspecialchars($userName) ?></p>
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

<!-- ── Quick Stats ──────────────────────────────────────────────────────────── -->
<div class="columns is-multiline mb-5">
    <!-- Total assessments -->
    <div class="column is-3-desktop is-6-tablet">
        <div class="box dashboard-stat-card">
            <div class="is-flex is-align-items-center" style="gap:1rem;">
                <div class="dashboard-stat-icon has-background-link-light has-text-link">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div>
                    <p class="dashboard-stat-value"><?= $stats['total'] ?></p>
                    <p class="dashboard-stat-label">Total Assessments</p>
                </div>
            </div>
            <div class="mt-3">
                <a href="/assessments" class="is-size-7 has-text-link">View all →</a>
            </div>
        </div>
    </div>

    <!-- Open drafts -->
    <div class="column is-3-desktop is-6-tablet">
        <div class="box dashboard-stat-card">
            <div class="is-flex is-align-items-center" style="gap:1rem;">
                <div class="dashboard-stat-icon has-background-warning-light has-text-warning-dark">
                    <i class="fas fa-pen"></i>
                </div>
                <div>
                    <p class="dashboard-stat-value"><?= $stats['drafts'] ?></p>
                    <p class="dashboard-stat-label">Open Drafts</p>
                </div>
            </div>
            <div class="mt-3">
                <a href="/assessments?status=draft" class="is-size-7 has-text-warning-dark">View drafts →</a>
            </div>
        </div>
    </div>

    <!-- Shared with me -->
    <div class="column is-3-desktop is-6-tablet">
        <div class="box dashboard-stat-card">
            <div class="is-flex is-align-items-center" style="gap:1rem;">
                <div class="dashboard-stat-icon has-background-info-light has-text-info">
                    <i class="fas fa-share-nodes"></i>
                </div>
                <div>
                    <p class="dashboard-stat-value"><?= $stats['shared_with_me'] ?></p>
                    <p class="dashboard-stat-label">Shared With Me</p>
                </div>
            </div>
            <div class="mt-3">
                <a href="/assessments" class="is-size-7 has-text-info">View shared →</a>
            </div>
        </div>
    </div>

    <!-- Overdue reviews -->
    <div class="column is-3-desktop is-6-tablet">
        <div class="box dashboard-stat-card <?= $stats['overdue'] > 0 ? 'dashboard-stat-card--alert' : '' ?>">
            <div class="is-flex is-align-items-center" style="gap:1rem;">
                <div class="dashboard-stat-icon <?= $stats['overdue'] > 0 ? 'has-background-danger-light has-text-danger' : 'has-background-grey-lighter has-text-grey' ?>">
                    <i class="fas fa-calendar-xmark"></i>
                </div>
                <div>
                    <p class="dashboard-stat-value <?= $stats['overdue'] > 0 ? 'has-text-danger' : '' ?>">
                        <?= $stats['overdue'] ?>
                        <?php if ($stats['overdue'] > 0): ?>
                        <span class="is-size-7 has-text-danger ml-1" title="Review dates passed">
                            <i class="fas fa-triangle-exclamation"></i>
                        </span>
                        <?php endif; ?>
                    </p>
                    <p class="dashboard-stat-label">Overdue Reviews</p>
                </div>
            </div>
            <div class="mt-3">
                <?php if ($stats['overdue'] > 0): ?>
                    <a href="/assessments?overdue=1" class="is-size-7 has-text-danger">View overdue →</a>
                <?php else: ?>
                    <span class="is-size-7 has-text-grey">All reviews on track</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ── Recent Assessments ───────────────────────────────────────────────────── -->
<div class="mb-5">
    <div class="level mb-3">
        <div class="level-left">
            <div class="level-item">
                <h2 class="title is-5 mb-0">Recent Assessments</h2>
            </div>
        </div>
        <div class="level-right">
            <div class="level-item">
                <a href="/assessments" class="is-size-7 has-text-link">View all assessments →</a>
            </div>
        </div>
    </div>

    <?php if (empty($recent)): ?>
    <div class="box has-text-centered py-5">
        <p class="has-text-grey mb-3"><span class="icon is-large"><i class="fas fa-clipboard-list fa-2x"></i></span></p>
        <p class="has-text-grey">No assessments yet.</p>
        <?php if (!$isViewer): ?>
        <a class="button is-link is-small mt-3" href="/assessments/new">
            <span class="icon"><i class="fas fa-plus"></i></span>
            <span>Create your first assessment</span>
        </a>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <div class="box p-0" style="overflow-x:auto;">
        <table class="table is-fullwidth is-hoverable is-size-7 mb-0">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Reference</th>
                    <th>Matrix</th>
                    <th>Status</th>
                    <th>Highest Risk</th>
                    <th>Updated</th>
                    <th style="width:60px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $userId = (int) Session::get('user_id');
                foreach ($recent as $a):
                    $statusLabel = $statusLabels[$a['status']] ?? $a['status'];
                    $statusColor = $statusColors[$a['status']] ?? 'is-light';
                    $isOwned     = (int) $a['owner_id'] === $userId;
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
                        <span class="tag is-light is-small ml-1">shared</span>
                        <?php endif; ?>
                    </td>
                    <td class="has-text-grey"><?= htmlspecialchars($a['reference_number'] ?? '—') ?></td>
                    <td class="has-text-grey"><?= htmlspecialchars($a['matrix_name'] ?? '') ?></td>
                    <td><span class="tag <?= $statusColor ?> is-small"><?= htmlspecialchars($statusLabel) ?></span></td>
                    <td>
                        <?php if ($riskCat !== null): ?>
                        <span class="tag is-small risk-badge"
                              style="background-color:<?= htmlspecialchars($riskColour ?? '#ccc') ?>;<?= riskBadgeStyle($riskColour ?? '#ccc') ?>">
                            <?= htmlspecialchars($riskCat) ?>
                        </span>
                        <?php else: ?>
                        <span class="has-text-grey-light">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="has-text-grey"><?= $updatedAt ?></td>
                    <td>
                        <a class="button is-link is-outlined is-small" href="/assessments/<?= $a['id'] ?>">
                            <span class="icon"><i class="fas fa-pen-to-square"></i></span>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- ── Quick Navigation ─────────────────────────────────────────────────────── -->
<div class="columns">
    <div class="column is-4">
        <div class="box has-text-centered py-4">
            <p class="is-size-4 mb-2 has-text-link"><i class="fas fa-clipboard-list"></i></p>
            <p class="title is-6 mb-1">Assessments</p>
            <p class="has-text-grey is-size-7 mb-3"><?= $isViewer ? 'View assessments shared with you' : 'Create, manage and export risk assessments' ?></p>
            <a class="button is-link is-outlined is-small" href="/assessments">View All</a>
        </div>
    </div>
    <div class="column is-4">
        <div class="box has-text-centered py-4">
            <p class="is-size-4 mb-2 has-text-info"><i class="fas fa-table-cells"></i></p>
            <p class="title is-6 mb-1">Risk Matrices</p>
            <p class="has-text-grey is-size-7 mb-3">Browse system matrices and build your own</p>
            <a class="button is-info is-outlined is-small" href="/matrices">View Matrices</a>
        </div>
    </div>
    <div class="column is-4">
        <div class="box has-text-centered py-4">
            <p class="is-size-4 mb-2 has-text-success"><i class="fas fa-book"></i></p>
            <p class="title is-6 mb-1">Library</p>
            <p class="has-text-grey is-size-7 mb-3">Reusable hazards, effects and controls</p>
            <a class="button is-success is-outlined is-small" href="/library">Open Library</a>
        </div>
    </div>
</div>

<?php
/**
 * Inline helper: return a CSS style string for readable text on a given hex background.
 * Returns dark text for light backgrounds, white for dark.
 */
function riskBadgeStyle(string $hex): string
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
