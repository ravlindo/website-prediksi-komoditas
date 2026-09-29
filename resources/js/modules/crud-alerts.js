export function initCrudAlerts() {
    document.querySelectorAll('[data-crud-toast]').forEach((toast) => {
        const close = () => {
            toast.classList.add('leaving');
            window.setTimeout(() => toast.remove(), 260);
        };

        toast.querySelectorAll('[data-toast-close]').forEach((button) => button.addEventListener('click', close));
        let timer = window.setTimeout(close, 5500);

        toast.addEventListener('mouseenter', () => {
            window.clearTimeout(timer);
            toast.classList.add('paused');
        });
        toast.addEventListener('mouseleave', () => {
            toast.classList.remove('paused');
            window.clearTimeout(timer);
            timer = window.setTimeout(close, 2500);
        });
    });
}
