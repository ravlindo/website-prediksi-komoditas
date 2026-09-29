<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#102e5c">
    <title>@yield('title', config('app.name'))</title>
    <link rel="icon" href="{{ asset(config('branding.favicon')) }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="app-shell">
        @include('partials.sidebar')
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <main class="main-content">
            @include('partials.topbar')
            @yield('content')
        </main>
        @include('partials.toast')
        @include('partials.confirm-dialog')
    </div>
</body>
</html>
