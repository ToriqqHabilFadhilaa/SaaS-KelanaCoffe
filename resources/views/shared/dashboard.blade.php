<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} - Kelana Coffee</title>
    <meta name="description" content="{{ $description ?? 'Kelana Coffee POS dan inventory workspace.' }}">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Kelana Coffee">
    <meta property="og:title" content="{{ $title }} - Kelana Coffee">
    <meta property="og:description" content="{{ $description ?? 'Kelana Coffee POS dan inventory workspace.' }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body @class([
    'min-h-screen',
    'bg-[#FAF3E7]',
    'text-[#2B1810]',
    'cashier-shell' => ($role ?? null) === 'kasir',
]) @style(($role ?? null) === 'kasir' ? "background-image: url('" . asset('assets/Background.png') . "');" : null)>
    @if (($role ?? null) === 'kasir')
        <header class="shrink-0 border-b border-[#E4D4B8] bg-white shadow-sm">
            <div class="mx-auto flex w-full max-w-[1600px] items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <a href="{{ route('cashier.dashboard') }}" class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-xl" aria-label="Ke dashboard kasir">
                        <img src="{{ asset('assets/KelanaCoffe.png') }}" alt="Kelana Coffee" class="h-full w-full object-cover">
                    </a>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold leading-tight">Kelana Coffee</p>
                        <p class="truncate text-xs text-[#8B5E3C]">Kasir: {{ auth()->user()->name ?? 'Guest' }}</p>
                    </div>
                </div>

                <nav class="flex min-w-0 items-center gap-1 overflow-x-auto" aria-label="Menu kasir">
                <a href="{{ route('cashier.dashboard') }}" @class([
                    'rounded-lg px-3 py-2 text-xs font-semibold transition',
                    'bg-[#3D2418] text-[#F5E9D3]' => request()->routeIs('cashier.dashboard'),
                    'text-[#8B5E3C] hover:bg-[#FAF3E7]' => ! request()->routeIs('cashier.dashboard'),
                ])>
                    Kasir
                </a>
                <a href="{{ route('cashier.history') }}" @class([
                    'rounded-lg px-3 py-2 text-xs font-semibold transition',
                    'bg-[#3D2418] text-[#F5E9D3]' => request()->routeIs('cashier.history'),
                    'text-[#8B5E3C] hover:bg-[#FAF3E7]' => ! request()->routeIs('cashier.history'),
                ])>
                    History Pesanan
                </a>
                <a href="{{ route('cashier.completed') }}" @class([
                    'rounded-lg px-3 py-2 text-xs font-semibold transition',
                    'bg-[#3D2418] text-[#F5E9D3]' => request()->routeIs('cashier.completed'),
                    'text-[#8B5E3C] hover:bg-[#FAF3E7]' => ! request()->routeIs('cashier.completed'),
                ])>
                    Transaksi Selesai
                </a>
                </nav>

                <div class="hidden shrink-0 items-center gap-3 sm:flex">
                    <span
                        class="tabular-nums text-sm text-[#8B5E3C]"
                        x-data="{ currentTime: '' }"
                        x-init="const updateTime = () => currentTime = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }); updateTime(); setInterval(updateTime, 1000)"
                        x-text="currentTime"
                    ></span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-button type="submit" variant="secondary" class="px-3 py-2 text-xs">
                            Keluar
                        </x-button>
                    </form>
                </div>
            </div>
        </header>
    @elseif (($role ?? null) === 'admin')
        <header class="shrink-0 border-b border-[#E4D4B8] bg-white shadow-sm">
            <div class="mx-auto flex w-full max-w-[1600px] items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <a href="{{ route('admin.dashboard') }}" class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-xl" aria-label="Ke dashboard admin">
                        <img src="{{ asset('assets/KelanaCoffe.png') }}" alt="Kelana Coffee" class="h-full w-full object-cover">
                    </a>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold leading-tight">Kelana Coffee</p>
                        <p class="truncate text-xs text-[#8B5E3C]">Admin: {{ auth()->user()->name ?? 'Guest' }}</p>
                    </div>
                </div>

                <nav class="flex min-w-0 items-center gap-1 overflow-x-auto" aria-label="Menu admin">
                    <a href="{{ route('admin.dashboard') }}" class="rounded-lg bg-[#3D2418] px-3 py-2 text-xs font-semibold text-[#F5E9D3]">Overview</a>
                    <a href="{{ route('admin.products') }}" class="rounded-lg px-3 py-2 text-xs font-semibold text-[#8B5E3C] transition hover:bg-[#FAF3E7]">Produk</a>
                    <a href="{{ route('admin.categories') }}" class="rounded-lg px-3 py-2 text-xs font-semibold text-[#8B5E3C] transition hover:bg-[#FAF3E7]">Kategori</a>
                    <a href="{{ route('admin.suppliers') }}" class="rounded-lg px-3 py-2 text-xs font-semibold text-[#8B5E3C] transition hover:bg-[#FAF3E7]">Supplier</a>
                    <a href="{{ route('admin.users') }}" class="rounded-lg px-3 py-2 text-xs font-semibold text-[#8B5E3C] transition hover:bg-[#FAF3E7]">Pengguna</a>
                    <a href="{{ route('admin.reports') }}" class="rounded-lg px-3 py-2 text-xs font-semibold text-[#8B5E3C] transition hover:bg-[#FAF3E7]">Laporan</a>
                </nav>

                <div class="hidden shrink-0 items-center gap-3 sm:flex">
                    <span class="tabular-nums text-sm text-[#8B5E3C]" x-data="{ currentTime: '' }" x-init="const updateTime = () => currentTime = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }); updateTime(); setInterval(updateTime, 1000)" x-text="currentTime"></span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-button type="submit" variant="secondary" class="px-3 py-2 text-xs">Keluar</x-button>
                    </form>
                </div>
            </div>
        </header>
    @elseif (($role ?? null) === 'inventory')
        <header class="shrink-0 border-b border-[#E4D4B8] bg-white shadow-sm">
            <div class="mx-auto flex w-full max-w-[1600px] items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <a href="{{ route('inventory.dashboard') }}" class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-xl" aria-label="Ke dashboard inventory">
                        <img src="{{ asset('assets/KelanaCoffe.png') }}" alt="Kelana Coffee" class="h-full w-full object-cover">
                    </a>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold leading-tight">Kelana Coffee</p>
                        <p class="truncate text-xs text-[#8B5E3C]">Inventory: {{ auth()->user()->name ?? 'Guest' }}</p>
                    </div>
                </div>

                <nav class="flex min-w-0 items-center gap-1 overflow-x-auto" aria-label="Menu inventory">
                    <a href="{{ route('inventory.dashboard') }}" class="rounded-lg bg-[#3D2418] px-3 py-2 text-xs font-semibold text-[#F5E9D3]">
                        Inventory
                    </a>
                    <a href="{{ route('inventory.movements') }}" class="rounded-lg px-3 py-2 text-xs font-semibold text-[#8B5E3C] transition hover:bg-[#FAF3E7]">
                        Movement
                    </a>
                </nav>

                <div class="hidden shrink-0 items-center gap-3 sm:flex">
                    <span class="tabular-nums text-sm text-[#8B5E3C]" x-data="{ currentTime: '' }" x-init="const updateTime = () => currentTime = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }); updateTime(); setInterval(updateTime, 1000)" x-text="currentTime"></span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-button type="submit" variant="secondary" class="px-3 py-2 text-xs">
                            Keluar
                        </x-button>
                    </form>
                </div>
            </div>
        </header>
    @endif

    @yield('content')

    @stack('scripts')
</body>
</html>
