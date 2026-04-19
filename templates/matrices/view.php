<?php
use App\Core\Session;
use App\Helpers\Csrf;

Session::start();

$matrix     = $matrix     ?? [];
$csrf       = $csrf       ?? '';
$canClone   = $canClone   ?? false;

$m          = $matrix;
$sevLevels  = $m['severity_levels']  ?? [];
$likLevels  = $m['likelihood_levels'] ?? [];
$bands      = $m['bands']             ?? [];
$cells      = $m['cells']             ?? [];
$categories = $m['categories']        ?? [];

$isSystem   = (bool) ($m['is_system'] ?? false);
$isOwner    = (int) ($m['owner_id'] ?? 0) === (int) Session::get('user_id');

// Build a quick cell lookup fn
$getCell = function(int $s, int $l) use ($cells): ?array {
    return $cells[$s . '_' . $l] ?? null;
};

// Determine luminance from hex to choose white or dark text on cell background
function textColorFor(string $hex): string
{
    $hex = ltrim($hex, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $lum = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    return $lum > 0.55 ? '#363636' : '#ffffff';
}
?>

<!-- ── Page header ─────────────────────────────────────────────────────────── -->
<div class="level mb-4">
    <div class="level-left">
        <div class="level-item">
            <div>
                <nav class="breadcrumb is-size-7 mb-1" aria-label="breadcrumbs">
                    <ul>
                        <li><a href="/matrices">Risk Matrices</a></li>
                        <li class="is-active"><a href="#"><?= htmlspecialchars($m['name']) ?></a></li>
                    </ul>
                </nav>
                <h1 class="title is-4 mb-1">
                    <?= htmlspecialchars($m['name']) ?>
                    <?php if ($isSystem): ?>
                    <span class="tag is-ocean ml-2 is-size-7">System</span>
                    <?php endif; ?>
                </h1>
                <p class="subtitle is-6 has-text-grey" style="max-width:60ch;">
                    <?= htmlspecialchars($m['description'] ?? '') ?>
                </p>
            </div>
        </div>
    </div>
    <div class="level-right">
        <div class="level-item" style="gap:0.5rem;display:flex;">
            <?php if ($canClone): ?>
            <!-- Clone button — opens naming modal -->
            <button class="button is-link js-clone-btn"
                    type="button"
                    data-matrix-id="<?= $m['id'] ?>"
                    data-matrix-name="<?= htmlspecialchars($m['name'], ENT_QUOTES) ?>"
                    title="Create your own editable copy of this matrix">
                <span class="icon"><i class="fas fa-copy"></i></span>
                <span>Clone Matrix</span>
            </button>
            <?php if ($isOwner && !$isSystem): ?>
            <a class="button is-info is-outlined" href="/matrices/<?= $m['id'] ?>/edit">
                <span class="icon"><i class="fas fa-pencil"></i></span>
                <span>Edit</span>
            </a>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ── Page content: sticky grid+bands, then scrollable reference tables ────── -->
<div class="matrix-page-wrapper">

    <!-- ── Sticky top: matrix grid + risk bands ───────────────────────────── -->
    <div class="matrix-sticky-header">
        <div class="columns is-desktop mb-0">

            <!-- Left: matrix grid -->
            <div class="column">
                <div class="box p-4">
                    <h2 class="title is-6 mb-3">
                        <span class="icon-text">
                            <span class="icon has-text-teal"><i class="fas fa-table-cells-large"></i></span>
                            <span>Risk Matrix Grid</span>
                        </span>
                    </h2>

                    <?php if (empty($sevLevels) || empty($likLevels)): ?>
                    <p class="has-text-grey">Matrix data not available.</p>
                    <?php else: ?>

                    <!-- Axis labels -->
                    <p class="is-size-7 has-text-grey mb-1">
                        <strong class="has-text-ocean"><?= htmlspecialchars($m['severity_axis_label']) ?></strong>
                        (rows, highest at top) ×
                        <strong class="has-text-ocean"><?= htmlspecialchars($m['likelihood_axis_label']) ?></strong>
                        (columns, lowest at left)
                    </p>

                    <div class="matrix-grid-wrapper">
                        <table class="matrix-grid-table">
                            <thead>
                                <tr>
                                    <!-- Top-left corner: axis label -->
                                    <th class="matrix-corner">
                                        <div class="matrix-corner-sev"><?= htmlspecialchars($m['severity_axis_label']) ?></div>
                                        <div class="matrix-corner-lik"><?= htmlspecialchars($m['likelihood_axis_label']) ?></div>
                                    </th>
                                    <?php foreach ($likLevels as $lik): ?>
                                    <th class="matrix-axis-cell matrix-lik-header"
                                        title="<?= htmlspecialchars($lik['description'] ?? '') ?>">
                                        <div class="matrix-level-val"><?= $lik['level_value'] ?></div>
                                        <div class="matrix-level-label"><?= htmlspecialchars($lik['label']) ?></div>
                                        <?php if ($lik['one_word'] && $lik['one_word'] !== $lik['label']): ?>
                                        <div class="matrix-level-word"><?= htmlspecialchars($lik['one_word']) ?></div>
                                        <?php endif; ?>
                                        <?php if ($lik['quantitative_range']): ?>
                                        <div class="matrix-level-quant"><?= htmlspecialchars($lik['quantitative_range']) ?></div>
                                        <?php endif; ?>
                                    </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Render severity rows highest → lowest (most severe at top)
                                $sevDesc = array_reverse($sevLevels);
                                foreach ($sevDesc as $sev):
                                ?>
                                <tr>
                                    <th class="matrix-axis-cell matrix-sev-header"
                                        title="<?= htmlspecialchars($sev['description'] ?? '') ?>">
                                        <div class="matrix-level-val"><?= $sev['level_value'] ?></div>
                                        <div class="matrix-level-label"><?= htmlspecialchars($sev['label']) ?></div>
                                        <?php if ($sev['one_word'] && $sev['one_word'] !== $sev['label']): ?>
                                        <div class="matrix-level-word"><?= htmlspecialchars($sev['one_word']) ?></div>
                                        <?php endif; ?>
                                    </th>
                                    <?php foreach ($likLevels as $lik):
                                        $cell = $getCell((int)$sev['level_value'], (int)$lik['level_value']);
                                        $bg   = $cell['colour_hex']  ?? '#cccccc';
                                        $cat  = $cell['risk_category'] ?? '—';
                                        $score = $cell['numeric_score'] ?? '';
                                        $fg   = textColorFor($bg);
                                        $bandName = htmlspecialchars($cell['band_short_description'] ?? $cat);
                                    ?>
                                    <td class="matrix-risk-cell"
                                        style="background-color:<?= $bg ?>;color:<?= $fg ?>;"
                                        data-bs-toggle="tooltip"
                                        title="S<?= $sev['level_value'] ?>×L<?= $lik['level_value'] ?> = <?= $score ?> | <?= htmlspecialchars($cat) ?>"
                                        <?php if ($isSystem): ?>data-readonly="true"<?php endif; ?>>
                                        <div class="matrix-risk-label"><?= htmlspecialchars($cat) ?></div>
                                        <?php if ($score !== ''): ?>
                                        <div class="matrix-risk-score"><?= $score ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php endif; ?>
                </div>
            </div>

            <!-- Right: risk bands legend -->
            <div class="column is-one-third-desktop">
                <div class="box p-4">
                    <h2 class="title is-6 mb-3">
                        <span class="icon-text">
                            <span class="icon has-text-teal"><i class="fas fa-bars"></i></span>
                            <span>Risk Bands</span>
                        </span>
                    </h2>
                    <?php if (empty($bands)): ?>
                    <p class="has-text-grey is-size-7">No bands defined.</p>
                    <?php else: ?>
                    <div class="risk-bands-list">
                        <?php foreach ($bands as $band): ?>
                        <?php
                            $bg = $band['colour_hex'];
                            $fg = textColorFor($bg);
                        ?>
                        <div class="risk-band-row mb-3">
                            <div class="risk-band-swatch"
                                 style="background-color:<?= $bg ?>;color:<?= $fg ?>;">
                                <span class="band-label"><?= htmlspecialchars($band['band_label']) ?></span>
                                <span class="band-name">
                                    <?= htmlspecialchars($band['band_name']) ?>
                                </span>
                                <?php if ($band['score_min'] !== null && $band['score_max'] !== null): ?>
                                <span class="band-score-range">
                                    <?php
                                        $sMin = (int) $band['score_min'];
                                        $sMax = (int) $band['score_max'];
                                        if ($sMin === $sMax) {
                                            echo 'Score: ' . $sMin;
                                        } else {
                                            echo 'Score: ' . $sMin . '–' . $sMax;
                                        }
                                    ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <?php if ($band['short_description']): ?>
                            <p class="is-size-7 has-text-weight-semibold mt-1 mb-0">
                                <?= htmlspecialchars($band['short_description']) ?>
                            </p>
                            <?php endif; ?>
                            <?php if ($band['full_description']): ?>
                            <p class="is-size-7 has-text-grey mt-0">
                                <?= htmlspecialchars($band['full_description']) ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div><!-- /.matrix-sticky-header -->

    <!-- ── Reference tables (full-width, side-by-side on desktop) ────────── -->
    <?php if (!empty($likLevels) || !empty($sevLevels)): ?>
    <div class="columns is-desktop mt-4">

        <?php if (!empty($likLevels)): ?>
        <div class="column">
            <div class="box p-4">
                <h2 class="title is-6 mb-3">
                    <span class="icon-text">
                        <span class="icon has-text-teal"><i class="fas fa-arrow-trend-up"></i></span>
                        <span><?= htmlspecialchars($m['likelihood_axis_label']) ?> Scale</span>
                    </span>
                </h2>
                <div class="table-container">
                    <table class="table is-fullwidth is-hoverable is-narrow ref-table">
                        <thead>
                            <tr>
                                <th style="width:3rem;">#</th>
                                <th>Level</th>
                                <?php if (array_filter($likLevels, fn($l) => $l['one_word'] && $l['one_word'] !== $l['label'])): ?>
                                <th>One Word</th>
                                <?php endif; ?>
                                <?php if (array_filter($likLevels, fn($l) => $l['quantitative_range'])): ?>
                                <th>Frequency / Range</th>
                                <?php endif; ?>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $hasOneWord = (bool) array_filter($likLevels, fn($l) => $l['one_word'] && $l['one_word'] !== $l['label']);
                            $hasQuant   = (bool) array_filter($likLevels, fn($l) => $l['quantitative_range']);
                            foreach ($likLevels as $lik):
                            ?>
                            <tr>
                                <td class="has-text-weight-bold has-text-ocean"><?= $lik['level_value'] ?></td>
                                <td><strong><?= htmlspecialchars($lik['label']) ?></strong></td>
                                <?php if ($hasOneWord): ?>
                                <td><?= htmlspecialchars($lik['one_word'] ?? '') ?></td>
                                <?php endif; ?>
                                <?php if ($hasQuant): ?>
                                <td class="is-family-monospace is-size-7"><?= htmlspecialchars($lik['quantitative_range'] ?? '—') ?></td>
                                <?php endif; ?>
                                <td class="is-size-7"><?= htmlspecialchars($lik['description'] ?? '—') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($sevLevels)): ?>
        <div class="column">
            <div class="box p-4">
                <h2 class="title is-6 mb-3">
                    <span class="icon-text">
                        <span class="icon has-text-teal"><i class="fas fa-triangle-exclamation"></i></span>
                        <span><?= htmlspecialchars($m['severity_axis_label']) ?> / Consequence Descriptions</span>
                    </span>
                </h2>
                <div class="table-container">
                    <table class="table is-fullwidth is-hoverable is-narrow ref-table">
                        <thead>
                            <tr>
                                <th style="width:3rem;">#</th>
                                <th>Level</th>
                                <th>Description</th>
                                <?php foreach ($categories as $cat): ?>
                                <th><?= htmlspecialchars($cat['name']) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sevLevels as $sev): ?>
                            <tr>
                                <td class="has-text-weight-bold has-text-ocean"><?= $sev['level_value'] ?></td>
                                <td><strong><?= htmlspecialchars($sev['label']) ?></strong></td>
                                <td class="is-size-7"><?= htmlspecialchars($sev['description'] ?? '—') ?></td>
                                <?php foreach ($categories as $cat): ?>
                                <td class="is-size-7">
                                    <?= htmlspecialchars($cat['descriptions'][$sev['level_value']] ?? '—') ?>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
    <?php endif; ?>

</div><!-- /.matrix-page-wrapper -->

<?php if ($isSystem): ?>
<!-- ── System read-only banner ─────────────────────────────────────────────── -->
<div class="notification is-warning is-light mt-4">
    <span class="icon-text">
        <span class="icon has-text-warning-dark"><i class="fas fa-lock"></i></span>
        <span>
            <strong>This is a read-only system matrix.</strong>
            <?php if ($canClone): ?>
            <a href="#"
               class="js-clone-btn"
               data-matrix-id="<?= $m['id'] ?>"
               data-matrix-name="<?= htmlspecialchars($m['name'], ENT_QUOTES) ?>"
               onclick="event.preventDefault();">
                Clone it
            </a>
            to create your own fully editable version.
            <?php else: ?>
            Contact an administrator to clone this matrix to your account.
            <?php endif; ?>
        </span>
    </span>
</div>
<?php endif; ?>

<!-- ── Score calculation note ─────────────────────────────────────────────── -->
<div class="notification is-light is-size-7 mt-2">
    <span class="icon-text">
        <span class="icon has-text-grey"><i class="fas fa-circle-info"></i></span>
        <span>
            Risk scores are calculated as
            <strong><?= htmlspecialchars($m['severity_axis_label']) ?></strong>
            value ×
            <strong><?= htmlspecialchars($m['likelihood_axis_label']) ?></strong>
            value.
            <?php if ($m['name'] === 'U.S. Army ATP 5-19 4×5'): ?>
            This matrix uses ordinal (non-numeric) risk rankings assigned per ATP 5-19 doctrine.
            <?php endif; ?>
        </span>
    </span>
</div>

<?php if ($isSystem): ?>
<!-- ── Read-only cell tooltip (injected into <body> via JS) ────────────────── -->
<div id="matrix-readonly-tip" class="matrix-readonly-tip" role="tooltip" aria-live="polite"></div>
<script>
(function () {
    const tip   = document.getElementById('matrix-readonly-tip');
    const cells = document.querySelectorAll('.matrix-risk-cell[data-readonly]');
    if (!tip || !cells.length) return;

    tip.textContent = 'This is a system standard. Clone it to customize.';

    let hideTimer;

    function showTip(cell) {
        clearTimeout(hideTimer);
        const rect  = cell.getBoundingClientRect();
        const scrollX = window.pageXOffset || document.documentElement.scrollLeft;
        const scrollY = window.pageYOffset || document.documentElement.scrollTop;
        tip.style.left = (rect.left + rect.width / 2 + scrollX) + 'px';
        tip.style.top  = (rect.top  + scrollY - 10) + 'px';
        tip.classList.add('is-visible');
        hideTimer = setTimeout(hideTip, 3000);
    }

    function hideTip() {
        tip.classList.remove('is-visible');
    }

    cells.forEach(function (cell) {
        cell.style.cursor = 'pointer';
        cell.addEventListener('click', function (e) {
            e.stopPropagation();
            showTip(cell);
        });
    });

    document.addEventListener('click', hideTip);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') hideTip();
    });
}());
</script>
<?php endif; ?>

<?php if ($canClone): ?>
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

<!-- Hidden form used by the modal -->
<form id="cloneSubmitForm" method="POST" style="display:none;">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" id="cloneNameHidden" name="clone_name" value="">
</form>
<?php endif; ?>

<?php if ($canClone): ?>
<script>
(function () {
    const modal      = document.getElementById('cloneNameModal');
    const nameInput  = document.getElementById('cloneNameInput');
    const hiddenName = document.getElementById('cloneNameHidden');
    const confirmBtn = document.getElementById('cloneConfirmBtn');
    const submitForm = document.getElementById('cloneSubmitForm');

    function openModal(matrixId, matrixName) {
        nameInput.value = 'Copy of ' + matrixName;
        submitForm.action = '/matrices/' + matrixId + '/copy';
        modal.classList.add('is-active');
        nameInput.select();
    }

    function closeModal() {
        modal.classList.remove('is-active');
        nameInput.value = '';
    }

    document.querySelectorAll('.js-clone-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            openModal(btn.dataset.matrixId, btn.dataset.matrixName);
        });
    });

    document.getElementById('cloneModalBg').addEventListener('click', closeModal);
    document.querySelectorAll('.js-close-clone-modal').forEach(function (el) {
        el.addEventListener('click', closeModal);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('is-active')) closeModal();
    });

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

    nameInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); confirmBtn.click(); }
    });
    nameInput.addEventListener('input', function () {
        nameInput.classList.remove('is-danger');
    });
}());
</script>
<?php endif; ?>

<style>
/* ── Sticky header (grid + bands) ────────────────────────────────────────── */
.matrix-sticky-header {
    position: sticky;
    top: 3.25rem;           /* sit flush below the fixed navbar              */
    z-index: 20;
    background-color: #f7f9fa;
    padding-bottom: 0.5rem;
    box-shadow: 0 3px 10px rgba(0, 30, 50, 0.10);
    margin-bottom: 0;
}

/* ── Read-only cell tooltip ───────────────────────────────────────────────── */
.matrix-readonly-tip {
    position: absolute;
    background: rgba(23, 37, 51, 0.93);
    color: #fff;
    font-size: 0.73rem;
    font-family: 'Montserrat', system-ui, sans-serif;
    padding: 0.38rem 0.75rem;
    border-radius: 5px;
    white-space: nowrap;
    pointer-events: none;
    opacity: 0;
    transform: translateX(-50%) translateY(-100%);
    transition: opacity 0.18s ease;
    z-index: 500;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.28);
}
.matrix-readonly-tip.is-visible {
    opacity: 1;
}
.matrix-readonly-tip::after {
    content: '';
    position: absolute;
    left: 50%;
    bottom: -5px;
    transform: translateX(-50%);
    border-width: 5px 5px 0;
    border-style: solid;
    border-color: rgba(23, 37, 51, 0.93) transparent transparent;
}

/* ── Matrix grid ──────────────────────────────────────────────────────────── */
.matrix-grid-wrapper {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.matrix-grid-table {
    border-collapse: collapse;
    min-width: 100%;
}

.matrix-corner {
    background: transparent;
    border: none;
    position: relative;
    width: 6rem;
    min-width: 5rem;
}

/* Diagonal divider in the corner cell */
.matrix-corner::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(
        to bottom right,
        transparent calc(50% - 0.5px),
        #c8d8e0 calc(50% - 0.5px),
        #c8d8e0 calc(50% + 0.5px),
        transparent calc(50% + 0.5px)
    );
    pointer-events: none;
}

.matrix-corner-sev {
    position: absolute;
    top: 0.35rem;
    right: 0.4rem;
    font-size: 0.65rem;
    font-weight: 700;
    color: var(--ocean);
    text-align: right;
}

.matrix-corner-lik {
    position: absolute;
    bottom: 0.35rem;
    left: 0.4rem;
    font-size: 0.65rem;
    font-weight: 700;
    color: var(--ocean);
    text-align: left;
}

/* Axis headers (severity row header / likelihood column header) */
.matrix-axis-cell {
    background-color: #f0f5f8;
    border: 1px solid #d0dfe6;
    padding: 0.35rem 0.5rem;
    text-align: center;
    min-width: 4.5rem;
    vertical-align: middle;
}

.matrix-sev-header {
    text-align: right;
    min-width: 6rem;
    max-width: 8rem;
}

.matrix-lik-header {
    min-width: 5rem;
}

.matrix-level-val {
    font-size: 0.65rem;
    color: var(--text-muted);
    font-family: 'Montserrat', system-ui, sans-serif;
}

.matrix-level-label {
    font-size: 0.7rem;
    font-weight: 700;
    color: var(--ocean);
    font-family: 'Montserrat', system-ui, sans-serif;
    line-height: 1.2;
}

.matrix-level-word {
    font-size: 0.62rem;
    color: var(--text-muted);
    font-style: italic;
}

.matrix-level-quant {
    font-size: 0.58rem;
    color: var(--text-muted);
    font-family: monospace;
}

/* Risk cells */
.matrix-risk-cell {
    border: 1px solid rgba(0,0,0,0.08);
    padding: 0.4rem 0.5rem;
    text-align: center;
    cursor: default;
    transition: filter 0.1s, transform 0.1s;
}

.matrix-risk-cell:hover {
    filter: brightness(1.08);
    transform: scale(1.04);
    z-index: 2;
    position: relative;
    box-shadow: 0 2px 8px rgba(0,0,0,0.18);
}

.matrix-risk-label {
    font-size: 0.68rem;
    font-weight: 700;
    font-family: 'Montserrat', system-ui, sans-serif;
    line-height: 1.2;
}

.matrix-risk-score {
    font-size: 0.62rem;
    opacity: 0.8;
    margin-top: 1px;
}

/* ── Risk bands panel ─────────────────────────────────────────────────────── */
.risk-band-swatch {
    border-radius: 4px;
    padding: 0.4rem 0.7rem;
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-family: 'Montserrat', system-ui, sans-serif;
}

.band-label {
    font-size: 0.7rem;
    font-weight: 700;
    opacity: 0.9;
    min-width: 1.5rem;
}

.band-name {
    font-size: 0.85rem;
    font-weight: 700;
    flex: 1;
}

.band-score-range {
    font-size: 0.65rem;
    opacity: 0.85;
    font-family: monospace;
    white-space: nowrap;
}

/* ── Reference tables ─────────────────────────────────────────────────────── */
.ref-table thead th {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--text-muted);
    border-bottom: 2px solid #d0dfe6;
}

.ref-table td {
    vertical-align: top;
    padding: 0.45rem 0.6rem;
}

@media screen and (max-width: 768px) {
    .matrix-axis-cell {
        min-width: 3.5rem;
        padding: 0.25rem;
    }
    .matrix-sev-header {
        min-width: 4rem;
    }
    .matrix-risk-label {
        font-size: 0.58rem;
    }
}
</style>
