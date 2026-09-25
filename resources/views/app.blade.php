<!DOCTYPE html>
@php($seo = \App\Support\NexxworthSeo::current())
<html lang="{{ $seo['locale'] ?? str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <meta name="application-name" content="{{ config('branding.name') }}">
        <link rel="icon" href="{{ config('branding.favicon_url') }}">
        @if ($seo)
            <title>{{ $seo['title'] }}</title>
            <meta name="description" content="{{ $seo['description'] }}">
            <link rel="canonical" href="{{ $seo['canonical'] }}">
            <link rel="alternate" hreflang="it" href="{{ $seo['alternate_it'] }}">
            <link rel="alternate" hreflang="en" href="{{ $seo['alternate_en'] }}">
            <link rel="alternate" hreflang="x-default" href="{{ $seo['alternate_it'] }}">
            <meta property="og:type" content="website">
            <meta property="og:site_name" content="Nexxworth Consulting">
            <meta property="og:locale" content="{{ $seo['locale'] === 'en' ? 'en_US' : 'it_IT' }}">
            <meta property="og:title" content="{{ $seo['title'] }}">
            <meta property="og:description" content="{{ $seo['description'] }}">
            <meta property="og:url" content="{{ $seo['canonical'] }}">
            <meta property="og:image" content="{{ url(config('branding.logo_url')) }}">
            <meta name="twitter:card" content="summary">
            <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'ProfessionalService', 'name' => 'Nexxworth Consulting', 'url' => route('home'), 'image' => url(config('branding.logo_url')), 'telephone' => '+39 366 430 0443', 'email' => 'ksd.servizi@gmail.com', 'address' => ['@type' => 'PostalAddress', 'streetAddress' => 'Via Cremona 29A/int. 3', 'addressLocality' => 'Mantova', 'postalCode' => '46100', 'addressCountry' => 'IT']], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
        @else
            <title inertia>{{ config('branding.name') }}</title>
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Dark mode anti-FOUC -->
        <script>
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark')
            } else {
                document.documentElement.classList.remove('dark')
            }
        </script>

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
