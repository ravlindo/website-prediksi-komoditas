export function initMarketPredictionEvaluation() {
    document.querySelectorAll('[data-market-evaluation-chart]').forEach(chart => {
        const tooltip = chart.querySelector('[data-eval-tooltip]');
        const points = [...chart.querySelectorAll('[data-eval-point]')];
        if (!tooltip || !points.length) return;

        const close = () => {
            tooltip.classList.add('is-hidden');
            points.forEach(point => point.classList.remove('is-active'));
        };

        const open = point => {
            points.forEach(item => item.classList.remove('is-active'));
            point.classList.add('is-active');
            tooltip.querySelector('[data-eval-date]').textContent = point.dataset.date;
            tooltip.querySelector('[data-eval-actual]').textContent = point.dataset.actual;
            tooltip.querySelector('[data-eval-forecast]').textContent = point.dataset.forecast;
            tooltip.querySelector('[data-eval-difference]').textContent = point.dataset.difference;
            tooltip.classList.remove('is-hidden');
        };

        points.forEach(point => {
            point.addEventListener('click', event => {
                event.stopPropagation();
                point.classList.contains('is-active') ? close() : open(point);
            });
            point.addEventListener('keydown', event => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    open(point);
                }
            });
        });
        tooltip.querySelector('[data-eval-close]')?.addEventListener('click', close);
        document.addEventListener('click', event => {
            if (!event.target.closest('[data-market-evaluation-chart]')) close();
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') close();
        });
    });

    document.querySelectorAll('[data-two-market-chart]').forEach(chart => {
        const tooltip = chart.querySelector('[data-market-tooltip]');
        const points = [...chart.querySelectorAll('[data-market-point]')];

        const close = () => {
            tooltip?.classList.add('is-hidden');
            points.forEach(point => point.classList.remove('is-active'));
        };

        const open = point => {
            points.forEach(item => item.classList.remove('is-active'));
            point.classList.add('is-active');
            tooltip.querySelector('[data-market-name]').textContent = point.dataset.market;
            tooltip.querySelector('[data-market-date]').textContent = point.dataset.date;
            tooltip.querySelector('[data-market-actual]').textContent = point.dataset.actual;
            tooltip.querySelector('[data-market-forecast]').textContent = point.dataset.forecast;
            tooltip.querySelector('[data-market-difference]').textContent = point.dataset.difference;
            tooltip.classList.remove('is-hidden');
        };

        points.forEach(point => {
            point.addEventListener('click', event => {
                event.stopPropagation();
                point.classList.contains('is-active') ? close() : open(point);
            });
            point.addEventListener('keydown', event => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    open(point);
                }
            });
        });

        chart.querySelectorAll('[data-series-toggle]').forEach(button => {
            button.addEventListener('click', () => {
                const series = chart.querySelector(`[data-series="${button.dataset.seriesToggle}"]`);
                button.classList.toggle('active');
                series?.classList.toggle('is-hidden-series');
                close();
            });
        });

        tooltip?.querySelector('[data-market-close]')?.addEventListener('click', close);
        document.addEventListener('click', event => {
            if (!event.target.closest('[data-two-market-chart]')) close();
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') close();
        });
    });
}
