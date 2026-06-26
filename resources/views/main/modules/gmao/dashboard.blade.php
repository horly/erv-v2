<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.gmao_dashboard') }} | {{ app_brand_name() }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body gmao-module-body">
    <div class="dashboard-shell main-shell accounting-shell gmao-shell" data-theme="light">
        @include('main.modules.gmao.partials.sidebar', ['activeGmaoPage' => 'dashboard'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.gmao_dashboard') }}</h1>
                    <p>{{ $company->name }} / {{ $site->name }}</p>
                </div>
                @include('main.modules.partials.accounting-header-actions')
            </header>

            <section class="dashboard-content module-dashboard-page gmao-page">
                <a class="back-link" href="{{ route('main.companies.sites.show', [$company, $site]) }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    {{ $site->name }}
                </a>

                <section class="module-heading">
                    <span class="module-heading-icon module-gmao"><i class="bi bi-tools" aria-hidden="true"></i></span>
                    <div>
                        <h2>{{ __('main.gmao_dashboard') }}</h2>
                        <p>{{ __('main.gmao_dashboard_subtitle') }}</p>
                    </div>
                </section>

                <section class="gmao-kpi-strip" aria-label="{{ __('admin.indicators') }}">
                    @foreach ($dashboard['metrics'] as $metric)
                        <article class="gmao-kpi-card gmao-kpi-{{ $metric['tone'] }}">
                            <div class="gmao-kpi-head">
                                <span class="gmao-kpi-icon"><i class="bi {{ $metric['icon'] }}" aria-hidden="true"></i></span>
                                <small>{{ $metric['label'] }}</small>
                            </div>
                            <div class="gmao-kpi-body">
                                <strong>{{ $metric['value'] }}</strong>
                                <em>{{ $metric['meta'] }}</em>
                            </div>
                            <div class="gmao-kpi-progress" aria-hidden="true">
                                <span style="width: {{ $metric['progress'] }}%"></span>
                            </div>
                        </article>
                    @endforeach
                </section>

                @php
                    $controlChart = [
                        'series' => [
                            (int) $dashboard['availability'],
                            (int) $dashboard['completionRate'],
                            (int) $dashboard['preventiveCompliance'],
                        ],
                        'labels' => [
                            __('main.gmao_availability'),
                            __('main.gmao_completion_rate'),
                            __('main.gmao_preventive_compliance'),
                        ],
                        'totalLabel' => __('main.gmao_control_index'),
                    ];

                    $controlAverage = count($controlChart['series']) > 0
                        ? (int) round(array_sum($controlChart['series']) / count($controlChart['series']))
                        : 0;

                    $controlStats = [
                        ['label' => __('main.gmao_availability'), 'value' => (int) $dashboard['availability'], 'tone' => 'cyan'],
                        ['label' => __('main.gmao_completion_rate'), 'value' => (int) $dashboard['completionRate'], 'tone' => 'blue'],
                        ['label' => __('main.gmao_preventive_compliance'), 'value' => (int) $dashboard['preventiveCompliance'], 'tone' => 'green'],
                    ];
                @endphp

                <section class="gmao-command-center">
                    <article class="company-card gmao-control-panel">
                        <div>
                            <span>{{ __('main.gmao_control_tower') }}</span>
                            <h3>{{ __('main.gmao_operational_summary') }}</h3>
                            <p>{{ __('main.gmao_control_tower_text') }}</p>
                        </div>
                        <div class="gmao-control-chart-wrap">
                            <div class="gmao-control-chart-shell">
                                <div id="gmaoControlChart" class="gmao-control-chart" aria-label="{{ __('main.gmao_operational_summary') }}"></div>
                                <div class="gmao-control-chart-center" aria-hidden="true">
                                    <strong>{{ $controlAverage }}%</strong>
                                    <span>{{ __('main.gmao_control_index') }}</span>
                                </div>
                            </div>
                            <script type="application/json" id="gmaoControlChartData">@json($controlChart)</script>
                            <div class="gmao-control-stats">
                                @foreach ($controlStats as $stat)
                                    <div class="gmao-control-stat gmao-control-stat-{{ $stat['tone'] }}">
                                        <span aria-hidden="true"></span>
                                        <div>
                                            <strong>{{ $stat['value'] }}%</strong>
                                            <small>{{ $stat['label'] }}</small>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </article>

                    <article class="company-card gmao-quick-panel">
                        <div class="hr-panel-header">
                            <div>
                                <span>{{ __('main.gmao_quick_access') }}</span>
                                <h3>{{ __('main.gmao_operations') }}</h3>
                            </div>
                            <i class="bi bi-grid-3x3-gap" aria-hidden="true"></i>
                        </div>
                        <div class="gmao-shortcuts">
                            <a href="{{ route('main.gmao.equipment', [$company, $site]) }}"><i class="bi bi-cpu"></i><span>{{ __('main.gmao_equipment') }}</span></a>
                            <a href="{{ route('main.gmao.work-orders', [$company, $site]) }}"><i class="bi bi-clipboard2-check"></i><span>{{ __('main.gmao_work_orders') }}</span></a>
                            <a href="{{ route('main.gmao.preventive', [$company, $site]) }}"><i class="bi bi-calendar2-week"></i><span>{{ __('main.gmao_preventive') }}</span></a>
                            <a href="{{ route('main.gmao.reports', [$company, $site]) }}"><i class="bi bi-bar-chart"></i><span>{{ __('main.gmao_reports') }}</span></a>
                        </div>
                    </article>
                </section>

                <section class="gmao-risk-grid" aria-label="{{ __('main.gmao_risk_overview') }}">
                    @foreach ($dashboard['riskCards'] as $risk)
                        <article class="gmao-risk-card gmao-risk-{{ $risk['tone'] }}">
                            <span><i class="bi {{ $risk['icon'] }}" aria-hidden="true"></i></span>
                            <div>
                                <strong>{{ $risk['value'] }}</strong>
                                <small>{{ $risk['label'] }}</small>
                            </div>
                        </article>
                    @endforeach
                </section>

                <section class="gmao-command-grid">
                    <article class="hr-panel gmao-health-panel">
                        <div class="hr-panel-header">
                            <div>
                                <span>{{ __('main.gmao_asset_health') }}</span>
                                <h3>{{ __('main.gmao_availability') }}</h3>
                            </div>
                            <strong>{{ $dashboard['availability'] }}%</strong>
                        </div>
                        <div class="hr-progress"><span style="width: {{ $dashboard['availability'] }}%"></span></div>
                        <div class="gmao-status-stack">
                            @foreach ($dashboard['statusRows'] as $row)
                                <div>
                                    <span class="status-pill gmao-status-{{ $row['status'] }}">{{ $row['label'] }}</span>
                                    <strong>{{ $row['count'] }}</strong>
                                </div>
                            @endforeach
                        </div>
                    </article>

                    <article class="hr-panel">
                        <div class="hr-panel-header">
                            <div>
                                <span>{{ __('main.gmao_critical_assets') }}</span>
                                <h3>{{ __('main.gmao_equipment') }}</h3>
                            </div>
                            <a href="{{ route('main.gmao.equipment', [$company, $site]) }}"><i class="bi bi-arrow-right"></i></a>
                        </div>
                        <div class="hr-activity-list">
                            @forelse ($dashboard['criticalEquipment'] as $equipment)
                                <div class="hr-activity-row">
                                    <span><i class="bi bi-cpu" aria-hidden="true"></i></span>
                                    <div>
                                        <strong>{{ $equipment->name }}</strong>
                                        <small>{{ $equipment->reference }} &middot; {{ $equipment->location?->name ?? '-' }}</small>
                                    </div>
                                    <span class="status-pill gmao-status-{{ $equipment->status }}">{{ $equipmentStatusLabels[$equipment->status] ?? $equipment->status }}</span>
                                </div>
                            @empty
                                <div class="module-empty-state module-empty-state-small"><i class="bi bi-shield-check"></i><strong>{{ __('main.gmao_no_critical_assets') }}</strong></div>
                            @endforelse
                        </div>
                    </article>
                </section>

                <section class="gmao-dashboard-grid">
                    <article class="hr-panel">
                        <div class="hr-panel-header">
                            <div><span>{{ __('main.gmao_operations') }}</span><h3>{{ __('main.gmao_active_work_orders') }}</h3></div>
                            <a href="{{ route('main.gmao.work-orders', [$company, $site]) }}"><i class="bi bi-arrow-right"></i></a>
                        </div>
                        <div class="hr-activity-list">
                            @forelse ($dashboard['activeOrders'] as $order)
                                <div class="hr-activity-row gmao-priority-row">
                                    <span><i class="bi bi-tools" aria-hidden="true"></i></span>
                                    <div>
                                        <strong>{{ $order->title }}</strong>
                                        <small>{{ $order->reference }} &middot; {{ $order->equipment?->name ?? '-' }} &middot; {{ $order->planned_at?->format('d/m/Y H:i') ?? '-' }}</small>
                                    </div>
                                    <span class="status-pill gmao-status-{{ $order->status }}">{{ $orderStatusLabels[$order->status] ?? $order->status }}</span>
                                </div>
                            @empty
                                <div class="module-empty-state module-empty-state-small"><i class="bi bi-check2-circle"></i><strong>{{ __('main.gmao_no_active_orders') }}</strong></div>
                            @endforelse
                        </div>
                    </article>

                    <article class="hr-panel">
                        <div class="hr-panel-header">
                            <div><span>{{ __('main.gmao_requests') }}</span><h3>{{ __('main.gmao_urgent_requests') }}</h3></div>
                            <a href="{{ route('main.gmao.requests', [$company, $site]) }}"><i class="bi bi-arrow-right"></i></a>
                        </div>
                        <div class="hr-activity-list">
                            @forelse ($dashboard['urgentRequests'] as $request)
                                <div class="hr-activity-row gmao-priority-row">
                                    <span><i class="bi bi-lightning-charge" aria-hidden="true"></i></span>
                                    <div>
                                        <strong>{{ $request->title }}</strong>
                                        <small>{{ $request->reference }} &middot; {{ $request->equipment?->name ?? '-' }} &middot; {{ $request->due_at?->format('d/m/Y') ?? '-' }}</small>
                                    </div>
                                    <span class="status-pill gmao-status-priority-urgent">{{ __('main.priority_urgent') }}</span>
                                </div>
                            @empty
                                <div class="module-empty-state module-empty-state-small"><i class="bi bi-shield-check"></i><strong>{{ __('main.gmao_no_urgent_requests') }}</strong></div>
                            @endforelse
                        </div>
                    </article>

                    <article class="hr-panel">
                        <div class="hr-panel-header">
                            <div><span>{{ __('main.gmao_operations') }}</span><h3>{{ __('main.gmao_recent_work_orders') }}</h3></div>
                            <a href="{{ route('main.gmao.work-orders', [$company, $site]) }}"><i class="bi bi-arrow-right"></i></a>
                        </div>
                        <div class="hr-activity-list">
                            @forelse ($dashboard['recentOrders'] as $order)
                                <div class="hr-activity-row">
                                    <span><i class="bi bi-clipboard2-check" aria-hidden="true"></i></span>
                                    <div>
                                        <strong>{{ $order->title }}</strong>
                                        <small>{{ $order->reference }} &middot; {{ $order->technician?->name ?? '-' }}</small>
                                    </div>
                                    <span class="status-pill gmao-status-{{ $order->status }}">{{ $orderStatusLabels[$order->status] ?? $order->status }}</span>
                                </div>
                            @empty
                                <div class="module-empty-state module-empty-state-small"><i class="bi bi-clipboard-plus"></i><strong>{{ __('main.gmao_no_records') }}</strong></div>
                            @endforelse
                        </div>
                    </article>

                    <article class="hr-panel">
                        <div class="hr-panel-header">
                            <div><span>{{ __('main.gmao_preventive') }}</span><h3>{{ __('main.gmao_due_preventive') }}</h3></div>
                            <a href="{{ route('main.gmao.preventive', [$company, $site]) }}"><i class="bi bi-arrow-right"></i></a>
                        </div>
                        <div class="hr-activity-list">
                            @forelse ($dashboard['duePlans'] as $plan)
                                <div class="hr-activity-row">
                                    <span><i class="bi bi-calendar2-week" aria-hidden="true"></i></span>
                                    <div>
                                        <strong>{{ $plan->title }}</strong>
                                        <small>{{ $plan->equipment?->name ?? '-' }} &middot; {{ $plan->next_due_at?->format('d/m/Y') ?? '-' }}</small>
                                    </div>
                                </div>
                            @empty
                                <div class="module-empty-state module-empty-state-small"><i class="bi bi-calendar-check"></i><strong>{{ __('main.gmao_no_due_preventive') }}</strong></div>
                            @endforelse
                        </div>
                    </article>

                    <article class="hr-panel">
                        <div class="hr-panel-header">
                            <div><span>{{ __('main.gmao_inventory') }}</span><h3>{{ __('main.gmao_low_stock') }}</h3></div>
                            <a href="{{ route('main.gmao.spare-parts', [$company, $site]) }}"><i class="bi bi-arrow-right"></i></a>
                        </div>
                        <div class="hr-activity-list">
                            @forelse ($dashboard['lowParts'] as $part)
                                <div class="hr-activity-row">
                                    <span><i class="bi bi-nut" aria-hidden="true"></i></span>
                                    <div>
                                        <strong>{{ $part->name }}</strong>
                                        <small>{{ $part->reference }} &middot; {{ number_format((float) $part->stock_quantity, 2, ',', ' ') }} {{ $part->unit }}</small>
                                    </div>
                                </div>
                            @empty
                                <div class="module-empty-state module-empty-state-small"><i class="bi bi-box-seam"></i><strong>{{ __('main.gmao_stock_ok') }}</strong></div>
                            @endforelse
                        </div>
                    </article>

                    <article class="hr-panel">
                        <div class="hr-panel-header">
                            <div><span>{{ __('main.gmao_technicians') }}</span><h3>{{ __('main.gmao_technician_load') }}</h3></div>
                            <a href="{{ route('main.gmao.technicians', [$company, $site]) }}"><i class="bi bi-arrow-right"></i></a>
                        </div>
                        <div class="gmao-load-list">
                            @forelse ($dashboard['workloadRows'] as $row)
                                <div class="gmao-load-row">
                                    <div>
                                        <strong>{{ $row['technician'] }}</strong>
                                        <small>{{ $row['hours'] }} h</small>
                                    </div>
                                    <span>{{ $row['count'] }}</span>
                                </div>
                            @empty
                                <div class="module-empty-state module-empty-state-small"><i class="bi bi-person-check"></i><strong>{{ __('main.gmao_no_technician_load') }}</strong></div>
                            @endforelse
                        </div>
                    </article>

                    <article class="hr-panel">
                        <div class="hr-panel-header">
                            <div><span>{{ __('main.gmao_location_coverage') }}</span><h3>{{ __('main.gmao_equipment_by_location') }}</h3></div>
                            <a href="{{ route('main.gmao.locations', [$company, $site]) }}"><i class="bi bi-arrow-right"></i></a>
                        </div>
                        <div class="gmao-load-list">
                            @forelse ($dashboard['locationRows'] as $row)
                                <div class="gmao-load-row">
                                    <div>
                                        <strong>{{ $row['location'] }}</strong>
                                        <small>{{ __('main.gmao_critical_value', ['value' => $row['critical']]) }}</small>
                                    </div>
                                    <span>{{ $row['count'] }}</span>
                                </div>
                            @empty
                                <div class="module-empty-state module-empty-state-small"><i class="bi bi-geo-alt"></i><strong>{{ __('main.gmao_no_records') }}</strong></div>
                            @endforelse
                        </div>
                    </article>

                    <article class="hr-panel">
                        <div class="hr-panel-header">
                            <div><span>{{ __('main.gmao_traceability') }}</span><h3>{{ __('main.gmao_recent_activity') }}</h3></div>
                            <a href="{{ route('main.gmao.traceability', [$company, $site]) }}"><i class="bi bi-arrow-right"></i></a>
                        </div>
                        <div class="hr-activity-list">
                            @forelse ($dashboard['activities'] as $activity)
                                <div class="hr-activity-row">
                                    <span><i class="bi bi-activity" aria-hidden="true"></i></span>
                                    <div>
                                        <strong>{{ $activity->title }}</strong>
                                        <small>{{ $activity->reference }} &middot; {{ $activity->created_at?->format('d/m/Y H:i') }}</small>
                                    </div>
                                </div>
                            @empty
                                <div class="module-empty-state module-empty-state-small"><i class="bi bi-clock-history"></i><strong>{{ __('main.no_notifications') }}</strong></div>
                            @endforelse
                        </div>
                    </article>
                </section>
            </section>
        </main>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const chartNode = document.getElementById('gmaoControlChart');
            const dataNode = document.getElementById('gmaoControlChartData');

            if (!chartNode || !dataNode || !window.ApexCharts) {
                return;
            }

            const payload = JSON.parse(dataNode.textContent || '{}');
            const series = (payload.series || []).map((value) => Number(value) || 0);
            const labels = payload.labels || [];

            const getTheme = () => {
                const shell = document.querySelector('.dashboard-shell');
                return shell?.dataset.theme === 'dark' || document.documentElement.dataset.theme === 'dark';
            };

            const chart = new ApexCharts(chartNode, {
                chart: {
                    type: 'radialBar',
                    height: 236,
                    toolbar: { show: false },
                    sparkline: { enabled: false },
                    fontFamily: 'inherit',
                },
                series,
                labels,
                colors: ['#0ea5e9', '#2563eb', '#10b981'],
                stroke: {
                    lineCap: 'round',
                },
                plotOptions: {
                    radialBar: {
                        startAngle: -125,
                        endAngle: 125,
                        hollow: {
                            size: '52%',
                            background: 'transparent',
                        },
                        track: {
                            background: getTheme() ? '#203450' : '#e8eef7',
                            strokeWidth: '86%',
                        },
                        dataLabels: {
                            name: {
                                show: false,
                            },
                            value: {
                                show: false,
                            },
                            total: {
                                show: false,
                            },
                        },
                    },
                },
                grid: {
                    padding: {
                        top: -8,
                        right: -6,
                        bottom: -20,
                        left: -6,
                    },
                },
                tooltip: {
                    enabled: true,
                    y: {
                        formatter: (value) => `${Math.round(value)}%`,
                    },
                },
                legend: {
                    show: false,
                },
            });

            chart.render();
        });
    </script>
</body>
</html>
