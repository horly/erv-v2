@php
    $useOld = old('modal_target') === $formTarget;
    $value = fn (string $key, $default = null) => $useOld ? old($key, $default) : $default;
    $selectedSource = $value('source_key', $task && $task->source_type && $task->source_id ? $task->source_type.':'.$task->source_id : '');
    $fieldError = fn (string $key) => $useOld && $errors->has($key);
@endphp
<div class="modal-body">
    <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"><i class="bi bi-x-lg"></i></button>
    <h2><i class="bi bi-check2-square"></i>{{ $task ? __('main.edit_task') : __('main.new_task') }}</h2>
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label">{{ __('main.task_title') }} *</label>
            <input name="title" class="form-control {{ $fieldError('title') ? 'is-invalid' : '' }}" value="{{ $value('title', $task?->title) }}" placeholder="{{ __('main.task_title') }}" required>
            @if ($fieldError('title'))<div class="invalid-feedback d-block">{{ $errors->first('title') }}</div>@endif
        </div>
        <div class="col-md-6">
            <label class="form-label">{{ __('main.task_type') }} *</label>
            <select name="type" class="form-select {{ $fieldError('type') ? 'is-invalid' : '' }}" required>
                @foreach ($typeLabels as $key => $label)
                    <option value="{{ $key }}" @selected($value('type', $task?->type ?? \App\Models\AccountingTask::TYPE_CALL) === $key)>{{ $label }}</option>
                @endforeach
            </select>
            @if ($fieldError('type'))<div class="invalid-feedback d-block">{{ $errors->first('type') }}</div>@endif
        </div>
        <div class="col-md-6">
            <label class="form-label">{{ __('main.priority') }} *</label>
            <select name="priority" class="form-select {{ $fieldError('priority') ? 'is-invalid' : '' }}" required>
                @foreach ($priorityLabels as $key => $label)
                    <option value="{{ $key }}" @selected($value('priority', $task?->priority ?? \App\Models\AccountingTask::PRIORITY_NORMAL) === $key)>{{ $label }}</option>
                @endforeach
            </select>
            @if ($fieldError('priority'))<div class="invalid-feedback d-block">{{ $errors->first('priority') }}</div>@endif
        </div>
        <div class="col-md-6">
            <label class="form-label">{{ __('main.task_assignee') }}</label>
            <select name="assigned_to" class="form-select {{ $fieldError('assigned_to') ? 'is-invalid' : '' }}">
                <option value="">{{ __('main.unassigned') }}</option>
                @foreach ($assignees as $assignee)
                    <option value="{{ $assignee->id }}" @selected((string) $value('assigned_to', $task?->assigned_to) === (string) $assignee->id)>{{ $assignee->name }} - {{ strtoupper($assignee->role) }}</option>
                @endforeach
            </select>
            @if ($fieldError('assigned_to'))<div class="invalid-feedback d-block">{{ $errors->first('assigned_to') }}</div>@endif
        </div>
        <div class="col-md-6">
            <label class="form-label">{{ __('main.task_due_date') }}</label>
            <input type="date" name="due_date" class="form-control {{ $fieldError('due_date') ? 'is-invalid' : '' }}" value="{{ $value('due_date', $task?->due_date?->format('Y-m-d')) }}">
            @if ($fieldError('due_date'))<div class="invalid-feedback d-block">{{ $errors->first('due_date') }}</div>@endif
        </div>
        <div class="col-md-4">
            <label class="form-label">{{ __('main.status') }} *</label>
            <select name="status" class="form-select {{ $fieldError('status') ? 'is-invalid' : '' }}" required>
                @foreach ($statusLabels as $key => $label)
                    <option value="{{ $key }}" @selected($value('status', $task?->status ?? \App\Models\AccountingTask::STATUS_TODO) === $key)>{{ $label }}</option>
                @endforeach
            </select>
            @if ($fieldError('status'))<div class="invalid-feedback d-block">{{ $errors->first('status') }}</div>@endif
        </div>
        <div class="col-md-4">
            <label class="form-label">{{ __('main.customer') }}</label>
            <select name="client_id" class="form-select {{ $fieldError('client_id') ? 'is-invalid' : '' }}">
                <option value="">-</option>
                @foreach ($clients as $client)
                    <option value="{{ $client->id }}" @selected((string) $value('client_id', $task?->client_id) === (string) $client->id)>{{ $client->name }}</option>
                @endforeach
            </select>
            @if ($fieldError('client_id'))<div class="invalid-feedback d-block">{{ $errors->first('client_id') }}</div>@endif
        </div>
        <div class="col-md-4">
            <label class="form-label">{{ __('main.supplier') }}</label>
            <select name="supplier_id" class="form-select {{ $fieldError('supplier_id') ? 'is-invalid' : '' }}">
                <option value="">-</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected((string) $value('supplier_id', $task?->supplier_id) === (string) $supplier->id)>{{ $supplier->name }}</option>
                @endforeach
            </select>
            @if ($fieldError('supplier_id'))<div class="invalid-feedback d-block">{{ $errors->first('supplier_id') }}</div>@endif
        </div>
        <div class="col-12">
            <label class="form-label">{{ __('main.task_related_document') }}</label>
            <select name="source_key" class="form-select {{ $fieldError('source_key') ? 'is-invalid' : '' }}" @disabled($task?->is_automatic)>
                <option value="">-</option>
                @foreach ($documents as $group => $options)
                    @if ($options !== [])
                        <optgroup label="{{ $group }}">
                            @foreach ($options as $key => $label)
                                <option value="{{ $key }}" @selected($selectedSource === $key)>{{ $label }}</option>
                            @endforeach
                        </optgroup>
                    @endif
                @endforeach
            </select>
            @if ($task?->is_automatic)
                <input type="hidden" name="source_key" value="{{ $selectedSource }}">
            @endif
            @if ($fieldError('source_key'))<div class="invalid-feedback d-block">{{ $errors->first('source_key') }}</div>@endif
        </div>
        <div class="col-12">
            <label class="form-label">{{ __('main.description') }}</label>
            <textarea name="description" rows="3" class="form-control {{ $fieldError('description') ? 'is-invalid' : '' }}" placeholder="{{ __('main.task_description') }}">{{ $value('description', $task?->description) }}</textarea>
            @if ($fieldError('description'))<div class="invalid-feedback d-block">{{ $errors->first('description') }}</div>@endif
        </div>
        <div class="col-12">
            <label class="form-label">{{ __('main.task_completion_notes') }}</label>
            <textarea name="completion_notes" rows="2" class="form-control {{ $fieldError('completion_notes') ? 'is-invalid' : '' }}" placeholder="{{ __('main.task_completion_notes') }}">{{ $value('completion_notes', $task?->completion_notes) }}</textarea>
            @if ($fieldError('completion_notes'))<div class="invalid-feedback d-block">{{ $errors->first('completion_notes') }}</div>@endif
        </div>
    </div>
    <div class="modal-actions">
        <button type="button" class="modal-cancel" data-bs-dismiss="modal">{{ __('admin.cancel') }}</button>
        <button class="modal-submit" type="submit">{{ $task ? __('admin.update') : __('admin.create') }}</button>
    </div>
</div>
