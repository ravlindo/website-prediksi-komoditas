export function initNavigation() {
    const sidebar = document.querySelector('#sidebar');
    const overlay = document.querySelector('#sidebarOverlay');
    const collapseButton = document.querySelector('#sidebarCollapse');

    if (window.localStorage.getItem('sidebar-collapsed') === 'true') {
        document.body.classList.add('sidebar-collapsed');
    }

    collapseButton?.addEventListener('click', () => {
        const isCollapsed = document.body.classList.toggle('sidebar-collapsed');
        window.localStorage.setItem('sidebar-collapsed', String(isCollapsed));
        collapseButton.setAttribute('aria-label', isCollapsed ? 'Perbesar sidebar' : 'Perkecil sidebar');
    });

    document.querySelector('#menuToggle')?.addEventListener('click', () => {
        sidebar?.classList.toggle('open');
        overlay?.classList.toggle('show');
    });

    overlay?.addEventListener('click', () => closeMenu(sidebar, overlay));
    document.querySelectorAll('.nav-item').forEach(item => item.addEventListener('click', () => {
        document.querySelectorAll('.nav-item').forEach(link => link.classList.remove('active'));
        item.classList.add('active');
        closeMenu(sidebar, overlay);
    }));

    document.querySelector('#pageBack')?.addEventListener('click', (event) => {
        const fallback = event.currentTarget.dataset.fallback;
        if (window.history.length > 1 && document.referrer) {
            window.history.back();
        } else if (fallback) {
            window.location.href = fallback;
        }
    });
}

function closeMenu(sidebar, overlay) {
    sidebar?.classList.remove('open');
    overlay?.classList.remove('show');
}
