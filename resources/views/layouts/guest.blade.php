<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'FlashSuite Pro') }}</title>

        <!-- Favicon / Logo Tab Browser -->
        <link rel="icon" type="image/svg+xml" href="{{ asset('assets/favicon.svg') }}?v=6">
        <link rel="alternate icon" type="image/svg+xml" href="{{ asset('assets/favicon/favicon.svg') }}?v=6">
        <link rel="shortcut icon" href="{{ asset('assets/favicon.svg') }}?v=6">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div class="mb-3">
                <a href="/" class="flex flex-col items-center gap-2 group">
                    <img src="{{ asset('assets/FlashSuitePro - Color.png') }}" alt="FlashSuite Pro" class="h-9 max-w-[220px] w-auto object-contain">
                    <span class="text-xs text-slate-500 font-medium tracking-wide">Trading & Distribution ERP</span>
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
