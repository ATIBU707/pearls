/**
 * Main JavaScript
 * Online Hostel Management System - Pearls of Wisdom Hostel
 *
 * Global UI behaviors loaded on every page.
 */

'use strict';

document.addEventListener('DOMContentLoaded', () => {

    // ================================================
    // AUTO-DISMISS ALERTS
    // ================================================
    document.querySelectorAll('.alert.alert-dismissible').forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 6000);
    });

    // ================================================
    // ACTIVE NAV LINK
    // ================================================
    const currentPath = window.location.pathname;
    document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
        try {
            const linkPath = new URL(link.href).pathname;
            if (currentPath.endsWith(linkPath) || currentPath === linkPath) {
                link.classList.add('active');
            }
        } catch (_) {}
    });

    // ================================================
    // SMOOTH SCROLL FOR ANCHOR LINKS
    // ================================================
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', e => {
            const target = document.querySelector(anchor.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // ================================================
    // CONFIRM DIALOG FOR DANGEROUS ACTIONS
    // ================================================
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', e => {
            const message = el.getAttribute('data-confirm') || 'Are you sure?';
            if (!confirm(message)) e.preventDefault();
        });
    });

    // ================================================
    // NOTIFICATION BADGE (unread count)
    // ================================================
    const notifBadge = document.getElementById('notifBadge');
    if (notifBadge) {
        fetch('/online/public/api/notifications.php?action=unread_count', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.ok ? r.json() : null)
        .then(data => {
            if (data && data.count > 0) {
                notifBadge.textContent = data.count > 99 ? '99+' : data.count;
                notifBadge.style.display = 'inline-flex';
            }
        })
        .catch(() => {}); // Fail silently
    }

    // ================================================
    // BACK TO TOP BUTTON
    // ================================================
    const backTop = document.getElementById('backToTop');
    if (backTop) {
        window.addEventListener('scroll', () => {
            backTop.style.opacity = window.scrollY > 400 ? '1' : '0';
            backTop.style.pointerEvents = window.scrollY > 400 ? 'auto' : 'none';
        }, { passive: true });

        backTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // ================================================
    // TOOLTIP INITIALIZATION (Bootstrap)
    // ================================================
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el, { trigger: 'hover' });
    });

    // ================================================
    // TABLE ROW CLICKABLE (rows with data-href)
    // ================================================
    document.querySelectorAll('tr[data-href]').forEach(row => {
        row.style.cursor = 'pointer';
        row.addEventListener('click', e => {
            // Only navigate if click was not on a button/link
            if (!e.target.closest('button, a, input, select')) {
                window.location.href = row.getAttribute('data-href');
            }
        });
    });

    // ================================================
    // COPY TO CLIPBOARD (elements with data-copy)
    // ================================================
    document.querySelectorAll('[data-copy]').forEach(el => {
        el.addEventListener('click', () => {
            const text = el.getAttribute('data-copy');
            navigator.clipboard.writeText(text).then(() => {
                const orig = el.innerHTML;
                el.innerHTML = '<i class="fas fa-check"></i> Copied!';
                el.classList.add('text-success');
                setTimeout(() => {
                    el.innerHTML = orig;
                    el.classList.remove('text-success');
                }, 2000);
            });
        });
    });

    // ================================================
    // SEARCH INPUT – CLEAR BUTTON
    // ================================================
    document.querySelectorAll('.search-with-clear').forEach(wrapper => {
        const input = wrapper.querySelector('input[type="search"], input[type="text"]');
        const clearBtn = wrapper.querySelector('.search-clear');

        if (!input || !clearBtn) return;

        input.addEventListener('input', () => {
            clearBtn.style.display = input.value ? 'flex' : 'none';
        });

        clearBtn.addEventListener('click', () => {
            input.value = '';
            clearBtn.style.display = 'none';
            input.focus();
            input.dispatchEvent(new Event('input'));
        });
    });

    // ================================================
    // LOADING OVERLAY (manual trigger)
    // ================================================
    window.showLoading = () => {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) overlay.style.display = 'flex';
    };

    window.hideLoading = () => {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) overlay.style.display = 'none';
    };

});

// ================================================
// GLOBAL AJAX HELPER
// ================================================

/**
 * Simple AJAX GET/POST helper using fetch
 * @param {string} url
 * @param {object} [options]
 * @param {string} [options.method] default 'GET'
 * @param {object} [options.body]   POST body (will be JSON-encoded)
 * @returns {Promise<object>}
 */
window.ajax = async function(url, options = {}) {
    const method  = (options.method || 'GET').toUpperCase();
    const headers = { 'X-Requested-With': 'XMLHttpRequest' };

    let body;
    if (options.body && method !== 'GET') {
        headers['Content-Type'] = 'application/json';
        body = JSON.stringify(options.body);
    }

    const response = await fetch(url, { method, headers, body });

    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    return response.json();
};
