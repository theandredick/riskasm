document.addEventListener('DOMContentLoaded', () => {

    // ── Navbar burger toggle ───────────────────────────────────────────────────
    document.querySelectorAll('.navbar-burger').forEach((burger) => {
        burger.addEventListener('click', () => {
            const target = document.getElementById(burger.dataset.target);
            burger.classList.toggle('is-active');
            burger.setAttribute('aria-expanded',
                burger.classList.contains('is-active') ? 'true' : 'false');
            if (target) target.classList.toggle('is-active');
        });
    });

    // ── Navbar account dropdown: click / tap to toggle on all widths ───────────
    document.querySelectorAll('.navbar .has-dropdown').forEach((item) => {
        const link = item.querySelector(':scope > .navbar-link');
        if (!link) return;

        link.addEventListener('click', (e) => {
            e.preventDefault();
            // Close any other open dropdowns
            document.querySelectorAll('.navbar .has-dropdown.is-active').forEach((other) => {
                if (other !== item) other.classList.remove('is-active');
            });
            item.classList.toggle('is-active');
        });
    });

    // Close the dropdown when clicking anywhere outside it
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.navbar .has-dropdown')) {
            document.querySelectorAll('.navbar .has-dropdown.is-active').forEach((item) => {
                item.classList.remove('is-active');
            });
        }
    });

    // Close dropdown + mobile menu when a dropdown link is followed
    document.querySelectorAll('.navbar-dropdown .navbar-item').forEach((link) => {
        link.addEventListener('click', () => {
            const parent = link.closest('.has-dropdown');
            if (parent) parent.classList.remove('is-active');
            // Also collapse mobile menu
            const menu   = document.getElementById('mainNavbar');
            const burger = document.querySelector('.navbar-burger[data-target="mainNavbar"]');
            if (menu)   menu.classList.remove('is-active');
            if (burger) {
                burger.classList.remove('is-active');
                burger.setAttribute('aria-expanded', 'false');
            }
        });
    });

    // Close mobile menu when a plain (non-dropdown) nav link is clicked
    document.querySelectorAll('#mainNavbar .navbar-start .navbar-item').forEach((item) => {
        item.addEventListener('click', () => {
            const menu   = document.getElementById('mainNavbar');
            const burger = document.querySelector('.navbar-burger[data-target="mainNavbar"]');
            if (menu)   menu.classList.remove('is-active');
            if (burger) {
                burger.classList.remove('is-active');
                burger.setAttribute('aria-expanded', 'false');
            }
        });
    });

    // ── Auto-dismiss flash notifications after 6 s ────────────────────────────
    document.querySelectorAll('.flash-notification').forEach((el) => {
        setTimeout(() => {
            el.style.transition = 'opacity 0.5s ease';
            el.style.opacity    = '0';
            setTimeout(() => el.remove(), 500);
        }, 6000);
    });

    // ── Bulma notification delete buttons ─────────────────────────────────────
    document.querySelectorAll('.notification .delete').forEach((btn) => {
        btn.addEventListener('click', () => {
            const notification = btn.closest('.notification');
            if (notification) notification.remove();
        });
    });

    // ── Create-user modal ──────────────────────────────────────────────────────
    const modal     = document.getElementById('createUserModal');
    const openBtn   = document.getElementById('openCreateUser');
    const closeBtns = [
        document.getElementById('closeModal'),
        document.getElementById('cancelModal'),
        document.getElementById('modalBackground'),
    ];

    if (modal && openBtn) {
        openBtn.addEventListener('click', () => modal.classList.add('is-active'));
        closeBtns.forEach((el) => {
            if (el) el.addEventListener('click', () => modal.classList.remove('is-active'));
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('is-active')) {
                modal.classList.remove('is-active');
            }
        });
    }

    // ── Users table live search ────────────────────────────────────────────────
    const searchInput = document.getElementById('userSearch');
    const usersTable  = document.getElementById('usersTable');
    const noResults   = document.getElementById('noResults');

    if (searchInput && usersTable) {
        searchInput.addEventListener('input', () => {
            const q     = searchInput.value.toLowerCase().trim();
            const rows  = usersTable.querySelectorAll('tbody tr');
            let visible = 0;

            rows.forEach((row) => {
                const haystack = row.dataset.search ?? '';
                const show     = !q || haystack.includes(q);
                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            if (noResults) noResults.style.display = (visible === 0 && q) ? '' : 'none';
        });
    }

    // ── Password field enhancements (show/hide toggle + caps-lock warning) ─────
    initPasswordFields();

});

/**
 * Attaches a show/hide eye icon and a Caps Lock warning to every
 * input[type="password"] on the page. Safe to call multiple times — fields
 * that have already been initialised are skipped via a data attribute.
 */
function initPasswordFields() {
    document.querySelectorAll('input[type="password"]').forEach((input) => {
        const control = input.closest('.control');
        if (!control || control.dataset.pwInit) return;
        control.dataset.pwInit = '1';

        // ── Show / hide toggle ───────────────────────────────────────────────
        control.classList.add('has-icons-right');

        const toggleBtn = document.createElement('span');
        toggleBtn.className = 'icon is-right pw-toggle';
        toggleBtn.setAttribute('role', 'button');
        toggleBtn.setAttribute('tabindex', '0');
        toggleBtn.setAttribute('aria-label', 'Toggle password visibility');
        toggleBtn.title = 'Show / hide password';
        toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
        control.appendChild(toggleBtn);

        const doToggle = () => {
            const revealing  = input.type === 'password';
            input.type       = revealing ? 'text' : 'password';
            toggleBtn.querySelector('i').className =
                revealing ? 'fas fa-eye-slash' : 'fas fa-eye';
            input.focus();
        };

        toggleBtn.addEventListener('click', doToggle);
        toggleBtn.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); doToggle(); }
        });

        // ── Caps Lock warning ────────────────────────────────────────────────
        const field = input.closest('.field');
        if (!field) return;

        const capsEl = document.createElement('p');
        capsEl.className = 'help pw-caps-warning';
        capsEl.style.display = 'none';
        capsEl.innerHTML =
            '<span class="icon is-small"><i class="fas fa-triangle-exclamation"></i></span>' +
            ' Caps Lock is on';
        // Insert right after the .control so it appears below the input
        control.insertAdjacentElement('afterend', capsEl);

        const checkCaps = (e) => {
            if (typeof e.getModifierState === 'function') {
                capsEl.style.display = e.getModifierState('CapsLock') ? '' : 'none';
            }
        };
        input.addEventListener('keydown', checkCaps);
        input.addEventListener('keyup',   checkCaps);
    });
}
