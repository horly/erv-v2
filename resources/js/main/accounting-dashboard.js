(() => {
    const dataNode = document.getElementById('accountingDashboardData');

    if (!dataNode) {
        return;
    }

    const data = JSON.parse(dataNode.textContent || '{}');
    const colors = {
        blue: '#2563eb',
        violet: '#7c3aed',
        rose: '#f43f5e',
        amber: '#f59e0b',
        green: '#10b981',
        cyan: '#06b6d4',
        muted: '#64748b',
        grid: '#dbe5f1',
        white: '#ffffff',
    };

    const renderFallback = (selector) => {
        const element = document.querySelector(selector);

        if (element) {
            element.innerHTML = `<div class="chart-empty">${data.emptyLabel || 'Aucune donnée'}</div>`;
        }
    };

    if (!window.ApexCharts) {
        [
            '#accountingRevenueChart',
            '#accountingContactsChart',
            '#accountingStockServicesChart',
            '#accountingDocumentsChart',
            '#accountingCashflowChart',
        ].forEach(renderFallback);
        return;
    }

    const charts = [];
    const baseOptions = {
        chart: {
            fontFamily: 'JetBrains Mono, monospace',
            toolbar: { show: false },
            animations: { enabled: true, speed: 450 },
            redrawOnParentResize: true,
            redrawOnWindowResize: true,
        },
        dataLabels: { enabled: false },
        grid: {
            borderColor: colors.grid,
            strokeDashArray: 4,
        },
        legend: {
            fontSize: '11px',
            fontWeight: 700,
            labels: { colors: colors.muted },
            markers: { radius: 2 },
        },
        tooltip: {
            theme: document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light',
        },
        xaxis: {
            labels: { style: { colors: colors.muted, fontSize: '11px' } },
            axisBorder: { color: colors.grid },
            axisTicks: { color: colors.grid },
        },
        yaxis: {
            min: 0,
            labels: { style: { colors: colors.muted, fontSize: '11px' } },
        },
    };

    const renderChart = (selector, options) => {
        const element = document.querySelector(selector);

        if (!element) {
            return null;
        }

        const chart = new ApexCharts(element, options);
        chart.render();
        charts.push(chart);

        return chart;
    };

    const periodSeries = (period = 'month') => {
        const periodData = data.periods?.[period] || data.periods?.month || {};

        return {
            labels: periodData.labels || [],
            series: [
                { name: data.labels?.revenue || 'Revenue', data: periodData.revenue || [] },
                { name: data.labels?.sales || 'Sales', data: periodData.sales || [] },
                { name: data.labels?.expenses || 'Expenses', data: periodData.expenses || [] },
            ],
        };
    };

    const cashflowPeriodSeries = (period = 'month') => {
        const periodData = data.periods?.[period] || data.periods?.month || {};

        return {
            labels: periodData.labels || [],
            series: [
                { name: data.labels?.receivables || 'Receivables', data: periodData.receivables || [] },
                { name: data.labels?.debts || 'Debts', data: periodData.debts || [] },
            ],
        };
    };

    const defaultPeriod = document.querySelector('[data-accounting-period].active')?.dataset.accountingPeriod || 'month';
    const defaultPeriodData = periodSeries(defaultPeriod);
    const defaultCashflowData = cashflowPeriodSeries(defaultPeriod);
    const revenueChart = renderChart('#accountingRevenueChart', {
        ...baseOptions,
        chart: { ...baseOptions.chart, type: 'area', height: 235 },
        colors: [colors.blue, colors.green, colors.rose],
        stroke: { curve: 'smooth', width: 3 },
        fill: {
            type: 'gradient',
            gradient: { shadeIntensity: 1, opacityFrom: .28, opacityTo: .03, stops: [0, 90, 100] },
        },
        series: defaultPeriodData.series,
        xaxis: { ...baseOptions.xaxis, categories: defaultPeriodData.labels },
    });

    const cashflowChart = renderChart('#accountingCashflowChart', {
        ...baseOptions,
        chart: { ...baseOptions.chart, type: 'line', height: 235 },
        colors: [colors.green, colors.rose],
        stroke: { curve: 'smooth', width: 3 },
        markers: { size: 4, strokeWidth: 3 },
        series: defaultCashflowData.series,
        xaxis: { ...baseOptions.xaxis, categories: defaultCashflowData.labels },
    });

    document.querySelectorAll('[data-accounting-period]').forEach((button) => {
        button.addEventListener('click', () => {
            const nextPeriod = button.dataset.accountingPeriod || 'month';
            const nextPeriodData = periodSeries(nextPeriod);
            const nextCashflowData = cashflowPeriodSeries(nextPeriod);

            document.querySelectorAll('[data-accounting-period]').forEach((periodButton) => {
                periodButton.classList.toggle('active', periodButton === button);
            });

            revenueChart?.updateOptions({
                series: nextPeriodData.series,
                xaxis: { categories: nextPeriodData.labels },
            });

            cashflowChart?.updateOptions({
                series: nextCashflowData.series,
                xaxis: { categories: nextCashflowData.labels },
            });
        });
    });

    renderChart('#accountingContactsChart', {
        ...baseOptions,
        chart: { ...baseOptions.chart, type: 'donut', height: 220 },
        colors: [colors.blue, colors.violet, colors.green, colors.amber, colors.rose],
        labels: data.contacts?.labels || [],
        series: data.contacts?.series || [],
        stroke: { width: 4, colors: [colors.white] },
        plotOptions: {
            pie: {
                donut: { size: '58%' },
            },
        },
        legend: { ...baseOptions.legend, position: 'bottom' },
    });

    renderChart('#accountingStockServicesChart', {
        ...baseOptions,
        chart: { ...baseOptions.chart, type: 'bar', height: 220 },
        colors: [colors.violet],
        plotOptions: {
            bar: {
                horizontal: true,
                borderRadius: 6,
                barHeight: '52%',
            },
        },
        series: [{ name: data.labels?.stock || 'Stock', data: data.stockServices?.series || [] }],
        xaxis: { ...baseOptions.xaxis, categories: data.stockServices?.labels || [] },
    });

    renderChart('#accountingDocumentsChart', {
        ...baseOptions,
        chart: { ...baseOptions.chart, type: 'bar', height: '100%' },
        colors: [colors.amber],
        plotOptions: {
            bar: {
                borderRadius: 6,
                columnWidth: '48%',
            },
        },
        series: [{ name: data.labels?.documents || 'Documents', data: data.documents?.series || [] }],
        xaxis: { ...baseOptions.xaxis, categories: data.documents?.labels || [] },
    });

    window.addEventListener('resize', () => {
        charts.forEach((chart) => chart.resize?.());
    });
})();
