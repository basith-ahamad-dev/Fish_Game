/* ==========================================================================
   Forms: show/hide password, strength meter, confirmation match, avatar preview.
   The server re-validates everything; this only gives faster feedback.
   ========================================================================== */
(() => {
    'use strict';
    const { $, $$ } = window.DC;

    const EYE = '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>';
    const EYE_OFF = '<path d="M3 3l18 18M10.6 5.1A10.4 10.4 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3.2 4M6.6 6.6A17 17 0 0 0 2 12s3.5 7 10 7a9.7 9.7 0 0 0 5.4-1.6"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>';

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-toggle-password]');
        if (!btn) return;
        const input = btn.parentElement.querySelector('input');
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        btn.querySelector('svg').innerHTML = show ? EYE_OFF : EYE;
    });

    /* Strength meter: 4 bars for length, mixed case, digits, symbols/length 12+ */
    $$('[data-strength]').forEach((input) => {
        const meter = input.closest('form').querySelector('[data-strength-meter]');
        if (!meter) return;
        const label = meter.querySelector('.strength__label');
        const labels = ['Too short', 'Weak — add upper & lowercase letters and a number', 'Okay — almost there', 'Strong', 'Excellent'];

        input.addEventListener('input', () => {
            const v = input.value;
            meter.hidden = v.length === 0;
            let score = 0;
            if (v.length >= 8) score++;
            if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
            if (/\d/.test(v)) score++;
            if (/[^A-Za-z0-9]/.test(v) || v.length >= 12) score++;
            if (v.length < 8) score = Math.min(score, 1);
            meter.dataset.level = String(score);
            label.textContent = labels[score];
        });
    });

    /* Confirmation must match its partner field */
    $$('[data-match]').forEach((input) => {
        const other = input.form.querySelector(`[name="${input.dataset.match}"]`);
        const check = () => input.setCustomValidity(input.value && input.value !== other.value ? 'Passwords do not match.' : '');
        input.addEventListener('input', check);
        other.addEventListener('input', check);
    });

    /* Client-side check before submit; show the first problem inline */
    $$('form[data-auth-form]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            const invalid = $$('input', form).find((i) => !i.checkValidity());
            if (!invalid) return;
            e.preventDefault();
            const field = invalid.closest('.field');
            if (field) {
                field.classList.add('has-error');
                let msg = field.querySelector('.field__error');
                if (!msg) {
                    msg = document.createElement('p');
                    msg.className = 'field__error';
                    msg.setAttribute('role', 'alert');
                    field.querySelector('.field__hint')?.remove();
                    field.appendChild(msg);
                }
                msg.textContent = invalid.validationMessage;
                invalid.addEventListener('input', () => field.classList.remove('has-error'), { once: true });
            }
            invalid.focus();
        });
    });

    /* Avatar preview before upload */
    const avatarInput = $('[data-avatar-input]');
    if (avatarInput) {
        avatarInput.addEventListener('change', () => {
            const file = avatarInput.files[0];
            const preview = $('[data-avatar-preview]');
            if (!file || !file.type.startsWith('image/')) return;
            const img = document.createElement('img');
            img.className = 'avatar-preview';
            img.alt = 'New avatar preview';
            img.src = URL.createObjectURL(file);
            preview.replaceChildren(img);
        });
    }
})();
