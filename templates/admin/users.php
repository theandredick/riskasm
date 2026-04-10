<?php
use App\Core\Session;
use App\Helpers\Csrf;
use App\Helpers\DateHelper;
$users = $users ?? [];
$roles = $roles ?? [];
Session::start();
?>
<div class="level mb-5">
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
            <a class="button is-link" href="/admin/users/new">
                <span class="icon"><i class="fas fa-user-plus"></i></span>
                <span>Create User</span>
            </a>
        </div>
    </div>
</div>

<?php if (empty($users)): ?>
<div class="box has-text-centered has-text-grey py-6">
    <p class="is-size-4 mb-2"><i class="fas fa-users"></i></p>
    <p>No users found.</p>
</div>
<?php else: ?>
<div class="box p-0">
<div class="table-container">
<table class="table is-fullwidth is-striped is-hoverable mb-0">
    <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Last Login</th>
            <th class="has-text-right">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $u): ?>
        <?php $isSelf = (int) $u['id'] === Session::userId(); ?>
        <tr>
            <td>
                <strong><?= htmlspecialchars($u['display_name']) ?></strong>
                <?php if ($isSelf): ?>
                <span class="tag is-info is-light ml-1">You</span>
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($u['email']) ?></td>
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
                <span class="tag is-dark"><?= ucfirst($u['role']) ?></span>
                <?php endif; ?>
            </td>
            <td>
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
            <td class="has-text-grey is-size-7">
                <?= DateHelper::formatDateTime($u['last_login_at']) ?>
            </td>
            <td class="has-text-right">
                <?php if (!$isSelf): ?>
                <form method="POST" action="/admin/users/<?= (int) $u['id'] ?>/toggle" class="is-inline-block">
                    <?= Csrf::field() ?>
                    <button
                        class="button is-small <?= $u['is_active'] ? 'is-warning is-light' : 'is-success is-light' ?>"
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
<?php endif; ?>
