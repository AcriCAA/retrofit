<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>RetroFit</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white antialiased">

    <div class="min-h-screen flex flex-col items-center justify-center px-6">

        {{-- Logo + wordmark --}}
        <div class="flex flex-col items-center gap-4">
            <x-application-logo class="w-40 h-40 text-gray-900" />
            <span class="text-4xl font-bold tracking-tight text-gray-900" style="font-family: 'Instrument Sans', sans-serif;">
                RetroFit
            </span>
            <p class="text-gray-500 text-sm mt-1">Find discontinued fashion on secondhand marketplaces.</p>
        </div>

        {{-- Auth links --}}
        <div class="mt-10 flex items-center gap-4">
            @auth
                <a href="{{ url('/dashboard') }}"
                   class="px-6 py-2.5 bg-gray-900 text-white text-sm font-semibold rounded-lg hover:bg-gray-700 transition">
                    Dashboard
                </a>
            @else
                <a href="{{ route('login') }}"
                   class="px-6 py-2.5 border border-gray-300 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 transition">
                    Log in
                </a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}"
                       class="px-6 py-2.5 bg-gray-900 text-white text-sm font-semibold rounded-lg hover:bg-gray-700 transition">
                        Get started
                    </a>
                @endif
            @endauth
        </div>

    </div>

</body>
</html>
