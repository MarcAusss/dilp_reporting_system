@php
    $user = auth()->user();

    $roleValue = $user?->getRoleNames()->first();

    $roleLabel = $roleValue ? \App\Enums\UserRole::tryFrom($roleValue)?->label() ?? 'User' : 'User';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>
        @hasSection('title')
            @yield('title') | {{ config('app.name') }}
        @else
            {{ $title ?? config('app.name') }}
        @endif
    </title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>

<body class="h-full bg-slate-50 font-sans text-slate-900 antialiased" x-data="{
    sidebarOpen: false
}"
    @keydown.escape.window="sidebarOpen = false">

    <div class="min-h-screen">

        <x-navigation.sidebar :role-label="$roleLabel" />

        <div class="min-h-screen lg:pl-72">

            <x-navigation.topbar :role-label="$roleLabel" />

            <main class="px-4 py-6 sm:px-6 lg:px-8 lg:py-8">

                @isset($slot)
                    {{ $slot }}
                @else
                    @yield('content')
                @endisset

            </main>

            <footer class="border-t border-slate-200 bg-white px-4 py-4 sm:px-6 lg:px-8">
                <div
                    class="flex flex-col gap-1 text-[11px] text-slate-400 sm:flex-row sm:items-center sm:justify-between">
                    <p>
                        DILP Reporting and Monitoring System
                    </p>

                    <p>
                        Department of Labor and Employment
                    </p>
                </div>
            </footer>

        </div>

    </div>

    @livewireScripts

</body>

</html>
