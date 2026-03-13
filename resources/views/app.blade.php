<!DOCTYPE html>
<html lang="es" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta http-equiv="content-language" content="es-PE">

        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';
                if (appearance === 'system') {
                    if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        <style>
            html { background-color: oklch(1 0 0); }
            html.dark { background-color: oklch(0.145 0 0); }
        </style>

        <title inertia>{{ config('sondeo.seo_site_name') }} — Sondeo ciudadano Perú</title>

        <meta name="description" content="{{ e(config('sondeo.seo_description')) }}">
        <meta name="keywords" content="{{ e(config('sondeo.seo_keywords')) }}">
        <meta name="author" content="Factosys Perú — factosysperu.com">
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
        <meta name="googlebot" content="index, follow">
        <meta name="theme-color" content="#D91023">

        <link rel="canonical" href="{{ rtrim(config('app.url'), '/') }}/">

        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="alternate icon" href="/favicon.ico" sizes="any">

        <meta property="og:type" content="website">
        <meta property="og:locale" content="es_PE">
        <meta property="og:site_name" content="{{ e(config('sondeo.seo_site_name')) }}">
        <meta property="og:title" content="{{ e(config('sondeo.seo_site_name')) }} — Sondeo ciudadano Perú">
        <meta property="og:description" content="{{ e(config('sondeo.seo_description')) }}">
        <meta property="og:url" content="{{ rtrim(config('app.url'), '/') }}/">
        <meta property="og:image" content="{{ rtrim(config('app.url'), '/') }}/favicon.svg">

        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ e(config('sondeo.seo_site_name')) }} — Sondeo ciudadano Perú">
        <meta name="twitter:description" content="{{ e(config('sondeo.seo_description')) }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
