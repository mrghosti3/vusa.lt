<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'VU SA') }}</title>

        <!-- Fonts -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap">

        <!-- Styles -->
        <link rel="stylesheet" href="{{ mix('/css/app.css') }}">

        <!-- Naive UI elements -->
        <meta name="naive-ui-style" />

        <!-- Scripts -->
        @routes
        <script src={{ mix('/js/app.js') }} defer></script>
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
