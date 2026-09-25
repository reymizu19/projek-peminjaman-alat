<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Panel Peminjam')</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        body {
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .nav-link {
            transition: all 0.2s ease;
        }

        .nav-link:hover {
            transform: translateX(2px);
        }
    </style>
</head>

<body class="bg-slate-100 text-slate-800 antialiased">
    <div class="flex min-h-screen">

        <aside class="hidden w-72 flex-col bg-slate-950 text-slate-200 md:flex">
            <div class="border-b border-slate-800 px-6 py-6">
                <div class="text-[10px] font-semibold uppercase tracking-[0.28em] text-blue-300">
                    Panel
                </div>
                <div class="mt-3 text-2xl font-semibold tracking-tight text-white">
                    Peminjam
                </div>
            </div>

            <nav class="flex-1 space-y-2 p-4">
                <a href="{{ route('peminjam.katalog') }}"
                   class="nav-link flex items-center justify-between rounded-xl px-4 py-3 text-sm font-medium text-slate-300 transition hover:bg-slate-800 hover:text-white {{ request()->routeIs('peminjam.katalog') ? 'bg-slate-800 text-white shadow-sm' : '' }}">
                    <span>Katalog Alat</span>
                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-blue-400"></span>
                </a>

                <a href="{{ route('peminjam.riwayat') }}"
                   class="nav-link flex items-center justify-between rounded-xl px-4 py-3 text-sm font-medium text-slate-300 transition hover:bg-slate-800 hover:text-white {{ request()->routeIs('peminjam.riwayat') ? 'bg-slate-800 text-white shadow-sm' : '' }}">
                    <span>Riwayat Peminjaman</span>
                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-sky-400"></span>
                </a>
            </nav>

            <div class="border-t border-slate-800 px-5 py-4">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400">Akun</p>
                        <p class="truncate text-sm font-semibold text-white">
                            {{ auth()->user()->name ?? 'Peminjam' }}
                        </p>
                    </div>

                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="rounded-lg bg-red-500 px-3 py-2 text-xs font-semibold text-white transition hover:bg-red-600">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="border-b border-slate-200 bg-white/90 backdrop-blur-sm">
                <div class="flex items-center justify-between px-4 py-4 md:px-6">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-[0.22em] text-slate-500">
                            Sistem Peminjaman
                        </p>
                        <h1 class="mt-1 text-xl font-semibold tracking-tight text-slate-900 md:text-2xl">
                            @yield('header-title', 'Panel Peminjam')
                        </h1>
                    </div>

                    <div class="hidden items-center gap-3 md:flex">
                        <div class="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700">
                            {{ auth()->user()->role ?? 'peminjam' }}
                        </div>

                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-100">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>

                <nav class="flex gap-2 overflow-x-auto border-t border-slate-200 bg-slate-50 px-4 py-3 md:hidden">
                    <a href="{{ route('peminjam.katalog') }}"
                       class="rounded-lg px-3 py-2 text-xs font-medium {{ request()->routeIs('peminjam.katalog') ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200' }}">
                        Katalog
                    </a>
                    <a href="{{ route('peminjam.riwayat') }}"
                       class="rounded-lg px-3 py-2 text-xs font-medium {{ request()->routeIs('peminjam.riwayat') ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200' }}">
                        Riwayat
                    </a>
                </nav>
            </header>

            <main class="flex-1 p-4 md:p-6">
                @if(session('success'))
                    <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 shadow-sm">
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>

</html>
