<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.archive_movements') }} | {{ app_brand_name() }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body archiving-module-body">
    <div class="dashboard-shell main-shell accounting-shell archiving-shell" data-theme="light">
        @include('main.modules.archiving.partials.sidebar', ['activeArchivingPage' => 'movements'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.archive_movements') }}</h1>
                    <p>{{ $company->name }} / {{ $site->name }}</p>
                </div>
                @include('main.modules.partials.accounting-header-actions')
            </header>

            <section class="dashboard-content accounting-list-page archiving-page">
                <a class="back-link" href="{{ route('main.archiving.dashboard', [$company, $site]) }}">
                    <i class="bi bi-arrow-left"></i>{{ __('main.archive_dashboard') }}
                </a>

                <section class="page-heading">
                    <div>
                        <h1>{{ __('main.archive_movements') }}</h1>
                        <p>{{ __('main.archive_movements_subtitle') }}</p>
                    </div>
                    <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#movementModal">
                        <i class="bi bi-plus-lg"></i>{{ __('main.archive_new_movement') }}
                    </button>
                </section>

                @if (session('success'))
                    <div class="flash-toast" role="status" data-autohide="15000">
                        <span class="flash-icon"><i class="bi bi-check2-circle"></i></span>
                        <span>{{ session('success') }}</span>
                        <button type="button" class="flash-close"><i class="bi bi-x-lg"></i></button>
                        <span class="flash-progress"></span>
                    </div>
                @endif

                <section class="table-tools">
                    <span></span>
                    <span><strong>{{ $movements->count() }}</strong> / <strong>{{ $movements->total() }}</strong> {{ __('main.rows') }}</span>
                </section>

                <section class="archive-movement-list" aria-label="{{ __('main.archive_movements') }}">
                    @forelse ($movements as $movement)
                        @php
                            $isRecordMovement = $movement->record !== null;
                            $target = $movement->record ?? $movement->container;
                            $targetReference = $movement->record?->reference ?? $movement->container?->reference ?? '-';
                            $targetTitle = $movement->record?->title ?? $movement->container?->title ?? '-';
                            $targetType = $isRecordMovement ? __('main.archive_record') : __('main.archive_container');
                        @endphp

                        <article class="archive-movement-card">
                            <header>
                                <span class="archive-movement-icon {{ $isRecordMovement ? 'is-record' : 'is-container' }}">
                                    <i class="bi {{ $isRecordMovement ? 'bi-file-earmark-text' : 'bi-folder2-open' }}" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <span class="archive-movement-reference">{{ $movement->reference }}</span>
                                    <h2>{{ $targetTitle }}</h2>
                                    <p>{{ $targetType }} &middot; {{ $targetReference }}</p>
                                </div>
                                <time datetime="{{ $movement->moved_at?->toIso8601String() }}">
                                    {{ $movement->moved_at?->format('d/m/Y') ?? '-' }}
                                    <span>{{ $movement->moved_at?->format('H:i') ?? '' }}</span>
                                </time>
                            </header>

                            <div class="archive-movement-route">
                                <div>
                                    <span>{{ __('main.from') }}</span>
                                    <strong>{{ $movement->fromBox?->name ?? __('main.archive_movement_no_source') }}</strong>
                                    <small>{{ $movement->fromBox?->physical_path ?? '-' }}</small>
                                </div>
                                <span class="archive-movement-arrow"><i class="bi bi-arrow-right" aria-hidden="true"></i></span>
                                <div>
                                    <span>{{ __('main.to') }}</span>
                                    <strong>{{ $movement->toBox?->name ?? '-' }}</strong>
                                    <small>{{ $movement->toBox?->physical_path ?? '-' }}</small>
                                </div>
                            </div>

                            <footer>
                                <span><i class="bi bi-card-text" aria-hidden="true"></i>{{ $movement->reason ?: __('main.not_defined') }}</span>
                                <span><i class="bi bi-person" aria-hidden="true"></i>{{ $movement->actor?->name ?? '-' }}</span>
                                @if ($movement->notes)
                                    <p>{{ $movement->notes }}</p>
                                @endif
                            </footer>
                        </article>
                    @empty
                        <article class="archive-movement-empty">
                            <i class="bi bi-arrow-left-right" aria-hidden="true"></i>
                            <strong>{{ __('main.archive_no_movements') }}</strong>
                            <span>{{ __('main.archive_no_movements_text') }}</span>
                        </article>
                    @endforelse
                </section>

                @if ($movements->hasPages())
                    <section class="subscriptions-pagination">
                        <span>{{ __('admin.showing') }} <strong>{{ $movements->firstItem() }}</strong> {{ __('admin.to') }} <strong>{{ $movements->lastItem() }}</strong> {{ __('admin.on') }} <strong>{{ $movements->total() }}</strong></span>
                        {{ $movements->links() }}
                    </section>
                @endif
            </section>
        </main>
    </div>

    <div class="modal fade subscription-modal accounting-proforma-modal archive-movement-modal" id="movementModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2><i class="bi bi-arrow-left-right"></i> {{ __('main.archive_new_movement') }}</h2>
                    <button type="button" class="modal-close" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
                </div>
                <form method="POST" action="{{ route('main.archiving.movements.store', [$company, $site]) }}" class="admin-form">
                    @csrf
                    <div class="modal-body">
                        <div class="modal-fields two-columns">
                            <label>{{ __('main.archive_record') }}
                                <select class="form-select" name="archive_record_id">
                                    <option value="">{{ __('main.none') }}</option>
                                    @foreach ($recordOptions as $option)
                                        <option value="{{ $option->id }}">{{ $option->reference }} - {{ $option->title }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>{{ __('main.archive_container') }}
                                <select class="form-select" name="archive_container_id">
                                    <option value="">{{ __('main.none') }}</option>
                                    @foreach ($containerOptions as $option)
                                        <option value="{{ $option->id }}">{{ $option->reference }} - {{ $option->title }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>{{ __('main.from') }}
                                <select class="form-select" name="from_archive_box_id">
                                    <option value="">{{ __('main.none') }}</option>
                                    @foreach ($boxOptions as $option)
                                        <option value="{{ $option->id }}">{{ $option->physical_path }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>{{ __('main.to') }} *
                                <select class="form-select" name="to_archive_box_id" required>
                                    @foreach ($boxOptions as $option)
                                        <option value="{{ $option->id }}">{{ $option->physical_path }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label>{{ __('main.date') }}<input class="form-control" type="datetime-local" name="moved_at"></label>
                            <label>{{ __('main.reason') }}<input class="form-control" name="reason"></label>
                        </div>
                        <label>{{ __('main.notes') }}<textarea class="form-control" name="notes" rows="3"></textarea></label>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button class="modal-submit" type="submit">{{ __('main.create') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
</body>
</html>
