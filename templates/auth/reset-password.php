<?php
use App\Helpers\Csrf;
$errors  = $errors ?? [];
$token   = $token ?? '';
$invalid = $invalid ?? false;
?>
<div class="box">
    <h1 class="title is-4 mb-4">Set New Password</h1>

    <?php if ($invalid): ?>
    <div class="notification is-danger is-light">
        <span class="icon-text">
            <span class="icon"><i class="fas fa-circle-exclamation"></i></span>
            <span>This password reset link is invalid or has expired.</span>
        </span>
    </div>
    <div class="has-text-centered mt-4">
        <a href="/auth/forgot-password" class="button is-link is-outlined">Request a new link</a>
    </div>
    <?php else: ?>

    <form method="POST" action="/auth/reset-password/<?= htmlspecialchars($token) ?>" novalidate>
        <?= Csrf::field() ?>

        <div class="field">
            <label class="label" for="password">New password</label>
            <div class="control has-icons-left has-icons-right">
                <input
                    class="input<?= isset($errors['password']) ? ' is-danger' : '' ?>"
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="new-password"
                    autofocus
                    required
                    minlength="8"
                >
                <span class="icon is-left"><i class="fas fa-lock"></i></span>
                <span class="icon is-right is-clickable" id="togglePassword" title="Show/hide password" style="pointer-events:all;">
                    <i class="fas fa-eye"></i>
                </span>
            </div>
            <p class="help">Minimum 8 characters.</p>
            <?php if (isset($errors['password'])): ?>
            <p class="help is-danger"><?= htmlspecialchars($errors['password'][0]) ?></p>
            <?php endif; ?>
        </div>

        <div class="field">
            <label class="label" for="password_confirm">Confirm new password</label>
            <div class="control has-icons-left">
                <input
                    class="input<?= isset($errors['password_confirm']) ? ' is-danger' : '' ?>"
                    type="password"
                    id="password_confirm"
                    name="password_confirm"
                    autocomplete="new-password"
                    required
                >
                <span class="icon is-left"><i class="fas fa-lock"></i></span>
            </div>
            <?php if (isset($errors['password_confirm'])): ?>
            <p class="help is-danger"><?= htmlspecialchars($errors['password_confirm'][0]) ?></p>
            <?php endif; ?>
        </div>

        <div class="field mt-5">
            <div class="control">
                <button class="button is-link is-fullwidth" type="submit">
                    <span class="icon"><i class="fas fa-key"></i></span>
                    <span>Set New Password</span>
                </button>
            </div>
        </div>
    </form>

    <?php endif; ?>
</div>
