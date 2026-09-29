import { showToast } from './toast';

export function initFilters() {
    document.querySelector('#applyFilter')?.addEventListener('click', () => showToast('Filter berhasil diterapkan'));
    document.querySelector('#refreshButton')?.addEventListener('click', event => {
        event.currentTarget.querySelector('span')?.animate(
            [{ transform: 'rotate(0)' }, { transform: 'rotate(360deg)' }],
            { duration: 700 },
        );
        showToast('Data tampilan berhasil diperbarui');
    });
}
