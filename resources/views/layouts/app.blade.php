<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Mailer'))</title>

    {{--
        Self-contained admin chrome. The package ships markup using Bootstrap 4
        classes + Material Icons; these CDN assets keep the admin UI working out
        of the box. Publish `mailer.views` and override this layout to integrate
        with the host app's own styling.
    --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">

    @stack('styles')
</head>
<body>
    <main class="py-4">
        @yield('content')
    </main>

    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

    @stack('scripts')
</body>
</html>
