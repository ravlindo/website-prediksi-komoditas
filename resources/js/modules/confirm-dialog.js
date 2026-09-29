export function initConfirmDialog() {
    const overlay = document.querySelector('#confirmOverlay');
    const dialog = overlay?.querySelector('.confirm-dialog');
    const title = document.querySelector('#confirmTitle');
    const message = document.querySelector('#confirmMessage');
    const accept = document.querySelector('#confirmAccept');
    const cancel = document.querySelector('#confirmCancel');
    const closeButton = document.querySelector('#confirmClose');
    let pendingForm = null;

    if (!overlay) return;

    const close = () => {
        overlay.classList.remove('visible');
        window.setTimeout(() => { overlay.hidden = true; pendingForm = null; }, 180);
    };

    document.querySelectorAll('[data-confirm-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed === 'true') return;
            event.preventDefault();
            pendingForm = form;
            title.textContent = form.dataset.confirmTitle || 'Konfirmasi tindakan';
            message.textContent = form.dataset.confirmMessage || 'Tindakan ini tidak dapat dibatalkan.';
            overlay.hidden = false;
            window.requestAnimationFrame(() => overlay.classList.add('visible'));
            cancel.focus();
        });
    });

    accept?.addEventListener('click', () => {
        if (!pendingForm) return;
        pendingForm.dataset.confirmed = 'true';
        accept.disabled = true;
        accept.querySelector('span').textContent = 'Menghapus...';
        pendingForm.requestSubmit();
    });
    cancel?.addEventListener('click', close);
    closeButton?.addEventListener('click', close);
    overlay.addEventListener('click', (event) => { if (event.target === overlay) close(); });
    dialog?.addEventListener('click', (event) => event.stopPropagation());
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !overlay.hidden) close(); });
}
