(() => {
    const dataNode = document.getElementById('dashboardChartData');

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
        muted: '#64748b',
        grid: '#dbe5f1',
    };
    const seriesNames = {
        subscriptions: data.seriesNames?.subscriptions || 'Subscriptions',
        users: data.seriesNames?.users || 'Users',
        companies: data.seriesNames?.companies || 'Companies',
    };

    const renderFallback = (selector) => {
        const element = document.querySelector(selector);

        if (element) {
            element.innerHTML = `<div class="chart-empty">${data.emptyLabel || 'No data'}</div>`;
        }
    };

    if (!window.ApexCharts) {
        ['#subscriptionsEvolutionChart', '#rolesDistributionChart', '#usersByCompanyChart', '#globalActivityChart'].forEach(renderFallback);
        return;
    }

    const baseOptions = {
        chart: {
            fontFamily: 'JetBrains Mono, monospace',
            toolbar: { show: false },
            animations: { enabled: true, speed: 450 },
        },
        dataLabels: { enabled: false },
        grid: {
            borderColor: colors.grid,
            strokeDashArray: 4,
        },
        legend: {
            fontSize: '12px',
            fontWeight: 700,
            labels: { colors: colors.muted },
            markers: { radius: 2 },
        },
        tooltip: {
            theme: document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light',
        },
        xaxis: {
            labels: { style: { colors: colors.muted, fontSize: '12px' } },
            axisBorder: { color: colors.grid },
            axisTicks: { color: colors.grid },
        },
        yaxis: {
            min: 0,
            labels: { style: { colors: colors.muted, fontSize: '12px' } },
        },
    };

    const renderChart = (selector, options) => {
        const element = document.querySelector(selector);

        if (!element) {
            return null;
        }

        const chart = new ApexCharts(element, options);
        chart.render();

        return chart;
    };

    const periodSeries = (period = 'month') => {
        const periodData = data.periods?.[period] || data.periods?.month || {};

        return {
            labels: periodData.labels || [],
            series: [
                { name: seriesNames.subscriptions, data: periodData.subscriptions || [] },
                { name: seriesNames.users, data: periodData.users || [] },
            ],
        };
    };
    const defaultPeriod = document.querySelector('[data-dashboard-period].active')?.dataset.dashboardPeriod || 'month';
    const defaultPeriodData = periodSeries(defaultPeriod);
    const subscriptionsEvolutionChart = renderChart('#subscriptionsEvolutionChart', {
        ...baseOptions,
        chart: { ...baseOptions.chart, type: 'area', height: 320 },
        colors: [colors.blue, colors.violet],
        stroke: { curve: 'smooth', width: 3 },
        fill: {
            type: 'gradient',
            gradient: { shadeIntensity: 1, opacityFrom: .24, opacityTo: .02, stops: [0, 92, 100] },
        },
        series: defaultPeriodData.series,
        xaxis: { ...baseOptions.xaxis, categories: defaultPeriodData.labels },
    });

    document.querySelectorAll('[data-dashboard-period]').forEach((button) => {
        button.addEventListener('click', () => {
            const nextPeriod = button.dataset.dashboardPeriod || 'month';
            const nextPeriodData = periodSeries(nextPeriod);

            document.querySelectorAll('[data-dashboard-period]').forEach((periodButton) => {
                periodButton.classList.toggle('active', periodButton === button);
            });

            subscriptionsEvolutionChart?.updateOptions({
                series: nextPeriodData.series,
                xaxis: { categories: nextPeriodData.labels },
            });
        });
    });

    renderChart('#rolesDistributionChart', {
        ...baseOptions,
        chart: { ...baseOptions.chart, type: 'donut', height: 300 },
        colors: [colors.blue, colors.rose, colors.violet],
        labels: data.roles?.labels || [],
        series: data.roles?.series || [],
        stroke: { width: 4, colors: ['#ffffff'] },
        plotOptions: {
            pie: {
                donut: { size: '58%' },
            },
        },
        legend: { ...baseOptions.legend, position: 'bottom' },
    });

    renderChart('#usersByCompanyChart', {
        ...baseOptions,
        chart: { ...baseOptions.chart, type: 'bar', height: 320 },
        colors: [colors.violet],
        plotOptions: {
            bar: {
                horizontal: true,
                borderRadius: 7,
                barHeight: '55%',
            },
        },
        series: [{ name: seriesNames.users, data: data.usersByCompany?.series || [] }],
        xaxis: { ...baseOptions.xaxis, categories: data.usersByCompany?.labels || [] },
    });

    renderChart('#globalActivityChart', {
        ...baseOptions,
        chart: { ...baseOptions.chart, type: 'line', height: 290 },
        colors: [colors.blue, colors.amber, colors.violet],
        stroke: { curve: 'smooth', width: 3 },
        markers: { size: 4, strokeWidth: 3 },
        series: [
            { name: seriesNames.subscriptions, data: data.yearlyActivity?.subscriptions || [] },
            { name: seriesNames.companies, data: data.yearlyActivity?.companies || [] },
            { name: seriesNames.users, data: data.yearlyActivity?.users || [] },
        ],
        xaxis: { ...baseOptions.xaxis, categories: data.yearlyActivity?.labels || [] },
    });
})();
