/**
 * Form Validation Helpers
 * Online Hostel Management System - Pearls of Wisdom Hostel
 *
 * Shared utility functions used across all auth and form pages.
 */

'use strict';

// ================================================
// VALIDATION UTILITIES
// ================================================

/**
 * Validate email format
 * @param {string} email
 * @returns {boolean}
 */
function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim());
}

/**
 * Validate Uganda phone number
 * Accepts: 07XXXXXXXX, 03XXXXXXXX, +2567XXXXXXXX, +2563XXXXXXXX
 * @param {string} phone
 * @returns {boolean}
 */
function isValidUgandaPhone(phone) {
    const stripped = phone.trim().replace(/[\s\-()]/g, '');
    return /^(\+256|0)(7|3)\d{8}$/.test(stripped);
}

/**
 * Get password strength score and individual checks
 * @param {string} password
 * @returns {{ score: number, checks: { length: boolean, upper: boolean, lower: boolean, number: boolean, special: boolean } }}
 */
function getPasswordStrength(password) {
    const checks = {
        length:  password.length >= 8,
        upper:   /[A-Z]/.test(password),
        lower:   /[a-z]/.test(password),
        number:  /[0-9]/.test(password),
        special: /[^A-Za-z0-9]/.test(password),
    };

    const passed = Object.values(checks).filter(Boolean).length;
    // score: 0 = empty, 1 = weak (1-2), 2 = fair (3), 3 = good (4), 4 = strong (5)
    let score = 0;
    if (password.length === 0) score = 0;
    else if (passed <= 2)      score = 1;
    else if (passed === 3)     score = 2;
    else if (passed === 4)     score = 3;
    else                       score = 4;

    return { score, checks };
}

// ================================================
// DOM HELPERS
// ================================================

/**
 * Display a validation error message for a field
 * @param {string} errorId  - ID of the <span class="auth-field-error"> element
 * @param {string} message
 * @param {string} [inputId] - Optional: input to mark with .error class
 */
function showFieldError(errorId, message, inputId) {
    const el = document.getElementById(errorId);
    if (el) {
        el.textContent = message;
        el.setAttribute('aria-live', 'polite');
    }
    if (inputId) {
        const input = document.getElementById(inputId);
        if (input) input.classList.add('error');
    }
}

/**
 * Clear a validation error message
 * @param {string} errorId
 * @param {string} [inputId]
 */
function clearError(errorId, inputId) {
    const el = document.getElementById(errorId);
    if (el) el.textContent = '';
    if (inputId) {
        const input = document.getElementById(inputId);
        if (input) input.classList.remove('error');
    }
}

/**
 * Clear all errors inside a form
 * @param {HTMLFormElement} form
 */
function clearAllErrors(form) {
    form.querySelectorAll('.auth-field-error').forEach(el => el.textContent = '');
    form.querySelectorAll('.auth-input.error').forEach(el => el.classList.remove('error'));
}

// ================================================
// REAL-TIME VALIDATION SETUP
// ================================================

/**
 * Attach real-time validation to a field so it clears its error on input.
 * @param {string} inputId
 * @param {string} errorId
 */
function attachLiveValidation(inputId, errorId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    input.addEventListener('input', () => clearError(errorId, inputId));
}

// ================================================
// LOADING STATE HELPERS
// ================================================

/**
 * Put a submit button into loading state
 * @param {HTMLButtonElement} btn
 */
function setLoadingState(btn) {
    const textEl    = btn.querySelector('.btn-text');
    const loadingEl = btn.querySelector('.btn-loading');
    if (textEl)    textEl.style.display    = 'none';
    if (loadingEl) loadingEl.style.display = 'inline-flex';
    btn.disabled = true;
}

/**
 * Restore a submit button from loading state
 * @param {HTMLButtonElement} btn
 */
function clearLoadingState(btn) {
    const textEl    = btn.querySelector('.btn-text');
    const loadingEl = btn.querySelector('.btn-loading');
    if (textEl)    textEl.style.display    = '';
    if (loadingEl) loadingEl.style.display = 'none';
    btn.disabled = false;
}

// ================================================
// GENERAL HELPERS
// ================================================

/**
 * Debounce a function
 * @param {Function} fn
 * @param {number} delay
 * @returns {Function}
 */
function debounce(fn, delay) {
    let timer;
    return function (...args) {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), delay);
    };
}

/**
 * Format number as currency (UGX)
 * @param {number} amount
 * @returns {string}
 */
function formatUGX(amount) {
    return 'UGX ' + Number(amount).toLocaleString('en-UG', { minimumFractionDigits: 0 });
}

/**
 * Truncate a string to maxLength with ellipsis
 * @param {string} str
 * @param {number} maxLength
 * @returns {string}
 */
function truncateText(str, maxLength) {
    return str.length > maxLength ? str.slice(0, maxLength) + '…' : str;
}

/**
 * Auto-dismiss Bootstrap alert after `ms` milliseconds
 * @param {HTMLElement} alertEl
 * @param {number} [ms=5000]
 */
function autoDismissAlert(alertEl, ms = 5000) {
    if (!alertEl) return;
    setTimeout(() => {
        alertEl.style.transition = 'opacity 0.4s';
        alertEl.style.opacity    = '0';
        setTimeout(() => alertEl.remove(), 400);
    }, ms);
}

// ================================================
// ON DOM READY
// ================================================

document.addEventListener('DOMContentLoaded', () => {
    // Auto-dismiss any Bootstrap alerts on page load
    document.querySelectorAll('.alert.alert-dismissible').forEach(el => {
        autoDismissAlert(el, 6000);
    });

    // Attach live validation to common fields (if they exist on this page)
    const fieldPairs = [
        ['email',            'emailError'],
        ['password',         'passwordError'],
        ['confirm_password', 'confirmPasswordError'],
        ['first_name',       'firstNameError'],
        ['last_name',        'lastNameError'],
        ['phone_number',     'phoneError'],
        ['student_id',       'studentIdError'],
    ];

    fieldPairs.forEach(([inputId, errorId]) => attachLiveValidation(inputId, errorId));
});
