<?php
use App\Core\Session;
use App\Helpers\Csrf;

Session::start();

$matrices = $matrices ?? [];

// Partition into system and user-owned
$systemMatrices = array_filter($matrices, fn($m) => $m['is_system']);
$ownedMatrices  = array_filter($matrices, fn($m) => !$m['is_system'] && (int)$m['owner_id'] === (int)Session::get('user_id'));

/**
 * Returns a teal badge for a dimension count like "5×5".
 */
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
            <div class="mt-4 is-flex is-gap-2" style="gap:0.5rem;">
                <a class="button is-link is-small is-outlined" href="/matrices/<?= $m['id'] ?>">
                    <span class="icon"><i class="fas fa-eye"></i></span>
                    <span>View</span>
                </a>
                <form method="POST" action="/matrices/<?= $m['id'] ?>/copy" style="display:inline;">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">
                    <button class="button is-teal is-small" type="submit"
                            title="Create your own editable copy of this matrix">
                        <span class="icon"><i class="fas fa-copy"></i></span>
                        <span>Clone</span>
                    </button>
                </form>
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
                <form method="POST" action="/matrices/<?= $m['id'] ?>/copy" style="display:inline;">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>">
                    <button class="button is-small" type="submit">
                        <span class="icon"><i class="fas fa-copy"></i></span>
                        <span>Clone</span>
                    </button>
                </form>
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
<!-- Empty state for custom matrices -->
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
