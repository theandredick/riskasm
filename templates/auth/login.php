<?php
use App\Helpers\Csrf;
$errors    = $errors ?? [];
$old       = $old ?? [];
$formError = $formError ?? null;
$returnUrl = $returnUrl ?? '/';
?>
<div class="box">
    <h1 class="title is-4 mb-4">Log In</h1>

    <?php if ($formError): ?>
    <div class="notification is-danger is-light">
        <span class="icon-text">
            <span class="icon"><i class="fas fa-circle-exclamation"></i></span>
            <span><?= htmlspecialchars($formError) ?></span>
        </span>
    </div>
    <?php endif; ?>

    <form method="POST" action="/auth/login" novalidate>
        <?= Csrf::field() ?>
        <input type="hidden" name="return_url" value="<?= htmlspecialchars($returnUrl) ?>">

        <div class="field">
            <label class="label" for="email">Email address</label>
            <div class="control has-icons-left">
                <input
                    class="input<?= isset($errors['email']) ? ' is-danger' : '' ?>"
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
            <?php if (isset($errors['email'])): ?>
            <p class="help is-danger"><?= htmlspecialchars($errors['email'][0]) ?></p>
            <?php endif; ?>
        </div>

        <div class="field">
            <label class="label" for="password">Password</label>
            <div class="control has-icons-left has-icons-right">
                <input
                    class="input pw-input<?= isset($errors['password']) ? ' is-danger' : '' ?>"
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >
                <span class="icon is-left"><i class="fas fa-lock"></i></span>
                <span class="icon is-right is-clickable toggle-pw" data-target="password" title="Show/hide password" style="pointer-events:all;">
                    <i class="fas fa-eye"></i>
                </span>
            </div>
            <p class="help is-warning caps-lock-warn" style="display:none;">
                <span class="icon"><i class="fas fa-triangle-exclamation"></i></span>
                Caps Lock is on
            </p>
            <?php if (isset($errors['password'])): ?>
            <p class="help is-danger"><?= htmlspecialchars($errors['password'][0]) ?></p>
            <?php endif; ?>
        </div>

        <div class="field">
            <div class="control">
                <label class="checkbox">
                    <input type="checkbox" name="remember_me" value="1"<?= !empty($old['remember_me']) ? ' checked' : '' ?>>
                    Remember me for 30 days
                </label>
            </div>
        </div>

        <div class="field mt-5">
            <div class="control">
                <button class="button is-link is-fullwidth" type="submit">
                    <span class="icon"><i class="fas fa-right-to-bracket"></i></span>
                    <span>Log In</span>
                </button>
            </div>
        </div>
    </form>
</div>

<div class="has-text-centered mt-4">
    <p class="mb-2">
        <a href="/auth/forgot-password" class="has-text-grey">Forgot your password?</a>
    </p>
    <p class="has-text-grey-light is-size-7">
        Don't have an account?
        <a href="/auth/register">Create one</a>
    </p>
</div>

<script>
(function () {
    // Show / hide password toggles
    document.querySelectorAll('.toggle-pw').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const input = document.getElementById(this.dataset.target);
            const icon  = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    });

    // Caps Lock warning
    document.querySelectorAll('.pw-input').forEach(function (input) {
        const warn = input.closest('.field').querySelector('.caps-lock-warn');
        if (!warn) return;
        ['keydown', 'keyup'].forEach(function (evt) {
            input.addEventListener(evt, function (e) {
                warn.style.display = e.getModifierState('CapsLock') ? '' : 'none';
            });
        });
        input.addEventListener('blur', function () { warn.style.display = 'none'; });
    });
}());
</script>
