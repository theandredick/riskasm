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

    // ── Disable-user confirmation modal ───────────────────────────────────────
    const disableModal     = document.getElementById('disableUserModal');
    const disableUserName  = document.getElementById('disableUserName');
    const confirmDisableBtn = document.getElementById('confirmDisableBtn');
    const closeDisableBtns = [
        document.getElementById('closeDisableModal'),
        document.getElementById('cancelDisableModal'),
        document.getElementById('disableModalBackground'),
    ];
    let pendingDisableForm = null;

    if (disableModal) {
        document.querySelectorAll('.js-disable-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                pendingDisableForm = btn.closest('.js-toggle-form');
                if (disableUserName) disableUserName.textContent = btn.dataset.name ?? 'this user';
                disableModal.classList.add('is-active');
            });
        });

        closeDisableBtns.forEach((el) => {
            if (el) el.addEventListener('click', () => {
                disableModal.classList.remove('is-active');
                pendingDisableForm = null;
            });
        });

        if (confirmDisableBtn) {
            confirmDisableBtn.addEventListener('click', () => {
                if (pendingDisableForm) pendingDisableForm.submit();
            });
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && disableModal.classList.contains('is-active')) {
                disableModal.classList.remove('is-active');
                pendingDisableForm = null;
            }
        });
    }

    // ── Create-user modal: AJAX email-exists check ─────────────────────────────
    const emailInput    = document.getElementById('m_email');
    const emailSpinner  = document.getElementById('m_email_spinner');
    const emailTakenMsg = document.querySelector('.js-email-taken');
    const createForm    = emailInput ? emailInput.closest('form') : null;

    if (emailInput && emailTakenMsg) {
        let lastChecked = '';

        const checkEmail = async () => {
            const val = emailInput.value.trim();
            if (val === lastChecked) return;
            lastChecked = val;

            // Skip if blank or obviously invalid (let HTML5 required/email handle it)
            if (!val || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
                emailTakenMsg.style.display = 'none';
                return;
            }

            if (emailSpinner) emailSpinner.classList.remove('is-hidden');
            try {
                const res  = await fetch('/admin/users/check-email?email=' + encodeURIComponent(val));
                const data = await res.json();
                if (data.exists) {
                    emailTakenMsg.style.display = '';
                    emailInput.classList.add('is-danger');
                } else {
                    emailTakenMsg.style.display = 'none';
                    // Only remove is-danger if it was set by this check (not a server-side error)
                    if (!emailInput.closest('.field').querySelector('.help.is-danger:not(.js-email-taken)')) {
                        emailInput.classList.remove('is-danger');
                    }
                }
            } catch (_) { /* network error — silent, server will catch it */ }
            finally {
                if (emailSpinner) emailSpinner.classList.add('is-hidden');
            }
        };

        emailInput.addEventListener('blur', checkEmail);

        // Block submit if the email is already taken
        if (createForm) {
            createForm.addEventListener('submit', (e) => {
                if (emailTakenMsg.style.display !== 'none') {
                    e.preventDefault();
                    emailInput.focus();
                }
            });
        }
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

    // ── Client-side validation for all novalidate forms ────────────────────────
    initFormValidation();

});

/**
 * Generic client-side validation for every form that has the novalidate
 * attribute.  Checks required, minlength, and email fields on submit and
 * clears errors as the user corrects each field.
 */
function initFormValidation() {
    document.querySelectorAll('form[novalidate]').forEach((form) => {

        form.addEventListener('submit', (e) => {
            let ok = true;

            form.querySelectorAll(
                'input[required], select[required], textarea[required], ' +
                'input[minlength]:not([required])'
            ).forEach((field) => {
                const msg = fieldError(field);
                if (msg) {
                    markError(field, msg);
                    ok = false;
                } else {
                    clearError(field);
                }
            });

            if (!ok) {
                e.preventDefault();
                // Scroll the first bad field into view
                const first = form.querySelector('input.is-danger, select.is-danger, textarea.is-danger');
                if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });

        // Clear an error as soon as the user corrects the field
        form.addEventListener('input',  (e) => clearError(e.target));
        form.addEventListener('change', (e) => clearError(e.target));
    });
}

/** Returns an error string for the field, or null if it is valid. */
function fieldError(field) {
    const val = field.value.trim();
    const tag = field.tagName.toLowerCase();

    if (field.hasAttribute('required') && val === '') {
        const label = field.closest('.field')?.querySelector('label')?.textContent?.trim();
        return (label ? label.replace(/:$/, '') : 'This field') + ' is required.';
    }

    if (val !== '' && field.hasAttribute('minlength')) {
        const min = parseInt(field.getAttribute('minlength'), 10);
        if (val.length < min) {
            const label = field.closest('.field')?.querySelector('label')?.textContent?.trim();
            return (label ? label.replace(/:$/, '') : 'This field') +
                   ` must be at least ${min} characters.`;
        }
    }

    if (val !== '' && field.type === 'email') {
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
            return 'Please enter a valid email address.';
        }
    }

    return null;
}

/** Highlights a field and inserts/shows a Bulma help error beneath it. */
function markError(field, message) {
    if (!field.matches('input, select, textarea')) return;
    field.classList.add('is-danger');

    // Also mark the wrapping .select div if present
    const selectWrap = field.closest('.select');
    if (selectWrap) selectWrap.classList.add('is-danger');

    const fieldEl = field.closest('.field');
    if (!fieldEl) return;

    let el = fieldEl.querySelector('.js-val-error');
    if (!el) {
        el = document.createElement('p');
        el.className = 'help is-danger js-val-error';
        fieldEl.appendChild(el);
    }
    el.textContent = message;
    el.style.display = '';
}

/** Removes the error highlight and hides the help message for a field. */
function clearError(field) {
    if (!field.matches || !field.matches('input, select, textarea')) return;
    field.classList.remove('is-danger');

    const selectWrap = field.closest('.select');
    if (selectWrap) selectWrap.classList.remove('is-danger');

    const fieldEl = field.closest('.field');
    if (!fieldEl) return;

    const el = fieldEl.querySelector('.js-val-error');
    if (el) el.style.display = 'none';
}

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
