/**
 * Global UX & Interaction Enhancements for Central Ticketing
 * - Toast Notification System
 * - Interactive Modal Confirmation (Replacing native confirm/alert)
 * - Auto Form Submit Loading State & Double-Click Protection
 */

// 1. Toast Notification System
window.showToast = function (message, type = 'success', duration = 3500) {
    let container = document.getElementById('globalToastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'globalToastContainer';
        container.className = 'fixed bottom-5 right-5 z-50 flex flex-col gap-2.5 max-w-sm pointer-events-none';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'pointer-events-auto transform transition-all duration-300 translate-y-4 opacity-0 flex items-center justify-between gap-3 px-4 py-3 rounded-2xl shadow-xl border text-xs sm:text-sm font-semibold backdrop-blur-md';

    let iconHtml = '';
    if (type === 'success') {
        toast.className += ' bg-emerald-50/95 dark:bg-emerald-950/90 border-emerald-200 dark:border-emerald-800 text-emerald-900 dark:text-emerald-200 shadow-emerald-500/10';
        iconHtml = '<i class="fa-solid fa-circle-check text-emerald-600 dark:text-emerald-400 text-base"></i>';
    } else if (type === 'error') {
        toast.className += ' bg-rose-50/95 dark:bg-rose-950/90 border-rose-200 dark:border-rose-800 text-rose-900 dark:text-rose-200 shadow-rose-500/10';
        iconHtml = '<i class="fa-solid fa-circle-exclamation text-rose-600 dark:text-rose-400 text-base"></i>';
    } else if (type === 'warning') {
        toast.className += ' bg-amber-50/95 dark:bg-amber-950/90 border-amber-200 dark:border-amber-800 text-amber-900 dark:text-amber-200 shadow-amber-500/10';
        iconHtml = '<i class="fa-solid fa-triangle-exclamation text-amber-600 dark:text-amber-400 text-base"></i>';
    } else {
        toast.className += ' bg-sky-50/95 dark:bg-sky-950/90 border-sky-200 dark:border-sky-800 text-sky-900 dark:text-sky-200 shadow-sky-500/10';
        iconHtml = '<i class="fa-solid fa-circle-info text-sky-600 dark:text-sky-400 text-base"></i>';
    }

    toast.innerHTML = `
        <div class="flex items-center gap-2.5">
            ${iconHtml}
            <span class="leading-snug">${message}</span>
        </div>
        <button type="button" class="w-6 h-6 flex items-center justify-center opacity-70 hover:opacity-100 rounded-lg transition-opacity" onclick="this.parentElement.remove()">
            <i class="fa-solid fa-xmark text-xs"></i>
        </button>
    `;

    container.appendChild(toast);

    // Animate in
    requestAnimationFrame(() => {
        toast.classList.remove('translate-y-4', 'opacity-0');
        toast.classList.add('translate-y-0', 'opacity-100');
    });

    // Auto dismiss
    setTimeout(() => {
        toast.classList.remove('translate-y-0', 'opacity-100');
        toast.classList.add('translate-y-2', 'opacity-0');
        setTimeout(() => toast.remove(), 300);
    }, duration);
};

// 2. Interactive Modal Confirmation
window.confirmAction = function ({
    title = 'Konfirmasi Tindakan',
    message = 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
    confirmText = 'Ya, Lanjutkan',
    confirmClass = 'bg-rose-600 hover:bg-rose-500 text-white',
    icon = 'fa-solid fa-triangle-exclamation',
    iconBg = 'bg-rose-100 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400',
    onConfirm = () => {}
} = {}) {
    const modal = document.getElementById('globalConfirmModal');
    if (!modal) {
        if (confirm(message)) {
            onConfirm();
        }
        return;
    }

    document.getElementById('globalConfirmTitle').textContent = title;
    document.getElementById('globalConfirmMessage').textContent = message;

    const iconWrapper = document.getElementById('globalConfirmIconWrapper');
    if (iconWrapper) {
        iconWrapper.className = `w-10 h-10 rounded-xl flex items-center justify-center text-lg flex-shrink-0 ${iconBg}`;
        iconWrapper.innerHTML = `<i class="${icon}"></i>`;
    }

    const btn = document.getElementById('globalConfirmBtn');
    btn.className = `px-4 py-2 rounded-xl text-xs font-semibold shadow-md transition-all ${confirmClass}`;
    btn.textContent = confirmText;

    // Replace button to clear stale event listeners
    const newBtn = btn.cloneNode(true);
    btn.parentNode.replaceChild(newBtn, btn);

    newBtn.addEventListener('click', () => {
        closeConfirmModal();
        onConfirm();
    });

    modal.classList.remove('hidden');
};

window.closeConfirmModal = function () {
    const modal = document.getElementById('globalConfirmModal');
    if (modal) modal.classList.add('hidden');
};

// 3. Automatic Form Submit Loading State & Anti-Double-Click
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function (e) {
            // Check if form is already submitting
            if (this.dataset.submitting === 'true') {
                e.preventDefault();
                return;
            }

            // Find primary submit button
            const submitBtn = this.querySelector('button[type="submit"]:not([data-no-loading])');
            if (submitBtn) {
                this.dataset.submitting = 'true';
                submitBtn.dataset.originalHtml = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-75', 'cursor-not-allowed');

                // Keep width stable
                submitBtn.style.minWidth = `${submitBtn.offsetWidth}px`;

                const label = submitBtn.dataset.loadingText || 'Memproses...';
                submitBtn.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin text-xs"></i> <span>${label}</span>`;

                // Timeout fallback if page does not reload
                setTimeout(() => {
                    this.dataset.submitting = 'false';
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-75', 'cursor-not-allowed');
                    if (submitBtn.dataset.originalHtml) {
                        submitBtn.innerHTML = submitBtn.dataset.originalHtml;
                    }
                }, 8000);
            }
        });
    });
});
