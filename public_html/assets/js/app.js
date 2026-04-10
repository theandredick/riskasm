// ── Navbar burger toggle ───────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    // Bulma navbar burger
    const burgers = document.querySelectorAll('.navbar-burger');
    burgers.forEach((burger) => {
        burger.addEventListener('click', () => {
            const target = document.getElementById(burger.dataset.target);
            burger.classList.toggle('is-active');
            if (target) target.classList.toggle('is-active');
        });
    });

    // Auto-dismiss flash notifications after 6 seconds
    document.querySelectorAll('.flash-notification').forEach((el) => {
        setTimeout(() => {
            el.style.transition = 'opacity 0.5s ease';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 500);
        }, 6000);
    });

    // Bulma notification delete buttons
    document.querySelectorAll('.notification .delete').forEach((btn) => {
        btn.addEventListener('click', () => {
            const notification = btn.closest('.notification');
            if (notification) notification.remove();
        });
    });
});
