<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" class="accounting-module-root">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('main.archive_records') }} | {{ app_brand_name() }}</title>
    <link rel="icon" href="{{ app_brand_favicon_url() }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <style>{!! file_get_contents(resource_path('css/admin/dashboard.css')) !!}</style>
    <style>{!! file_get_contents(resource_path('css/main.css')) !!}</style>
</head>
<body class="accounting-module-body archiving-module-body">
    <div class="dashboard-shell main-shell accounting-shell archiving-shell" data-theme="light">
        @include('main.modules.archiving.partials.sidebar', ['activeArchivingPage' => 'records'])

        <main class="dashboard-main">
            <header class="dashboard-topbar">
                <div>
                    <h1>{{ __('main.archive_records') }}</h1>
                    <p>{{ $company->name }} / {{ $site->name }}</p>
                </div>
                @include('main.modules.partials.accounting-header-actions')
            </header>

            <section class="dashboard-content accounting-list-page archiving-page">
                <a class="back-link" href="{{ route('main.archiving.dashboard', [$company, $site]) }}">
                    <i class="bi bi-arrow-left"></i>
                    {{ __('main.archive_dashboard') }}
                </a>

                <section class="page-heading">
                    <div>
                        <h1>{{ __('main.archive_records') }}</h1>
                        <p>{{ __('main.archive_records_subtitle') }}</p>
                    </div>
                    <button class="primary-action" type="button" data-bs-toggle="modal" data-bs-target="#recordModal">
                        <i class="bi bi-plus-lg"></i>
                        {{ __('main.archive_new_record') }}
                    </button>
                </section>

                @if (session('success'))
                    <div class="flash-toast" role="status" data-autohide="15000">
                        <span class="flash-icon"><i class="bi bi-check2-circle"></i></span>
                        <span>{{ session('success') }}</span>
                        <button type="button" class="flash-close" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg"></i></button>
                        <span class="flash-progress"></span>
                    </div>
                @endif

                <section class="table-tools">
                    <form class="search-box" method="GET">
                        <i class="bi bi-search"></i>
                        <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('main.archive_search_record') }}">
                    </form>
                    <span><strong>{{ $records->count() }}</strong> / <strong>{{ $records->total() }}</strong> {{ __('main.rows') }}</span>
                </section>

                <section class="archive-document-grid" aria-label="{{ __('main.archive_records') }}">
                    @forelse ($records as $record)
                        @php
                            $recordBox = $record->box ?? $record->container?->box;
                            $fileUrl = $record->file_path ? public_storage_url($record->file_path) : null;
                            $extension = strtolower(pathinfo($record->file_path ?? '', PATHINFO_EXTENSION));
                            $previewType = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) ? 'image' : ($extension === 'pdf' ? 'pdf' : 'file');
                        @endphp

                        <article class="archive-document-card"
                            data-record-update-url="{{ route('main.archiving.records.update', [$company, $site, $record]) }}"
                            data-record-attach-url="{{ route('main.archiving.records.file.attach', [$company, $site, $record]) }}"
                            data-record-replace-url="{{ route('main.archiving.records.file.replace', [$company, $site, $record]) }}"
                            data-record-title="{{ $record->title }}"
                            data-record-document-type="{{ $record->document_type }}"
                            data-record-category="{{ $record->category }}"
                            data-record-owner-service="{{ $record->owner_service }}"
                            data-record-document-date="{{ $record->document_date?->toDateString() }}"
                            data-record-retention-until="{{ $record->retention_until?->toDateString() }}"
                            data-record-confidentiality-level="{{ $record->confidentiality_level }}"
                            data-record-status="{{ $record->status }}"
                            data-record-description="{{ $record->description }}"
                        >
                            <header>
                                <span class="archive-document-spine" aria-hidden="true"></span>
                                <span class="archive-document-icon"><i class="bi bi-file-earmark-text" aria-hidden="true"></i></span>
                                <div class="archive-document-heading">
                                    <span class="archive-document-reference">{{ $record->reference }}</span>
                                    <strong>{{ $record->title }}</strong>
                                    <small>{{ $record->document_type ?: __('main.document') }} &middot; {{ $record->category ?: '-' }}</small>
                                </div>
                                <span class="status-pill archive-status-{{ $record->status }}">{{ $recordStatusLabels[$record->status] ?? $record->status }}</span>
                            </header>

                            <dl class="archive-document-meta">
                                <div><dt>{{ __('main.archive_container') }}</dt><dd>{{ $record->container?->title ?? '-' }}</dd></div>
                                <div><dt>{{ __('main.archive_box') }}</dt><dd>{{ $recordBox?->name ?? '-' }}</dd></div>
                                <div><dt>{{ __('main.document_date') }}</dt><dd>{{ $record->document_date?->format('d/m/Y') ?? '-' }}</dd></div>
                                <div><dt>{{ __('main.expiration_date') }}</dt><dd>{{ $record->retention_until?->format('d/m/Y') ?? '-' }}</dd></div>
                            </dl>

                            <div class="archive-document-path">
                                <i class="bi bi-signpost-split" aria-hidden="true"></i>
                                <span>{{ $recordBox?->physical_path ?? '-' }}</span>
                            </div>

                            <footer>
                                <button class="archive-document-action archive-record-edit-trigger" type="button" data-bs-toggle="modal" data-bs-target="#recordEditModal" title="{{ __('admin.edit') }}" aria-label="{{ __('admin.edit') }}">
                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                </button>
                                @if ($fileUrl)
                                    <button class="archive-document-action archive-preview-trigger" type="button" data-bs-toggle="modal" data-bs-target="#recordPreviewModal" data-preview-url="{{ $fileUrl }}" data-preview-type="{{ $previewType }}" data-preview-title="{{ $record->title }}" title="{{ __('main.archive_preview_file') }}" aria-label="{{ __('main.archive_preview_file') }}">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </button>
                                    <button class="archive-document-action archive-record-file-trigger" type="button" data-bs-toggle="modal" data-bs-target="#recordFileModal" data-file-mode="replace" title="{{ __('main.archive_replace_file') }}" aria-label="{{ __('main.archive_replace_file') }}">
                                        <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                                    </button>
                                    <a class="archive-document-action archive-document-action-primary text-decoration-none" href="{{ $fileUrl }}" target="_blank" rel="noopener" title="{{ __('main.archive_open_file') }}" aria-label="{{ __('main.archive_open_file') }}">
                                        <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                                    </a>
                                @else
                                    <span class="archive-document-no-file">
                                        <i class="bi bi-file-earmark-x" aria-hidden="true"></i>
                                        {{ __('main.archive_no_file') }}
                                    </span>
                                    <button class="archive-document-action archive-document-action-primary archive-record-file-trigger" type="button" data-bs-toggle="modal" data-bs-target="#recordFileModal" data-file-mode="attach" title="{{ __('main.archive_attach_file') }}" aria-label="{{ __('main.archive_attach_file') }}">
                                        <i class="bi bi-paperclip" aria-hidden="true"></i>
                                    </button>
                                @endif
                            </footer>
                        </article>
                    @empty
                        <article class="archive-document-empty">{{ __('main.archive_no_records') }}</article>
                    @endforelse
                </section>

                @if ($records->hasPages())
                    <section class="subscriptions-pagination">
                        <span>{{ __('admin.showing') }} <strong>{{ $records->firstItem() }}</strong> {{ __('admin.to') }} <strong>{{ $records->lastItem() }}</strong> {{ __('admin.on') }} <strong>{{ $records->total() }}</strong></span>
                        {{ $records->links() }}
                    </section>
                @endif
            </section>
        </main>
    </div>

    <div class="modal fade subscription-modal accounting-proforma-modal archive-record-modal" id="recordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2><i class="bi bi-file-earmark-text"></i> {{ __('main.archive_new_record') }}</h2>
                    <button type="button" class="modal-close" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
                </div>
                <form method="POST" action="{{ route('main.archiving.records.store', [$company, $site]) }}" enctype="multipart/form-data" class="admin-form">
                    @csrf
                    <div class="modal-body">
                        <div class="modal-fields two-columns">
                            <label>{{ __('main.archive_container') }}<select class="form-select" name="archive_container_id"><option value="">{{ __('main.none') }}</option>@foreach ($containerOptions as $option)<option value="{{ $option->id }}">{{ $option->reference }} - {{ $option->title }}</option>@endforeach</select></label>
                            <label>{{ __('main.archive_box') }}<select class="form-select" name="archive_box_id"><option value="">{{ __('main.none') }}</option>@foreach ($boxOptions as $option)<option value="{{ $option->id }}">{{ $option->physical_path }}</option>@endforeach</select></label>
                            <label>{{ __('main.archive_title_label') }} *<input class="form-control" name="title" required placeholder="{{ __('main.archive_record_title_placeholder') }}"></label>
                            <label>{{ __('main.document_type') }}<input class="form-control" name="document_type" placeholder="{{ __('main.archive_document_type_placeholder') }}"></label>
                            <label>{{ __('main.category') }}<input class="form-control" name="category" placeholder="{{ __('main.archive_category_placeholder') }}"></label>
                            <label>{{ __('main.owner_service') }}<input class="form-control" name="owner_service" placeholder="{{ __('main.archive_service_placeholder') }}"></label>
                            <label>{{ __('main.document_date') }}<input class="form-control" type="date" name="document_date"></label>
                            <label>{{ __('main.archived_at') }}<input class="form-control" type="date" name="archived_at" value="{{ now()->toDateString() }}"></label>
                            <label>{{ __('main.expiration_date') }}<input class="form-control" type="date" name="retention_until"></label>
                            <label>{{ __('main.confidentiality') }} *<select class="form-select" name="confidentiality_level" required>@foreach ($confidentialityLabels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                            <label>{{ __('main.status') }} *<select class="form-select" name="status" required>@foreach ($recordStatusLabels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                            <label>{{ __('main.file') }}<input class="form-control" type="file" name="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.jpg,.jpeg,.png"><span class="field-help">{{ __('main.archive_file_format_help') }}</span></label>
                        </div>
                        <label>{{ __('main.description') }}<textarea class="form-control" name="description" rows="3"></textarea></label>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button class="modal-submit" type="submit">{{ __('main.create') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade subscription-modal accounting-proforma-modal archive-record-modal" id="recordEditModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2><i class="bi bi-pencil-square"></i> {{ __('main.archive_edit_record') }}</h2>
                    <button type="button" class="modal-close" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
                </div>
                <form method="POST" action="#" class="admin-form" data-record-edit-form>
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="modal-fields two-columns">
                            <label>{{ __('main.archive_title_label') }} *<input class="form-control" name="title" required></label>
                            <label>{{ __('main.document_type') }}<input class="form-control" name="document_type" placeholder="{{ __('main.archive_document_type_placeholder') }}"></label>
                            <label>{{ __('main.category') }}<input class="form-control" name="category" placeholder="{{ __('main.archive_category_placeholder') }}"></label>
                            <label>{{ __('main.owner_service') }}<input class="form-control" name="owner_service" placeholder="{{ __('main.archive_service_placeholder') }}"></label>
                            <label>{{ __('main.document_date') }}<input class="form-control" type="date" name="document_date"></label>
                            <label>{{ __('main.expiration_date') }}<input class="form-control" type="date" name="retention_until"></label>
                            <label>{{ __('main.confidentiality') }} *<select class="form-select" name="confidentiality_level" required>@foreach ($confidentialityLabels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                            <label>{{ __('main.status') }} *<select class="form-select" name="status" required>@foreach ($recordStatusLabels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                        </div>
                        <label>{{ __('main.description') }}<textarea class="form-control" name="description" rows="3"></textarea></label>
                        <p class="field-help">{{ __('main.archive_record_edit_location_help') }}</p>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button class="modal-submit" type="submit">{{ __('main.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade subscription-modal accounting-proforma-modal archive-record-modal" id="recordFileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 data-file-modal-title><i class="bi bi-paperclip"></i> {{ __('main.archive_attach_file') }}</h2>
                    <button type="button" class="modal-close" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
                </div>
                <form method="POST" action="#" enctype="multipart/form-data" class="admin-form" data-record-file-form>
                    @csrf
                    <div class="modal-body">
                        <label>{{ __('main.file') }} *<input class="form-control" type="file" name="file" required accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.jpg,.jpeg,.png"><span class="field-help">{{ __('main.archive_file_format_help') }}</span></label>
                        <label data-replacement-reason-wrap hidden>{{ __('main.archive_replacement_reason') }} *<textarea class="form-control" name="replacement_reason" rows="3" placeholder="{{ __('main.archive_replacement_reason_placeholder') }}"></textarea></label>
                        <div class="archive-file-advice">
                            <i class="bi bi-info-circle" aria-hidden="true"></i>
                            <span>{{ __('main.archive_pdf_recommended') }}</span>
                        </div>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
                        <button class="modal-submit" type="submit" data-file-submit-label>{{ __('main.archive_attach_file') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade subscription-modal accounting-proforma-modal archive-file-preview-modal" id="recordPreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="recordPreviewTitle"><i class="bi bi-eye"></i> {{ __('main.archive_preview_file') }}</h2>
                    <button type="button" class="modal-close" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="archive-file-preview" data-preview-container></div>
                <div class="modal-actions">
                    <a class="primary-action text-decoration-none" href="#" target="_blank" rel="noopener" data-preview-open>
                        <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                        {{ __('main.archive_open_file') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>{!! file_get_contents(resource_path('js/main.js')) !!}</script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const previewModal = document.getElementById('recordPreviewModal');
        const previewTitle = document.getElementById('recordPreviewTitle');
        const previewContainer = previewModal?.querySelector('[data-preview-container]');
        const previewOpen = previewModal?.querySelector('[data-preview-open]');
        const editModal = document.getElementById('recordEditModal');
        const editForm = editModal?.querySelector('[data-record-edit-form]');
        const fileModal = document.getElementById('recordFileModal');
        const fileForm = fileModal?.querySelector('[data-record-file-form]');
        const fileModalTitle = fileModal?.querySelector('[data-file-modal-title]');
        const fileSubmitLabel = fileModal?.querySelector('[data-file-submit-label]');
        const replacementReasonWrap = fileModal?.querySelector('[data-replacement-reason-wrap]');

        const defaultPreviewTitle = @json(__('main.archive_preview_file'));
        const previewUnavailableText = @json(__('main.archive_preview_unavailable'));
        const attachFileTitle = @json(__('main.archive_attach_file'));
        const replaceFileTitle = @json(__('main.archive_replace_file'));

        const setPreviewTitle = (title) => {
            if (! previewTitle) return;

            previewTitle.replaceChildren();

            const icon = document.createElement('i');
            icon.className = 'bi bi-eye';
            icon.setAttribute('aria-hidden', 'true');

            previewTitle.append(icon, document.createTextNode(` ${title}`));
        };

        const createPreviewFallback = (title) => {
            const fallback = document.createElement('div');
            fallback.className = 'archive-preview-fallback';

            const icon = document.createElement('i');
            icon.className = 'bi bi-file-earmark';
            icon.setAttribute('aria-hidden', 'true');

            const name = document.createElement('strong');
            name.textContent = title;

            const help = document.createElement('span');
            help.textContent = previewUnavailableText;

            fallback.append(icon, name, help);

            return fallback;
        };

        editModal?.addEventListener('show.bs.modal', (event) => {
            const card = event.relatedTarget?.closest('.archive-document-card');
            if (! card || ! editForm) return;

            editForm.action = card.dataset.recordUpdateUrl || '#';

            Object.entries({
                title: card.dataset.recordTitle || '',
                document_type: card.dataset.recordDocumentType || '',
                category: card.dataset.recordCategory || '',
                owner_service: card.dataset.recordOwnerService || '',
                document_date: card.dataset.recordDocumentDate || '',
                retention_until: card.dataset.recordRetentionUntil || '',
                confidentiality_level: card.dataset.recordConfidentialityLevel || '',
                status: card.dataset.recordStatus || '',
                description: card.dataset.recordDescription || '',
            }).forEach(([name, value]) => {
                const field = editForm.elements[name];
                if (field) field.value = value;
            });
        });

        fileModal?.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            const card = trigger?.closest('.archive-document-card');
            const mode = trigger?.dataset.fileMode || 'attach';
            const isReplace = mode === 'replace';

            if (! card || ! fileForm) return;

            fileForm.action = isReplace ? (card.dataset.recordReplaceUrl || '#') : (card.dataset.recordAttachUrl || '#');
            fileForm.reset();

            if (fileModalTitle) {
                fileModalTitle.replaceChildren();
                const icon = document.createElement('i');
                icon.className = isReplace ? 'bi bi-arrow-repeat' : 'bi bi-paperclip';
                icon.setAttribute('aria-hidden', 'true');
                fileModalTitle.append(icon, document.createTextNode(` ${isReplace ? replaceFileTitle : attachFileTitle}`));
            }

            if (fileSubmitLabel) {
                fileSubmitLabel.textContent = isReplace ? replaceFileTitle : attachFileTitle;
            }

            if (replacementReasonWrap) {
                replacementReasonWrap.hidden = ! isReplace;
                const reason = replacementReasonWrap.querySelector('[name="replacement_reason"]');
                if (reason) reason.required = isReplace;
            }
        });

        previewModal?.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            const url = trigger?.dataset.previewUrl || '';
            const type = trigger?.dataset.previewType || 'file';
            const title = trigger?.dataset.previewTitle || defaultPreviewTitle;

            setPreviewTitle(title);
            if (previewOpen) previewOpen.href = url;
            if (! previewContainer) return;

            previewContainer.replaceChildren();

            if (type === 'image') {
                const image = document.createElement('img');
                image.src = url;
                image.alt = title;
                previewContainer.append(image);
                return;
            }

            if (type === 'pdf') {
                const frame = document.createElement('iframe');
                frame.src = url;
                frame.title = title;
                previewContainer.append(frame);
                return;
            }

            previewContainer.append(createPreviewFallback(title));
        });

        previewModal?.addEventListener('hidden.bs.modal', () => {
            if (previewContainer) previewContainer.replaceChildren();
        });
    });
    </script>
</body>
</html>
