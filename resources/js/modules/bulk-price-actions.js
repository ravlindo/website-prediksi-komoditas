export function initBulkPriceActions() {
    const selectAll = document.querySelector('#selectAllPrices');
    const checks = [...document.querySelectorAll('.price-row-check')];
    const count = document.querySelector('#selectedCount');
    const button = document.querySelector('#deleteSelectedButton');
    if (!selectAll || !checks.length || !count || !button) return;

    const update = () => {
        const selected = checks.filter(check => check.checked).length;
        count.textContent = `${selected} data dipilih`;
        button.disabled = selected === 0;
        selectAll.checked = selected === checks.length;
        selectAll.indeterminate = selected > 0 && selected < checks.length;
        checks.forEach(check => check.closest('tr')?.classList.toggle('selected-row', check.checked));
    };
    selectAll.addEventListener('change', () => { checks.forEach(check => { check.checked = selectAll.checked; }); update(); });
    checks.forEach(check => check.addEventListener('change', update));
    update();
}
