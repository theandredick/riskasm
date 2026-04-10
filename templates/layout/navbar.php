<?php
use App\Core\Session;
Session::start();
?>
<nav class="navbar is-dark is-fixed-top" role="navigation" aria-label="main navigation">
    <div class="container is-fluid">

        <div class="navbar-brand">
            <a class="navbar-item" href="/">
                <span class="icon-text">
                    <span class="icon has-text-link"><i class="fas fa-shield-halved"></i></span>
                    <span class="has-text-weight-bold">Smart Risk Assessment</span>
                </span>
            </a>

            <a role="button" class="navbar-burger" aria-label="menu" aria-expanded="false" data-target="mainNavbar">
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
            </a>
        </div>

        <div id="mainNavbar" class="navbar-menu">
            <div class="navbar-start">
                <?php if (Session::isLoggedIn()): ?>
                <a class="navbar-item" href="/">
                    <span class="icon-text">
                        <span class="icon"><i class="fas fa-gauge-high"></i></span>
                        <span>Dashboard</span>
                    </span>
                </a>
                <a class="navbar-item" href="/assessments">
                    <span class="icon-text">
                        <span class="icon"><i class="fas fa-clipboard-list"></i></span>
                        <span>Assessments</span>
                    </span>
                </a>
                <a class="navbar-item" href="/matrices">
                    <span class="icon-text">
                        <span class="icon"><i class="fas fa-table-cells"></i></span>
                        <span>Risk Matrices</span>
                    </span>
                </a>
                <a class="navbar-item" href="/library">
                    <span class="icon-text">
                        <span class="icon"><i class="fas fa-book"></i></span>
                        <span>Library</span>
                    </span>
                </a>
                <?php endif; ?>
            </div>

            <div class="navbar-end">
                <?php if (Session::isLoggedIn()): ?>
                <div class="navbar-item has-dropdown is-hoverable">
                    <a class="navbar-link">
                        <span class="icon-text">
                            <span class="icon"><i class="fas fa-circle-user"></i></span>
                            <span><?= htmlspecialchars(Session::get('user_name', 'Account')) ?></span>
                        </span>
                    </a>
                    <div class="navbar-dropdown is-right">
                        <div class="navbar-item is-size-7 has-text-grey">
                            <?= htmlspecialchars(ucfirst(Session::userRole() ?? '')) ?>
                        </div>
                        <hr class="navbar-divider">
                        <?php if (Session::userRole() === 'admin'): ?>
                        <a class="navbar-item" href="/admin/users">
                            <span class="icon-text">
                                <span class="icon has-text-warning"><i class="fas fa-users-gear"></i></span>
                                <span>User Management</span>
                            </span>
                        </a>
                        <a class="navbar-item" href="/admin/audit">
                            <span class="icon-text">
                                <span class="icon"><i class="fas fa-scroll"></i></span>
                                <span>Audit Log</span>
                            </span>
                        </a>
                        <hr class="navbar-divider">
                        <?php endif; ?>
                        <a class="navbar-item" href="/auth/logout">
                            <span class="icon-text">
                                <span class="icon"><i class="fas fa-right-from-bracket"></i></span>
                                <span>Log out</span>
                            </span>
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <div class="navbar-item">
                    <div class="buttons">
                        <a class="button is-primary is-small" href="/auth/register">
                            <span class="icon"><i class="fas fa-user-plus"></i></span>
                            <span>Register</span>
                        </a>
                        <a class="button is-light is-small" href="/auth/login">
                            <span>Log in</span>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</nav>
