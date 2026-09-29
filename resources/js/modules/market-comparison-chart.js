function setMarketVisibility(root, key, visible) {
    root.querySelectorAll(`[data-market-series="${key}"]`).forEach((series) => series.classList.toggle('is-hidden', !visible));
    root.querySelectorAll(`[data-market-line-toggle="${key}"], [data-market-card-toggle="${key}"]`).forEach((control) => {
        control.classList.toggle('is-off', !visible);
        control.setAttribute('aria-pressed', visible ? 'true' : 'false');
    });
}

function updateMarketCounter(root) {
    const toggles = [...root.querySelectorAll('[data-market-line-toggle]')];
    const active = toggles.filter((toggle) => toggle.getAttribute('aria-pressed') === 'true').length;
    const counter = root.querySelector('[data-market-active-count]');
    const showAll = root.querySelector('[data-market-show-all]');
    if (counter) counter.textContent = String(active);
    if (showAll) showAll.hidden = active === toggles.length;
}

function toggleMarket(root, key) {
    const control = root.querySelector(`[data-market-line-toggle="${key}"]`);
    setMarketVisibility(root, key, control?.getAttribute('aria-pressed') !== 'true');
    updateMarketCounter(root);
}

function positionTooltip(root, point) {
    const tooltip = root.querySelector('[data-market-tooltip]');
    const wrap = root.querySelector('.market-chart-wrap');
    if (!tooltip || !wrap) return;
    const pointRect = point.getBoundingClientRect();
    const wrapRect = wrap.getBoundingClientRect();
    const x = pointRect.left - wrapRect.left + (pointRect.width / 2) + wrap.scrollLeft;
    const y = pointRect.top - wrapRect.top + wrap.scrollTop;
    tooltip.querySelector('[data-market-tooltip-name]').textContent = point.dataset.marketName || '';
    tooltip.querySelector('[data-market-tooltip-date]').textContent = point.dataset.marketDate || '';
    tooltip.querySelector('[data-market-tooltip-price]').textContent = point.dataset.marketPrice || '';
    tooltip.querySelector('[data-market-tooltip-color]').style.background = point.dataset.marketColor || '#2f72dc';
    tooltip.style.left = `${Math.max(105, Math.min(x, wrap.scrollWidth - 105))}px`;
    tooltip.style.top = `${Math.max(92, y)}px`;
    tooltip.hidden = false;
}

function hideTooltip(root) {
    const tooltip = root.querySelector('[data-market-tooltip]');
    if (tooltip) tooltip.hidden = true;
}

export function initMarketComparisonChart() {
    document.querySelectorAll('[data-market-comparison-chart]').forEach((root) => {
        const dateChips = [...root.querySelectorAll('[data-market-date-chip]')];
        const dateSelectionLine = root.querySelector('[data-market-date-selection-line]');
        const datePricePopup = root.querySelector('[data-market-date-price-popup]');
        const datePriceTitle = root.querySelector('[data-market-date-price-title]');
        const datePriceList = root.querySelector('[data-market-date-price-list]');
        const chartWrap = root.querySelector('.market-chart-wrap');
        let activeDateIndex = null;

        const clearDateSelection = () => {
            dateChips.forEach((chip) => {
                chip.classList.remove('selected');
                chip.setAttribute('aria-pressed', 'false');
            });
            root.querySelectorAll('[data-market-date-index]').forEach((point) => point.classList.remove('date-selected-point'));
            if (dateSelectionLine) dateSelectionLine.hidden = true;
            if (datePricePopup) datePricePopup.hidden = true;
            activeDateIndex = null;
        };

        const selectDate = (index) => {
            if (activeDateIndex === index) {
                clearDateSelection();
                return;
            }

            clearDateSelection();
            const chip = root.querySelector(`[data-market-date-chip="${index}"]`);
            if (!chip) return;
            chip.classList.add('selected');
            chip.setAttribute('aria-pressed', 'true');
            root.querySelectorAll(`[data-market-date-index="${index}"]`).forEach((point) => point.classList.add('date-selected-point'));
            if (dateSelectionLine) {
                const x = chip.dataset.marketDateX || '0';
                dateSelectionLine.setAttribute('x1', x);
                dateSelectionLine.setAttribute('x2', x);
                dateSelectionLine.hidden = false;
            }
            if (datePricePopup && datePriceTitle && datePriceList && chartWrap) {
                const template = root.querySelector(`[data-market-date-price-template="${index}"]`);
                const selectedPoint = root.querySelector(`[data-market-date-index="${index}"]`);
                const wrapRect = chartWrap.getBoundingClientRect();
                let x = (Number(chip.dataset.marketDateX || 0) / 1040) * chartWrap.clientWidth;
                if (selectedPoint) {
                    const pointRect = selectedPoint.getBoundingClientRect();
                    x = pointRect.left - wrapRect.left + (pointRect.width / 2) + chartWrap.scrollLeft;
                }
                datePriceTitle.textContent = chip.dataset.marketDateLabel || '';
                datePriceList.innerHTML = template?.innerHTML || '';
                datePricePopup.style.left = `${Math.max(155, Math.min(x, chartWrap.scrollWidth - 155))}px`;
                datePricePopup.hidden = false;
                hideTooltip(root);
            }
            activeDateIndex = index;
        };

        dateChips.forEach((chip) => chip.addEventListener('click', () => selectDate(chip.dataset.marketDateChip)));
        root.querySelectorAll('[data-market-line-toggle]').forEach((button) => button.addEventListener('click', () => toggleMarket(root, button.dataset.marketLineToggle)));
        root.querySelectorAll('[data-market-card-toggle]').forEach((button) => button.addEventListener('click', () => toggleMarket(root, button.dataset.marketCardToggle)));
        root.querySelector('[data-market-show-all]')?.addEventListener('click', () => {
            root.querySelectorAll('[data-market-line-toggle]').forEach((button) => setMarketVisibility(root, button.dataset.marketLineToggle, true));
            updateMarketCounter(root);
        });
        root.querySelectorAll('[data-market-point]').forEach((point) => {
            point.addEventListener('mouseenter', () => positionTooltip(root, point));
            point.addEventListener('focus', () => positionTooltip(root, point));
            point.addEventListener('click', () => selectDate(point.dataset.marketDateIndex));
            point.addEventListener('mouseleave', () => hideTooltip(root));
            point.addEventListener('blur', () => hideTooltip(root));
        });
        root.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                hideTooltip(root);
                clearDateSelection();
            }
        });
    });
}
