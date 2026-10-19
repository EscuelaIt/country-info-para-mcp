<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'Countries Info')</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="min-h-screen bg-base-100">
            {{-- Navigation --}}
            <nav class="navbar bg-base-200 shadow-md">
                <div class="flex-1">
                    <a href="{{ route('home') }}" class="btn btn-ghost normal-case text-xl">
                        🌍 Countries Info
                    </a>
                </div>
            </nav>

            {{-- Main Content --}}
            <main class="container mx-auto py-8 px-4">
                @yield('content')
            </main>

            {{-- Footer --}}
            <footer class="bg-base-200 text-base-content mt-12 py-6 border-t">
                <div class="container mx-auto px-4 text-center">
                    <p>&copy; 2026 Countries Info. All rights reserved.</p>
                </div>
            </footer>
        </div>
    </body>
</html>
