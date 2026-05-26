(() => {
    const dataNode = document.getElementById('accountingTreasuryData');

    if (!dataNode) {
        return;
    }

    const data = JSON.parse(dataNode.textContent || '{}');
    const colors = {
        blue: '#2563eb',
        green: '#10b981',
        rose: '#f43f5e',
        violet: '#7c3aed',
        amber: '#f59e0b',
        muted: '#64748b',
        grid: '#dbe5f1',
    };
    const currency = data.currency || '';
    const money = (value) => `${new Intl.NumberFormat(document.documentElement.lang || 'fr', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value || 0))} ${currency}`.trim();
    const chartNodes = ['#treasuryFlowChart', '#treasuryBalanceChart', '#treasuryForecastChart'];

    if (!window.ApexCharts) {
        chartNodes.forEach((selector) => {
            const node = document.querySelector(selector);
            if (node) {
                node.innerHTML = '<div class="chart-empty">-</div>';
            }
        });
        return;
    }

    const baseOptions = {
        chart: {
            fontFamily: 'JetBrains Mono, monospace',
            toolbar: { show: false },
            animations: { enabled: true, speed: 400 },
            redrawOnParentResize: true,
            redrawOnWindowResize: true,
        },
        dataLabels: { enabled: false },
        grid: { borderColor: colors.grid, strokeDashArray: 4 },
        legend: {
            fontSize: '11px',
            fontWeight: 700,
            labels: { colors: colors.muted },
        },
        tooltip: {
            y: { formatter: (value) => money(value) },
        },
        xaxis: {
            labels: { style: { colors: colors.muted, fontSize: '11px' } },
            axisBorder: { color: colors.grid },
            axisTicks: { color: colors.grid },
        },
        yaxis: {
            labels: {
                style: { colors: colors.muted, fontSize: '11px' },
                formatter: (value) => new Intl.NumberFormat(document.documentElement.lang || 'fr', { maximumFractionDigits: 0 }).format(value),
            },
        },
    };

    const periodSeries = (period) => {
        const selected = data.periods?.[period] || data.periods?.month || {};
        const inflows = selected.inflows || [];
        const outflows = selected.outflows || [];

        return {
            labels: selected.labels || [],
            series: [
                { name: data.labels?.inflows || 'Entrées', data: inflows },
                { name: data.labels?.outflows || 'Sorties', data: outflows },
                { name: data.labels?.net || 'Flux net', data: inflows.map((value, index) => Number(value || 0) - Number(outflows[index] || 0)) },
            ],
        };
    };

    const initial = periodSeries('month');
    const flowChart = new ApexCharts(document.querySelector('#treasuryFlowChart'), {
        ...baseOptions,
        chart: { ...baseOptions.chart, type: 'area', height: 265 },
        colors: [colors.green, colors.rose, colors.blue],
        stroke: { curve: 'smooth', width: [3, 3, 2] },
        fill: { type: 'gradient', gradient: { opacityFrom: .22, opacityTo: .03, stops: [0, 95, 100] } },
        series: initial.series,
        xaxis: { ...baseOptions.xaxis, categories: initial.labels },
    });
    flowChart.render();

    new ApexCharts(document.querySelector('#treasuryBalanceChart'), {
        ...baseOptions,
        chart: { ...baseOptions.chart, type: 'bar', height: 235 },
        colors: [colors.blue],
        plotOptions: { bar: { borderRadius: 6, horizontal: true, barHeight: '46%' } },
        series: [{ name: data.labels?.balance || 'Solde', data: data.accounts?.series || [] }],
        xaxis: { ...baseOptions.xaxis, categories: data.accounts?.labels || [] },
        yaxis: {
            labels: { style: { colors: colors.muted, fontSize: '11px' } },
        },
    }).render();

    new ApexCharts(document.querySelector('#treasuryForecastChart'), {
        ...baseOptions,
        chart: { ...baseOptions.chart, type: 'bar', height: 235 },
        colors: [colors.blue, colors.green, colors.rose, colors.violet],
        plotOptions: { bar: { borderRadius: 5, distributed: true, columnWidth: '48%' } },
        legend: { show: false },
        series: [{ name: data.labels?.balance || 'Solde', data: data.forecast?.series || [] }],
        xaxis: { ...baseOptions.xaxis, categories: data.forecast?.labels || [] },
    }).render();

    document.querySelectorAll('[data-treasury-period]').forEach((button) => {
        button.addEventListener('click', () => {
            document.querySelectorAll('[data-treasury-period]').forEach((periodButton) => {
                periodButton.classList.toggle('active', periodButton === button);
            });

            const period = periodSeries(button.dataset.treasuryPeriod || 'month');
            flowChart.updateOptions({
                series: period.series,
                xaxis: { categories: period.labels },
            });
        });
    });
})();
