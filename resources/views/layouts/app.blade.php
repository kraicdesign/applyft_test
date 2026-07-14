<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b1220">
    <title>@yield('title', 'FileDrop') · {{ config('app.name', 'File Retention') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="ambient ambient-one"></div>
    <div class="ambient ambient-two"></div>

    <nav class="navbar navbar-expand-md app-navbar">
        <div class="container app-container">
            <a class="navbar-brand brand" href="{{ route('files.upload') }}" aria-label="FileDrop upload">
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M12 3v12m0-12 4.5 4.5M12 3 7.5 7.5M5 14v4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <span>File<span>Drop</span></span>
            </a>

            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#appNavigation" aria-controls="appNavigation" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="appNavigation">
                <div class="navbar-nav ms-auto align-items-md-center gap-md-1">
                    <a class="nav-link {{ request()->routeIs('files.upload') ? 'active' : '' }}" href="{{ route('files.upload') }}">Upload</a>
                    <a class="nav-link {{ request()->routeIs('files.index') ? 'active' : '' }}" href="{{ route('files.index') }}">Manage files</a>
                    <span class="retention-pill ms-md-3"><span></span> 24-hour retention</span>
                </div>
            </div>
        </div>
    </nav>

    <main class="app-main">
        @yield('content')
    </main>

    <footer class="app-footer">
        <div class="container app-container d-flex flex-column flex-sm-row justify-content-between gap-2">
            <span>Private document storage with automatic expiry.</span>
            <span>PDF &amp; DOCX · 10 MB maximum</span>
        </div>
    </footer>

    @stack('modals')
    @stack('scripts')
</body>
</html>
