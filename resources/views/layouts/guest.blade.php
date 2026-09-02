<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div class="mb-2">
                <a href="/" class="flex flex-col items-center gap-2 group">
                    <div class="h-12 w-12 rounded-xl bg-[#03193c] flex items-center justify-center text-white font-bold text-2xl shadow-md group-hover:bg-[#1b2e52] transition-colors">
                        T
                    </div>
                    <div class="text-center">
                        <span class="text-2xl font-bold text-[#03193c] tracking-tight font-sans block">TradePro</span>
                        <span class="text-xs text-slate-500 font-medium">Trading & Distribution ERP</span>
                    </div>
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
