@extends('layouts.guest')

@section('title', 'Sign In | ' . config('app.name'))

@section('content')

    <div class="min-h-screen lg:grid lg:grid-cols-[1.1fr_0.9fr]">

        {{-- Left government information panel --}}
        <section
            class="relative hidden overflow-hidden bg-[#0f2a44] px-12 py-12 text-white lg:flex lg:flex-col lg:justify-between">
            <div class="absolute inset-0 opacity-10"
                style="
                background-image:
                    linear-gradient(rgba(255,255,255,.15) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(255,255,255,.15) 1px, transparent 1px);
                background-size: 48px 48px;
            ">
            </div>

            <div class="relative z-10">
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-14 w-14 items-center justify-center rounded-xl border border-white/20 bg-white/10 text-sm font-bold tracking-wide">
                        DILP
                    </div>

                    <div>
                        <p class="text-sm font-medium text-slate-300">
                            Department of Labor and Employment
                        </p>

                        <h1 class="mt-1 text-lg font-semibold">
                            DILP Reporting and Monitoring System
                        </h1>
                    </div>
                </div>
            </div>

            <div class="relative z-10 max-w-xl">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-blue-200">
                    Government Information System
                </p>

                <h2 class="mt-5 text-4xl font-semibold leading-tight tracking-tight">
                    Project monitoring, fund utilization, and reporting in one
                    authoritative system.
                </h2>

                <p class="mt-6 max-w-lg text-base leading-7 text-slate-300">
                    Secure access to DILP project records, workflow monitoring,
                    beneficiary information, financial accomplishment, and
                    official reporting.
                </p>
            </div>

            <div class="relative z-10 border-t border-white/10 pt-6 text-xs text-slate-400">
                Authorized personnel only.
            </div>
        </section>

        {{-- Login panel --}}
        <main class="flex min-h-screen items-center justify-center px-6 py-10 sm:px-10 lg:px-16">
            <div class="w-full max-w-md">

                {{-- Mobile heading --}}
                <div class="mb-8 lg:hidden">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#0f2a44] text-xs font-bold tracking-wide text-white">
                        DILP
                    </div>

                    <h1 class="mt-5 text-lg font-semibold text-slate-900">
                        DILP Reporting and Monitoring System
                    </h1>
                </div>

                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-[#1e5a8a]">
                        Secure Access
                    </p>

                    <h2 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">
                        Sign in to your account
                    </h2>

                    <p class="mt-2 text-sm leading-6 text-slate-500">
                        Enter your authorized system credentials to continue.
                    </p>
                </div>

                @if ($errors->any())
                    <div class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3" role="alert">
                        <p class="text-sm font-medium text-red-800">
                            The credentials you entered could not be verified.
                        </p>

                        <p class="mt-1 text-xs leading-5 text-red-600">
                            Check your email and password, then try again.
                        </p>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700">
                            Email address
                        </label>

                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                            autocomplete="username" placeholder="name@example.gov.ph"
                            class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[#1e5a8a] focus:ring-4 focus:ring-blue-100">
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <label for="password" class="block text-sm font-medium text-slate-700">
                                Password
                            </label>
                        </div>

                        <input id="password" type="password" name="password" required autocomplete="current-password"
                            class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition focus:border-[#1e5a8a] focus:ring-4 focus:ring-blue-100">
                    </div>

                    <label class="flex items-center gap-3">
                        <input type="checkbox" name="remember" value="1"
                            class="h-4 w-4 rounded border-slate-300 text-[#164b73] focus:ring-[#1e5a8a]">

                        <span class="text-sm text-slate-600">
                            Keep me signed in on this device
                        </span>
                    </label>

                    <button type="submit"
                        class="flex w-full items-center justify-center rounded-lg bg-[#0f2a44] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#163b5d] focus:outline-none focus:ring-4 focus:ring-blue-200">
                        Sign In
                    </button>
                </form>

                <div class="mt-8 border-t border-slate-200 pt-5">
                    <p class="text-xs leading-5 text-slate-500">
                        Access is restricted to authorized personnel. System
                        activities may be recorded for security and audit purposes.
                    </p>
                </div>

            </div>
        </main>

    </div>

@endsection
