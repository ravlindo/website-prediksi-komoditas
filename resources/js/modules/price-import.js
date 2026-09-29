export function initPriceImport() {
    const input = document.querySelector('#importFile');
    const zone = document.querySelector('#fileDropzone');
    const name = document.querySelector('#fileName');
    if (!input || !zone || !name) return;

    const showFile = (file) => {
        if (!file) return;
        name.textContent = `${file.name} · ${(file.size / 1024 / 1024).toFixed(2)} MB`;
        zone.classList.add('has-file');
    };
    input.addEventListener('change', () => showFile(input.files[0]));
    ['dragenter', 'dragover'].forEach(type => zone.addEventListener(type, event => { event.preventDefault(); zone.classList.add('dragging'); }));
    ['dragleave', 'drop'].forEach(type => zone.addEventListener(type, event => { event.preventDefault(); zone.classList.remove('dragging'); }));
    zone.addEventListener('drop', event => {
        const file = event.dataTransfer?.files?.[0];
        if (!file) return;
        const transfer = new DataTransfer(); transfer.items.add(file); input.files = transfer.files; showFile(file);
    });
}
