<?php
use App\Helpers\Csrf;
$formError = $formError ?? null;
$success   = $success ?? null;
$old       = $old ?? [];
?>
<div class="box">
    <h1 class="title is-4 mb-2">Reset Password</h1>
    <p class="has-text-grey mb-4 is-size-7">
        Enter your email address and we'll send you a link to reset your password.
    </p>

    <?php if ($success): ?>
    <div class="notification is-success is-light">
        <span class="icon-text">
            <span class="icon"><i class="fas fa-circle-check"></i></span>
            <span><?= htmlspecialchars($success) ?></span>
        </span>
    </div>
    <?php else: ?>

    <?php if ($formError): ?>
    <div class="notification is-danger is-light">
        <span class="icon-text">
            <span class="icon"><i class="fas fa-circle-exclamation"></i></span>
            <span><?= htmlspecialchars($formError) ?></span>
        </span>
    </div>
    <?php endif; ?>

    <form method="POST" action="/auth/forgot-password" novalidate>
        <?= Csrf::field() ?>

        <div class="field">
            <label class="label" for="email">Email address</label>
            <div class="control has-icons-left">
                <input
                    class="input"
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                    autocomplete="email"
                    autofocus
                    required
                >
                <span class="icon is-left"><i class="fas fa-envelope"></i></span>
            </div>
        </div>

        <div class="field mt-5">
            <div class="control">
                <button class="button is-link is-fullwidth" type="submit">
                    <span class="icon"><i class="fas fa-paper-plane"></i></span>
                    <span>Send Reset Link</span>
                </button>
            </div>
        </div>
    </form>

    <?php endif; ?>
</div>

<div class="has-text-centered mt-4">
    <a href="/auth/login" class="has-text-grey is-size-7">
        <span class="icon-text">
            <span class="icon"><i class="fas fa-arrow-left"></i></span>
            <span>Back to log in</span>
        </span>
    </a>
</div>
