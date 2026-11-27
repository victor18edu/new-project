<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('components.loading-overlay-head')
    <title>@yield('title', 'Frontend Laravel')</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary" data-loading-root>
        <div class="container">
            <a class="navbar-brand" href="/">Frontend App</a>
        </div>
    </nav>
    <main class="container py-4" data-loading-root>
        @yield('content')
    </main>

    <x-loading-overlay />
</body>
</html>