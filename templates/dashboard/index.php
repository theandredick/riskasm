<?php
use App\Core\Session;
Session::start();
$userName = Session::get('user_name', 'there');
$isViewer = (Session::userRole() ?? 'viewer') === 'viewer';
?>
<div class="level mb-5">
    <div class="level-left">
        <div class="level-item">
            <div>
                <h1 class="title is-4">Dashboard</h1>
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

<div class="columns">
    <div class="column is-4">
        <div class="box has-text-centered py-6">
            <p class="is-size-3 mb-3 has-text-link"><i class="fas fa-clipboard-list"></i></p>
            <p class="title is-5 mb-1">Assessments</p>
            <p class="has-text-grey is-size-7 mb-4"><?= $isViewer ? 'View and comment on risk assessments shared with you' : 'Create, manage and export risk assessments' ?></p>
            <a class="button is-link is-outlined" href="/assessments">View All</a>
        </div>
    </div>
    <div class="column is-4">
        <div class="box has-text-centered py-6">
            <p class="is-size-3 mb-3 has-text-info"><i class="fas fa-table-cells"></i></p>
            <p class="title is-5 mb-1">Risk Matrices</p>
            <p class="has-text-grey is-size-7 mb-4">Browse system matrices and build your own</p>
            <a class="button is-info is-outlined" href="/matrices">View Matrices</a>
        </div>
    </div>
    <div class="column is-4">
        <div class="box has-text-centered py-6">
            <p class="is-size-3 mb-3 has-text-success"><i class="fas fa-book"></i></p>
            <p class="title is-5 mb-1">Library</p>
            <p class="has-text-grey is-size-7 mb-4">Reusable hazards, effects and controls</p>
            <a class="button is-success is-outlined" href="/library">Open Library</a>
        </div>
    </div>
</div>

<div class="notification is-info is-light mt-4">
    <span class="icon-text">
        <span class="icon"><i class="fas fa-circle-info"></i></span>
        <span><strong>Getting started?</strong> Create a new assessment, or browse the risk matrix library to explore built-in industry matrices.</span>
    </span>
</div>
