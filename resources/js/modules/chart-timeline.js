export function initChartTimeline() {
    const chart = document.querySelector('#interactivePriceChart');
    const chips = [...document.querySelectorAll('[data-date-chip]')];
    const points = [...document.querySelectorAll('[data-chart-point]')];
    const line = document.querySelector('#selectedDateLine');
    const popup = document.querySelector('#chartPointPopup');
    const popupDate = document.querySelector('#chartPopupDate');
    const popupPrice = document.querySelector('#chartPopupPrice');
    const popupHoliday = document.querySelector('#chartPopupHoliday');
    const popupHolidayType = document.querySelector('#chartPopupHolidayType');
    const popupHolidayName = document.querySelector('#chartPopupHolidayName');
    const holidayJumps = [...document.querySelectorAll('[data-holiday-jump]')];
    let activeIndex = null;
    if (!chart || !chips.length || !points.length || !line || !popup) return;

    const clearSelection = () => {
        points.forEach(item => item.classList.remove('selected-point'));
        chips.forEach(item => { item.classList.remove('selected'); item.setAttribute('aria-pressed', 'false'); });
        line.hidden = true;
        popup.hidden = true;
        if (popupHoliday) popupHoliday.hidden = true;
        popup.classList.remove('edge-left', 'edge-right');
        activeIndex = null;
    };

    const selectPoint = (index, scrollChip = false) => {
        const point = points[index];
        const chip = chips[index];
        if (!point || !chip) return;

        points.forEach(item => item.classList.remove('selected-point'));
        chips.forEach(item => { item.classList.remove('selected'); item.setAttribute('aria-pressed', 'false'); });
        point.classList.add('selected-point');
        chip.classList.add('selected');
        chip.setAttribute('aria-pressed', 'true');

        const x = Number(point.dataset.x);
        activeIndex = index;
        line.setAttribute('x1', x); line.setAttribute('x2', x); line.hidden = false;
        popupDate.textContent = point.dataset.date;
        popupPrice.textContent = point.dataset.price;
        if (popupHoliday && popupHolidayType && popupHolidayName) {
            const hasHoliday = Boolean(point.dataset.holidayName);
            popupHoliday.hidden = !hasHoliday;
            popupHolidayType.textContent = point.dataset.holidayType || '';
            popupHolidayName.textContent = point.dataset.holidayName || '';
        }
        const pointBox = point.getBoundingClientRect();
        const chartBox = chart.getBoundingClientRect();
        popup.style.left = `${pointBox.left + pointBox.width / 2 - chartBox.left}px`;
        popup.style.top = `${pointBox.top + pointBox.height / 2 - chartBox.top}px`;
        popup.classList.toggle('edge-left', x < 120);
        popup.classList.toggle('edge-right', x > 640);
        popup.hidden = false;
        if (scrollChip) chip.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    };

    const togglePoint = (index, scrollChip = false) => {
        if (activeIndex === index) {
            clearSelection();
            return;
        }
        selectPoint(index, scrollChip);
    };

    chips.forEach((chip, index) => chip.addEventListener('click', () => togglePoint(index)));
    holidayJumps.forEach(item => item.addEventListener('click', () => selectPoint(Number(item.dataset.holidayJump), true)));
    points.forEach((point, index) => {
        point.addEventListener('click', () => togglePoint(index, true));
        point.addEventListener('keydown', event => {
            if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); togglePoint(index, true); }
        });
    });
    window.addEventListener('resize', () => { if (activeIndex !== null) selectPoint(activeIndex); });
}
