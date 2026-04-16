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

    // Close mobile menu when a nav link is clicked
    document.querySelectorAll('#mainNavbar .navbar-item:not(.has-dropdown)').forEach((item) => {
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

    // ── Mobile: tap to toggle navbar account dropdown ─────────────────────────
    // On touch devices .is-hoverable doesn't trigger; wire a click handler.
    document.querySelectorAll('.navbar .has-dropdown').forEach((item) => {
        const link = item.querySelector('.navbar-link');
        if (!link) return;
        link.addEventListener('click', (e) => {
            // Only intercept on mobile widths (burger visible)
            if (window.innerWidth <= 1023) {
                e.preventDefault();
                item.classList.toggle('is-active');
            }
        });
    });

    // ── Create-user modal ──────────────────────────────────────────────────────
    const modal    = document.getElementById('createUserModal');
    const openBtn  = document.getElementById('openCreateUser');
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
        // Close on Escape
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

});
