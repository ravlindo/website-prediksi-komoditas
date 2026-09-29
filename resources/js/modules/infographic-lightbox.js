export function initInfographicLightbox() {
    const modal = document.querySelector('[data-infographic-lightbox]');
    const cards = [...document.querySelectorAll('[data-infographic-card]')];
    if (!modal || !cards.length) return;

    const image = modal.querySelector('[data-infographic-image]');
    const imageWrap = modal.querySelector('[data-infographic-image-wrap]');
    const stage = modal.querySelector('[data-infographic-stage]');
    const title = modal.querySelector('[data-infographic-title]');
    const description = modal.querySelector('[data-infographic-description]');
    const source = modal.querySelector('[data-infographic-source]');
    const year = modal.querySelector('[data-infographic-year]');
    const position = modal.querySelector('[data-infographic-position]');
    const zoomLabel = modal.querySelector('[data-infographic-zoom-label]');
    const download = modal.querySelector('[data-infographic-download]');
    let current = 0;
    let zoom = 1;
    let originX = 0;
    let originY = 0;
    let dragging = false;
    let startX = 0;
    let startY = 0;
    let lastFocus = null;

    const applyTransform = () => {
        imageWrap.style.transform = `translate(${originX}px, ${originY}px) scale(${zoom})`;
        zoomLabel.value = `${Math.round(zoom * 100)}%`;
        imageWrap.classList.toggle('is-zoomed', zoom > 1);
    };

    const resetZoom = () => {
        zoom = 1;
        originX = 0;
        originY = 0;
        applyTransform();
    };

    const show = (index) => {
        current = (index + cards.length) % cards.length;
        const card = cards[current];
        image.classList.add('is-loading');
        image.src = card.dataset.image;
        image.alt = card.dataset.title;
        title.textContent = card.dataset.title;
        description.textContent = card.dataset.description || 'Visualisasi informasi yang dirangkum agar data lebih cepat dibaca dan dipahami.';
        source.textContent = card.dataset.source || 'Sumber belum ditentukan';
        year.textContent = card.dataset.year;
        position.textContent = `${String(current + 1).padStart(2, '0')} / ${String(cards.length).padStart(2, '0')}`;
        download.href = card.dataset.image;
        download.download = `${card.dataset.title}.jpg`;
        resetZoom();
        image.onload = () => image.classList.remove('is-loading');
    };

    const open = (index, trigger) => {
        lastFocus = trigger;
        show(index);
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('lightbox-open');
        modal.querySelector('[data-infographic-close].lightbox-close')?.focus();
    };

    const close = () => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('lightbox-open');
        resetZoom();
        lastFocus?.focus();
    };

    cards.forEach((card, index) => card.querySelectorAll('[data-infographic-open]').forEach((button) => button.addEventListener('click', () => open(index, button))));
    modal.querySelectorAll('[data-infographic-close]').forEach((button) => button.addEventListener('click', close));
    modal.querySelector('[data-infographic-previous]')?.addEventListener('click', () => show(current - 1));
    modal.querySelector('[data-infographic-next]')?.addEventListener('click', () => show(current + 1));
    modal.querySelector('[data-infographic-zoom-in]')?.addEventListener('click', () => { zoom = Math.min(3, zoom + .25); applyTransform(); });
    modal.querySelector('[data-infographic-zoom-out]')?.addEventListener('click', () => { zoom = Math.max(1, zoom - .25); if (zoom === 1) { originX = 0; originY = 0; } applyTransform(); });

    stage.addEventListener('wheel', (event) => {
        event.preventDefault();
        zoom = Math.max(1, Math.min(3, zoom + (event.deltaY < 0 ? .15 : -.15)));
        if (zoom === 1) { originX = 0; originY = 0; }
        applyTransform();
    }, { passive: false });
    imageWrap.addEventListener('pointerdown', (event) => {
        if (zoom === 1) return;
        dragging = true; startX = event.clientX - originX; startY = event.clientY - originY;
        imageWrap.setPointerCapture(event.pointerId);
    });
    imageWrap.addEventListener('pointermove', (event) => {
        if (!dragging) return;
        originX = event.clientX - startX; originY = event.clientY - startY; applyTransform();
    });
    imageWrap.addEventListener('pointerup', () => { dragging = false; });
    imageWrap.addEventListener('dblclick', () => { zoom = zoom === 1 ? 2 : 1; if (zoom === 1) { originX = 0; originY = 0; } applyTransform(); });

    document.addEventListener('keydown', (event) => {
        if (!modal.classList.contains('is-open')) return;
        if (event.key === 'Escape') close();
        if (event.key === 'ArrowLeft') show(current - 1);
        if (event.key === 'ArrowRight') show(current + 1);
    });
}
