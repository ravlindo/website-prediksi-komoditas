export function initPriceTable() {
    document.querySelector('#tableSearch')?.addEventListener('input', event => {
        const term = event.target.value.toLowerCase();
        document.querySelectorAll('#priceTable tr').forEach(row => {
            if (!row.classList.contains('category-row')) {
                row.hidden = !row.textContent.toLowerCase().includes(term);
            }
        });

        document.querySelectorAll('#priceTable .category-row').forEach(categoryRow => {
            let next = categoryRow.nextElementSibling;
            let hasVisibleCommodity = false;
            while (next && !next.classList.contains('category-row')) {
                if (!next.hidden) hasVisibleCommodity = true;
                next = next.nextElementSibling;
            }
            categoryRow.hidden = !hasVisibleCommodity;
        });
    });
}
