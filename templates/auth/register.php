<?php
use App\Helpers\Csrf;
$errors = $errors ?? [];
$old    = $old ?? [];
?>
<div class="box">
    <h1 class="title is-4 mb-4">Create Account</h1>

    <form method="POST" action="/auth/register" novalidate>
        <?= Csrf::field() ?>

        <div class="field">
            <label class="label" for="display_name">Your name</label>
            <div class="control has-icons-left">
                <input
                    class="input<?= isset($errors['display_name']) ? ' is-danger' : '' ?>"
                    type="text"
                    id="display_name"
                    name="display_name"
                    value="<?= htmlspecialchars($old['display_name'] ?? '') ?>"
                    autocomplete="name"
                    autofocus
                    required
                >
                <span class="icon is-left"><i class="fas fa-user"></i></span>
            </div>
            <?php if (isset($errors['display_name'])): ?>
            <p class="help is-danger"><?= htmlspecialchars($errors['display_name'][0]) ?></p>
            <?php endif; ?>
        </div>

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
                    autocomplete="new-password"
                    required
                    minlength="8"
                >
                <span class="icon is-left"><i class="fas fa-lock"></i></span>
                <span class="icon is-right is-clickable toggle-pw" data-target="password" title="Show/hide password" style="pointer-events:all;">
                    <i class="fas fa-eye"></i>
                </span>
            </div>
            <p class="help">Minimum 8 characters.</p>
            <p class="help is-warning caps-lock-warn" style="display:none;">
                <span class="icon"><i class="fas fa-triangle-exclamation"></i></span>
                Caps Lock is on
            </p>
            <?php if (isset($errors['password'])): ?>
            <p class="help is-danger"><?= htmlspecialchars($errors['password'][0]) ?></p>
            <?php endif; ?>
        </div>

        <div class="field">
            <label class="label" for="password_confirm">Confirm password</label>
            <div class="control has-icons-left has-icons-right">
                <input
                    class="input pw-input<?= isset($errors['password_confirm']) ? ' is-danger' : '' ?>"
                    type="password"
                    id="password_confirm"
                    name="password_confirm"
                    autocomplete="new-password"
                    required
                >
                <span class="icon is-left"><i class="fas fa-lock"></i></span>
                <span class="icon is-right is-clickable toggle-pw" data-target="password_confirm" title="Show/hide password" style="pointer-events:all;">
                    <i class="fas fa-eye"></i>
                </span>
            </div>
            <p class="help is-warning caps-lock-warn" style="display:none;">
                <span class="icon"><i class="fas fa-triangle-exclamation"></i></span>
                Caps Lock is on
            </p>
            <?php if (isset($errors['password_confirm'])): ?>
            <p class="help is-danger"><?= htmlspecialchars($errors['password_confirm'][0]) ?></p>
            <?php endif; ?>
        </div>

        <div class="field mt-5">
            <div class="control">
                <button class="button is-link is-fullwidth" type="submit">
                    <span class="icon"><i class="fas fa-user-plus"></i></span>
                    <span>Create Account</span>
                </button>
            </div>
        </div>
    </form>
</div>

<div class="has-text-centered mt-4">
    <p class="has-text-grey-light is-size-7">
        Already have an account?
        <a href="/auth/login">Log in</a>
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
