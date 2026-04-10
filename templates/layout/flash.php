<?php
use App\Core\Session;
Session::start();

$flashSuccess = Session::getFlash('success');
$flashError   = Session::getFlash('error');
$flashInfo    = Session::getFlash('info');
$flashWarning = Session::getFlash('warning');
?>
<?php if ($flashSuccess): ?>
<div class="notification is-success is-light mb-4 flash-notification">
    <button class="delete" type="button"></button>
    <span class="icon-text">
        <span class="icon"><i class="fas fa-circle-check"></i></span>
        <span><?= htmlspecialchars($flashSuccess) ?></span>
    </span>
</div>
<?php endif; ?>
<?php if ($flashError): ?>
<div class="notification is-danger is-light mb-4 flash-notification">
    <button class="delete" type="button"></button>
    <span class="icon-text">
        <span class="icon"><i class="fas fa-circle-exclamation"></i></span>
        <span><?= htmlspecialchars($flashError) ?></span>
    </span>
</div>
<?php endif; ?>
<?php if ($flashWarning): ?>
<div class="notification is-warning is-light mb-4 flash-notification">
    <button class="delete" type="button"></button>
    <span class="icon-text">
        <span class="icon"><i class="fas fa-triangle-exclamation"></i></span>
        <span><?= htmlspecialchars($flashWarning) ?></span>
    </span>
</div>
<?php endif; ?>
<?php if ($flashInfo): ?>
<div class="notification is-info is-light mb-4 flash-notification">
    <button class="delete" type="button"></button>
    <span class="icon-text">
        <span class="icon"><i class="fas fa-circle-info"></i></span>
        <span><?= htmlspecialchars($flashInfo) ?></span>
    </span>
</div>
<?php endif; ?>
