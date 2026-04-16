<?php
use App\Core\Session;
use App\Helpers\Csrf;
Session::start();

$user   = $user   ?? [];
$errors = $errors ?? [];
$old    = $old    ?? [];

$val = fn(string $k, string $fb = '') => htmlspecialchars($old[$k] ?? $user[$k] ?? $fb);
$err = function(string $k) use ($errors): string {
    if (empty($errors[$k])) {
        return '';
    }
    $msgs = implode(' ', array_map('htmlspecialchars', (array) $errors[$k]));
    return '<p class="help is-danger">' . $msgs . '</p>';
};
$cls = fn(string $k) => !empty($errors[$k]) ? ' is-danger' : '';
?>

<div class="columns is-centered">
<div class="column is-10-tablet is-8-desktop is-7-widescreen">

<div class="level mb-5">
    <div class="level-left">
        <div class="level-item">
            <div>
                <h1 class="title is-4">
                    <span class="icon-text">
                        <span class="icon has-text-teal"><i class="fas fa-id-card"></i></span>
                        <span>My Profile</span>
                    </span>
                </h1>
                <p class="subtitle is-6 has-text-grey">Update your display name, email, or password</p>
            </div>
        </div>
    </div>
    <div class="level-right">
        <div class="level-item">
            <span class="tag is-dark is-medium">
                <span class="icon"><i class="fas fa-user-tag"></i></span>
                <span><?= htmlspecialchars(ucfirst($user['role'] ?? '')) ?></span>
            </span>
        </div>
    </div>
</div>

<form method="POST" action="/profile" novalidate>
    <?= Csrf::field() ?>

    <div class="box">
        <h2 class="title is-6 has-text-ocean mb-4">
            <span class="icon"><i class="fas fa-user-pen"></i></span>
            <span>Account Details</span>
        </h2>

        <div class="field">
            <label class="label" for="display_name">Display Name</label>
            <div class="control has-icons-left">
                <input
                    id="display_name"
                    name="display_name"
                    type="text"
                    class="input<?= $cls('display_name') ?>"
                    value="<?= $val('display_name') ?>"
                    maxlength="100"
                    autocomplete="name"
                    required
                >
                <span class="icon is-left"><i class="fas fa-user"></i></span>
            </div>
            <?= $err('display_name') ?>
        </div>

        <div class="field">
            <label class="label" for="email">Email Address</label>
            <div class="control has-icons-left">
                <input
                    id="email"
                    name="email"
                    type="email"
                    class="input<?= $cls('email') ?>"
                    value="<?= $val('email') ?>"
                    autocomplete="email"
                    required
                >
                <span class="icon is-left"><i class="fas fa-envelope"></i></span>
            </div>
            <?= $err('email') ?>
        </div>
    </div>

    <div class="box mt-5">
        <h2 class="title is-6 has-text-ocean mb-1">
            <span class="icon"><i class="fas fa-lock"></i></span>
            <span>Change Password</span>
        </h2>
        <p class="help has-text-grey mb-4">Leave all three fields blank to keep your current password.</p>

        <div class="field">
            <label class="label" for="current_password">Current Password</label>
            <div class="control has-icons-left">
                <input
                    id="current_password"
                    name="current_password"
                    type="password"
                    class="input<?= $cls('current_password') ?>"
                    autocomplete="current-password"
                    placeholder="Required when changing password"
                >
                <span class="icon is-left"><i class="fas fa-key"></i></span>
            </div>
            <?= $err('current_password') ?>
        </div>

        <div class="columns">
            <div class="column">
                <div class="field">
                    <label class="label" for="new_password">New Password</label>
                    <div class="control has-icons-left">
                        <input
                            id="new_password"
                            name="new_password"
                            type="password"
                            class="input<?= $cls('new_password') ?>"
                            autocomplete="new-password"
                            minlength="8"
                            placeholder="Min. 8 characters"
                        >
                        <span class="icon is-left"><i class="fas fa-lock"></i></span>
                    </div>
                    <?= $err('new_password') ?>
                </div>
            </div>
            <div class="column">
                <div class="field">
                    <label class="label" for="new_password_confirm">Confirm New Password</label>
                    <div class="control has-icons-left">
                        <input
                            id="new_password_confirm"
                            name="new_password_confirm"
                            type="password"
                            class="input<?= $cls('new_password_confirm') ?>"
                            autocomplete="new-password"
                            placeholder="Repeat new password"
                        >
                        <span class="icon is-left"><i class="fas fa-lock"></i></span>
                    </div>
                    <?= $err('new_password_confirm') ?>
                </div>
            </div>
        </div>
    </div>

    <div class="field is-grouped mt-4">
        <div class="control">
            <button type="submit" class="button is-primary">
                <span class="icon"><i class="fas fa-floppy-disk"></i></span>
                <span>Save Changes</span>
            </button>
        </div>
        <div class="control">
            <a href="/" class="button is-light">Cancel</a>
        </div>
    </div>

</form>

</div>
</div>
