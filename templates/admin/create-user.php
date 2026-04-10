<?php
use App\Helpers\Csrf;
$errors = $errors ?? [];
$old    = $old ?? [];
$roles  = $roles ?? [];
?>
<div class="level mb-5">
    <div class="level-left">
        <div class="level-item">
            <div>
                <h1 class="title is-4">Create User</h1>
                <p class="subtitle is-6 has-text-grey">Add a new user to the system</p>
            </div>
        </div>
    </div>
    <div class="level-right">
        <div class="level-item">
            <a class="button is-light" href="/admin/users">
                <span class="icon"><i class="fas fa-arrow-left"></i></span>
                <span>Back to Users</span>
            </a>
        </div>
    </div>
</div>

<div class="columns">
    <div class="column is-6-desktop is-8-tablet">
        <div class="box">
            <form method="POST" action="/admin/users/new" novalidate>
                <?= Csrf::field() ?>

                <div class="field">
                    <label class="label" for="display_name">Display name</label>
                    <div class="control has-icons-left">
                        <input
                            class="input<?= isset($errors['display_name']) ? ' is-danger' : '' ?>"
                            type="text"
                            id="display_name"
                            name="display_name"
                            value="<?= htmlspecialchars($old['display_name'] ?? '') ?>"
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
                            autocomplete="off"
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
                    <div class="control has-icons-left">
                        <input
                            class="input<?= isset($errors['password']) ? ' is-danger' : '' ?>"
                            type="password"
                            id="password"
                            name="password"
                            autocomplete="new-password"
                            required
                            minlength="8"
                        >
                        <span class="icon is-left"><i class="fas fa-lock"></i></span>
                    </div>
                    <p class="help">Minimum 8 characters.</p>
                    <?php if (isset($errors['password'])): ?>
                    <p class="help is-danger"><?= htmlspecialchars($errors['password'][0]) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field">
                    <label class="label" for="role">Role</label>
                    <div class="control">
                        <div class="select is-fullwidth<?= isset($errors['role']) ? ' is-danger' : '' ?>">
                            <select id="role" name="role" required>
                                <?php foreach ($roles as $r): ?>
                                <option value="<?= $r ?>"<?= ($old['role'] ?? 'assessor') === $r ? ' selected' : '' ?>>
                                    <?= ucfirst($r) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <?php if (isset($errors['role'])): ?>
                    <p class="help is-danger"><?= htmlspecialchars($errors['role'][0]) ?></p>
                    <?php endif; ?>
                </div>

                <div class="field mt-5">
                    <div class="control">
                        <button class="button is-link" type="submit">
                            <span class="icon"><i class="fas fa-user-plus"></i></span>
                            <span>Create User</span>
                        </button>
                        <a class="button is-light ml-2" href="/admin/users">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
