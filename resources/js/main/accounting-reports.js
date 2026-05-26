(() => {
    const dataNode = document.getElementById('accountingReportData');
    const chartNode = document.getElementById('accountingReportChart');

    if (!dataNode || !chartNode) {
        return;
    }

    const data = JSON.parse(dataNode.textContent || '{}');

    if (!window.ApexCharts) {
        chartNode.innerHTML = '<div class="chart-empty">-</div>';
        return;
    }

    const currency = data.currency || '';
    const money = (value) => {
        const result = new Intl.NumberFormat(document.documentElement.lang || 'fr', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(Number(value || 0));

        return currency ? `${result} ${currency}` : result;
    };

    new ApexCharts(chartNode, {
        chart: {
            type: 'area',
            height: 300,
            fontFamily: 'JetBrains Mono, monospace',
            toolbar: { show: false },
            redrawOnParentResize: true,
            redrawOnWindowResize: true,
        },
        colors: ['#2563eb', '#10b981', '#f43f5e'],
        series: data.series || [],
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 3 },
        fill: {
            type: 'gradient',
            gradient: { opacityFrom: 0.2, opacityTo: 0.03, stops: [0, 95, 100] },
        },
        grid: { borderColor: '#dbe5f1', strokeDashArray: 4 },
        legend: {
            fontSize: '11px',
            fontWeight: 700,
            labels: { colors: '#64748b' },
        },
        xaxis: {
            categories: data.labels || [],
            labels: { style: { colors: '#64748b', fontSize: '11px' } },
            axisBorder: { color: '#dbe5f1' },
            axisTicks: { color: '#dbe5f1' },
        },
        yaxis: {
            labels: {
                style: { colors: '#64748b', fontSize: '11px' },
                formatter: (value) => new Intl.NumberFormat(document.documentElement.lang || 'fr', {
                    maximumFractionDigits: 0,
                }).format(value),
            },
        },
        tooltip: {
            y: { formatter: (value) => money(value) },
        },
        noData: { text: '-' },
    }).render();
})();
