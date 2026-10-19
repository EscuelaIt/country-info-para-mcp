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
            <nav class="navbar bg-base-100 border-b border-base-300 px-4">
                <div class="flex-1">
                    <a href="{{ route('home') }}" class="btn btn-ghost text-xl font-bold">
                        🌍 Countries Info
                    </a>
                </div>
            </nav>

            {{-- Main Content --}}
            <main class="container mx-auto py-8 px-4">
                @yield('content')
            </main>

            {{-- Footer --}}
            <footer class="footer footer-center bg-base-200 text-base-content/70 mt-16 p-6">
                <div>
                    <p>&copy; 2026 Countries Info. All rights reserved.</p>
                </div>
            </footer>
        </div>
    </body>
</html>
