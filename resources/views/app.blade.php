<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', '8ohm.co.za') }}</title>
        <meta name="description" content="E-Commerce SaaS Platform for premium footwear. Shop the latest brands and exclusive collections.">
        <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}" sizes="any">
        <link rel="icon" href="{{ asset('assets/images/favicon.svg') }}" type="image/svg+xml">
        <link rel="apple-touch-icon" href="{{ asset('assets/images/apple-touch-icon.png') }}">
        <link rel="manifest" href="{{ asset('assets/images/manifest.json') }}">

            <!-- Open Graph / Facebook -->
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:title" content="{{ config('app.name', '8ohm.co.za') }}">
        <meta property="og:description" content="E-Commerce SaaS Platform for premium footwear.">
        <meta property="og:image" content="{{ asset('assets/images/8OHM_Logo.webp') }}">

        <!-- Twitter -->
        <meta property="twitter:card" content="summary_large_image">
        <meta property="twitter:url" content="{{ url()->current() }}">
        <meta property="twitter:title" content="{{ config('app.name', '8ohm.co.za') }}">
        <meta property="twitter:description" content="E-Commerce SaaS Platform for premium footwear.">
        <meta property="twitter:image" content="{{ asset('assets/images/8OHM_Logo.webp') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/phosphor-icons@1.4.2/src/css/icons.min.css" rel="stylesheet">

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
<script src="https://cdn.jsdelivr.net/npm/phosphor-icons@1.4.2/src/index.min.js"></script>
