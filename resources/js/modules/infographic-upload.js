export function initInfographicUpload() {
    const input = document.querySelector('[data-infographic-input]');
    const preview = document.querySelector('[data-infographic-preview]');
    if (!input || !preview) return;

    input.addEventListener('change', () => {
        const file = input.files?.[0];
        if (!file) return;
        const existing = preview.querySelector('img');
        if (existing) existing.remove();
        const image = document.createElement('img');
        image.src = URL.createObjectURL(file);
        image.alt = 'Pratinjau gambar baru';
        image.onload = () => URL.revokeObjectURL(image.src);
        preview.prepend(image);
        preview.classList.add('has-image');
        const title = preview.querySelector('strong');
        if (title) title.textContent = file.name;
    });
}
