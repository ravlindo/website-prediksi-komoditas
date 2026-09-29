export function showToast(message) {
    const toast = document.querySelector('#toast');
    if (!toast) return;

    toast.textContent = `✓ ${message}`;
    toast.classList.add('show');
    window.setTimeout(() => toast.classList.remove('show'), 2400);
}
