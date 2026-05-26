<header class="dashboard-topbar">
    <div>
        <h1>{{ $title }}</h1>
        <p>{{ $company->name }} / {{ $site->name }}</p>
    </div>

    @include('main.modules.partials.accounting-header-actions')
</header>
