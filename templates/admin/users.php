<?php
use App\Core\Session;
use App\Helpers\Csrf;
use App\Helpers\DateHelper;

$users       = $users       ?? [];
$roles       = $roles       ?? [];
$modal_open  = $modal_open  ?? false;
$modal_errors = $modal_errors ?? [];
$modal_old   = $modal_old   ?? [];

Session::start();

$merr = function(string $k) use ($modal_errors): string {
    if (empty($modal_errors[$k])) {
        return '';
    }
    $msgs = implode(' ', array_map('htmlspecialchars', (array) $modal_errors[$k]));
    return '<p class="help is-danger">' . $msgs . '</p>';
};
$mcls = fn(string $k) => !empty($modal_errors[$k]) ? ' is-danger' : '';
$mval = fn(string $k, string $fb = '') => htmlspecialchars($modal_old[$k] ?? $fb);
?>

<!-- ── Page Header ─────────────────────────────────────────────────────────── -->
<div class="level mb-4">
    <div class="level-left">
        <div class="level-item">
            <div>
                <h1 class="title is-4">User Management</h1>
                <p class="subtitle is-6 has-text-grey">
                    <?= count($users) ?> registered user<?= count($users) !== 1 ? 's' : '' ?>
                </p>
            </div>
        </div>
    </div>
    <div class="level-right">
        <div class="level-item">
            <button class="button is-link" id="openCreateUser">
                <span class="icon"><i class="fas fa-user-plus"></i></span>
                <span>Create User</span>
            </button>
        </div>
    </div>
</div>

<!-- ── Search / Filter ────────────────────────────────────────────────────── -->
<?php if (count($users) > 5): ?>
<div class="field mb-4">
    <div class="control has-icons-left">
        <input
            id="userSearch"
            class="input"
            type="search"
            placeholder="Filter by name or email…"
            autocomplete="off"
        >
        <span class="icon is-left"><i class="fas fa-magnifying-glass"></i></span>
    </div>
</div>
<?php endif; ?>

<!-- ── Users Table ─────────────────────────────────────────────────────────── -->
<?php if (empty($users)): ?>
<div class="box has-text-centered has-text-grey py-6">
    <p class="is-size-4 mb-2"><i class="fas fa-users"></i></p>
    <p>No users found.</p>
</div>
<?php else: ?>
<div class="box p-0">
    <div class="table-container" style="overflow-x:auto;">
        <table class="table is-fullwidth is-striped is-hoverable mb-0" id="usersTable">
            <thead>
                <tr>
                    <th>Name</th>
                    <th class="is-hidden-mobile">Email</th>
                    <th>Role</th>
                    <th class="is-hidden-mobile">Status</th>
                    <th class="is-hidden-touch">Last Login</th>
                    <th class="has-text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <?php $isSelf = (int) $u['id'] === Session::userId(); ?>
                <tr data-search="<?= strtolower(htmlspecialchars($u['display_name'] . ' ' . $u['email'])) ?>">
                    <td>
                        <div>
                            <strong><?= htmlspecialchars($u['display_name']) ?></strong>
                            <?php if ($isSelf): ?>
                            <span class="tag is-info is-light ml-1">You</span>
                            <?php endif; ?>
                        </div>
                        <div class="is-hidden-tablet is-size-7 has-text-grey">
                            <?= htmlspecialchars($u['email']) ?>
                        </div>
                        <div class="is-hidden-tablet mt-1">
                            <?php if ($u['is_active']): ?>
                            <span class="tag is-success is-light is-small">Active</span>
                            <?php else: ?>
                            <span class="tag is-danger is-light is-small">Disabled</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="is-hidden-mobile"><?= htmlspecialchars($u['email']) ?></td>
                    <td>
                        <?php if (!$isSelf): ?>
                        <form method="POST" action="/admin/users/<?= (int) $u['id'] ?>/role" class="is-inline-block">
                            <?= Csrf::field() ?>
                            <div class="select is-small">
                                <select name="role" onchange="this.form.submit()">
                                    <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r ?>"<?= $u['role'] === $r ? ' selected' : '' ?>>
                                        <?= ucfirst($r) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </form>
                        <?php else: ?>
                        <span class="tag is-dark is-small"><?= ucfirst($u['role']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="is-hidden-mobile">
                        <?php if ($u['is_active']): ?>
                        <span class="tag is-success is-light">
                            <span class="icon"><i class="fas fa-circle-check"></i></span>
                            <span>Active</span>
                        </span>
                        <?php else: ?>
                        <span class="tag is-danger is-light">
                            <span class="icon"><i class="fas fa-ban"></i></span>
                            <span>Disabled</span>
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="has-text-grey is-size-7 is-hidden-touch">
                        <?= DateHelper::formatDateTime($u['last_login_at']) ?>
                    </td>
                    <td class="has-text-right">
                        <?php if (!$isSelf): ?>
                        <form method="POST" action="/admin/users/<?= (int) $u['id'] ?>/toggle" class="is-inline-block">
                            <?= Csrf::field() ?>
                            <button
                                class="button is-small <?= $u['is_active'] ? 'is-danger-muted' : 'is-success is-light' ?>"
                                type="submit"
                                onclick="return confirm('<?= $u['is_active'] ? 'Disable' : 'Enable' ?> this user?')"
                                title="<?= $u['is_active'] ? 'Disable account' : 'Enable account' ?>"
                            >
                                <span class="icon">
                                    <i class="fas <?= $u['is_active'] ? 'fa-ban' : 'fa-circle-check' ?>"></i>
                                </span>
                                <span><?= $u['is_active'] ? 'Disable' : 'Enable' ?></span>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<p id="noResults" class="has-text-centered has-text-grey py-4" style="display:none;">
    No users match your search.
</p>
<?php endif; ?>

<!-- ── Create User Modal ───────────────────────────────────────────────────── -->
<div class="modal<?= $modal_open ? ' is-active' : '' ?>" id="createUserModal">
    <div class="modal-background" id="modalBackground"></div>
    <div class="modal-card">
        <header class="modal-card-head">
            <p class="modal-card-title">
                <span class="icon-text">
                    <span class="icon has-text-teal"><i class="fas fa-user-plus"></i></span>
                    <span>Create New User</span>
                </span>
            </p>
            <button class="delete" aria-label="close" id="closeModal"></button>
        </header>
        <form method="POST" action="/admin/users/new" novalidate>
            <?= Csrf::field() ?>
            <section class="modal-card-body">
                <?php if (!empty($modal_errors)): ?>
                <div class="notification is-danger is-light mb-4">
                    <button class="delete"></button>
                    Please correct the errors below.
                </div>
                <?php endif; ?>

                <div class="field">
                    <label class="label" for="m_display_name">Display Name</label>
                    <div class="control has-icons-left">
                        <input
                            id="m_display_name"
                            name="display_name"
                            type="text"
                            class="input<?= $mcls('display_name') ?>"
                            value="<?= $mval('display_name') ?>"
                            maxlength="100"
                            autocomplete="off"
                            required
                        >
                        <span class="icon is-left"><i class="fas fa-user"></i></span>
                    </div>
                    <?= $merr('display_name') ?>
                </div>

                <div class="field">
                    <label class="label" for="m_email">Email Address</label>
                    <div class="control has-icons-left">
                        <input
                            id="m_email"
                            name="email"
                            type="email"
                            class="input<?= $mcls('email') ?>"
                            value="<?= $mval('email') ?>"
                            autocomplete="off"
                            required
                        >
                        <span class="icon is-left"><i class="fas fa-envelope"></i></span>
                    </div>
                    <?= $merr('email') ?>
                </div>

                <div class="columns">
                    <div class="column">
                        <div class="field">
                            <label class="label" for="m_password">Password</label>
                            <div class="control has-icons-left">
                                <input
                                    id="m_password"
                                    name="password"
                                    type="password"
                                    class="input<?= $mcls('password') ?>"
                                    autocomplete="new-password"
                                    minlength="8"
                                    placeholder="Min. 8 characters"
                                    required
                                >
                                <span class="icon is-left"><i class="fas fa-lock"></i></span>
                            </div>
                            <?= $merr('password') ?>
                        </div>
                    </div>
                    <div class="column">
                        <div class="field">
                            <label class="label" for="m_role">Role</label>
                            <div class="control">
                                <div class="select is-fullwidth<?= $mcls('role') ?>">
                                    <select id="m_role" name="role" required>
                                        <?php foreach ($roles as $r): ?>
                                        <option value="<?= $r ?>"<?= $mval('role') === $r ? ' selected' : '' ?>>
                                            <?= ucfirst($r) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <?= $merr('role') ?>
                        </div>
                    </div>
                </div>

                <p class="help has-text-grey mt-2">
                    <span class="icon"><i class="fas fa-circle-info"></i></span>
                    The user can change their password after logging in.
                </p>
            </section>
            <footer class="modal-card-foot" style="justify-content: flex-end;">
                <button type="button" class="button" id="cancelModal">Cancel</button>
                <button type="submit" class="button is-link ml-2">
                    <span class="icon"><i class="fas fa-user-plus"></i></span>
                    <span>Create User</span>
                </button>
            </footer>
        </form>
    </div>
</div>
