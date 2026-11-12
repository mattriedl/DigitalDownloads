// Small progressive enhancements. Every page works without JavaScript.

// Mobile menu and category drawer toggles: <button data-toggle="target-id">
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-toggle]');
    if (!button) return;
    const target = document.getElementById(button.dataset.toggle);
    if (!target) return;
    const open = target.hasAttribute('hidden');
    target.toggleAttribute('hidden', !open);
    button.setAttribute('aria-expanded', String(open));
});

// Dialogs: <button data-open-dialog="id"> and <button data-close-dialog>
document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-open-dialog]');
    if (opener) {
        document.getElementById(opener.dataset.openDialog)?.showModal();
        return;
    }
    const closer = event.target.closest('[data-close-dialog]');
    if (closer) closer.closest('dialog')?.close();
});

// Cookie notice: remembered per browser.
const notice = document.getElementById('cookie-notice');
if (notice) {
    let seen = false;
    try {
        seen = localStorage.getItem('cookie-notice') === '1';
    } catch {}
    if (!seen) notice.removeAttribute('hidden');
    notice.querySelector('button')?.addEventListener('click', () => {
        try {
            localStorage.setItem('cookie-notice', '1');
        } catch {}
        notice.setAttribute('hidden', '');
    });
}

// Download countdown: counts down, draws the ring, then reveals the real link.
const countdown = document.querySelector('[data-countdown]');
if (countdown) {
    const total = Number(countdown.dataset.countdown) || 0;
    const ring = countdown.querySelector('[data-ring]');
    const number = countdown.querySelector('[data-seconds]');
    const ready = document.querySelectorAll('[data-ready]');
    const waiting = document.querySelectorAll('[data-waiting]');
    const circumference = ring ? Number(ring.getAttribute('stroke-dasharray')) : 0;
    let left = total;

    const draw = () => {
        if (number) number.textContent = String(left);
        if (ring && total > 0) ring.style.strokeDashoffset = String(circumference * (left / total));
    };

    const finish = () => {
        waiting.forEach((el) => el.setAttribute('hidden', ''));
        ready.forEach((el) => el.removeAttribute('hidden'));
        document.querySelector('[data-ready] a')?.focus();
    };

    draw();
    if (total <= 0) {
        finish();
    } else {
        const timer = setInterval(() => {
            // Only count while the tab is visible, so the ads on the page are actually seen.
            if (document.visibilityState !== 'visible') return;
            left -= 1;
            draw();
            if (left <= 0) {
                clearInterval(timer);
                finish();
            }
        }, 1000);
    }
}
