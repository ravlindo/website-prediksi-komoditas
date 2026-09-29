export function initPredictionChart() {
    document.querySelectorAll('[data-prediction-chart]').forEach(chart => {
        const tooltip = chart.querySelector('[data-chart-tooltip]');
        const focuses = [...chart.querySelectorAll('[data-chart-focus]')];
        if (!tooltip || !focuses.length) return;

        const hide = () => {
            tooltip.classList.add('is-hidden');
            tooltip.setAttribute('aria-hidden', 'true');
            focuses.forEach(item => {
                item.classList.remove('is-focused');
                if (item.matches('button')) item.setAttribute('aria-expanded', 'false');
            });
        };

        const write = (target) => {
            tooltip.classList.remove('is-hidden');
            tooltip.setAttribute('aria-hidden', 'false');
            tooltip.querySelector('[data-tooltip-kind]').textContent = target.dataset.kind || 'Informasi harga';
            tooltip.querySelector('[data-tooltip-date]').textContent = target.dataset.date || '-';
            tooltip.querySelector('[data-tooltip-value]').textContent = target.dataset.value || '-';
            tooltip.querySelector('[data-tooltip-status]').textContent = target.dataset.status || '';
            const range = tooltip.querySelector('[data-tooltip-range]');
            range.textContent = target.dataset.range || '';
            range.classList.toggle('is-hidden', !target.dataset.range);

            focuses.forEach(item => {
                item.classList.remove('is-focused');
                if (item.matches('button')) item.setAttribute('aria-expanded', 'false');
            });
            target.classList.add('is-focused');
            if (target.matches('button')) target.setAttribute('aria-expanded', 'true');
            tooltip.classList.remove('is-updating');
            void tooltip.offsetWidth;
            tooltip.classList.add('is-updating');
        };

        const toggle = (target) => {
            const isOpenTarget = target.classList.contains('is-focused') && !tooltip.classList.contains('is-hidden');
            isOpenTarget ? hide() : write(target);
        };

        focuses.forEach(target => {
            if (target.matches('button')) target.setAttribute('aria-expanded', 'false');
            target.addEventListener('click', () => toggle(target));
            target.addEventListener('keydown', event => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    toggle(target);
                }
            });
        });

        document.addEventListener('click', event => {
            if (!event.target.closest('[data-chart-focus]')) hide();
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') hide();
        });
    });
}
