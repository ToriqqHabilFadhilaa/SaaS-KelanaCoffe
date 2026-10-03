@extends('shared.dashboard', [
    'title' => 'Admin Dashboard',
    'description' => 'Kelola operasional dan laporan Kelana Coffee.',
    'role' => 'admin',
])

@section('content')
    <main class="mx-auto w-full max-w-[1600px] px-4 py-6 sm:px-6 lg:px-8">
        <div>
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#8B5E3C]">Overview</p>
            <h1 class="mt-1 text-2xl font-bold text-[#2B1810]">Admin Dashboard</h1>
            <p class="mt-1 text-sm text-[#8B5E3C]">Pantau penjualan, order, dan kondisi operasional hari ini.</p>
            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ route('admin.products') }}" class="rounded-xl bg-[#3D2418] px-4 py-2.5 text-xs font-semibold text-[#F5E9D3]">Kelola Produk</a>
                <a href="{{ route('admin.categories') }}" class="rounded-xl border border-[#E4D4B8] bg-white px-4 py-2.5 text-xs font-semibold text-[#3D2418]">Kelola Kategori</a>
                <a href="{{ route('admin.suppliers') }}" class="rounded-xl border border-[#E4D4B8] bg-white px-4 py-2.5 text-xs font-semibold text-[#3D2418]">Kelola Supplier</a>
                <a href="{{ route('admin.users') }}" class="rounded-xl border border-[#E4D4B8] bg-white px-4 py-2.5 text-xs font-semibold text-[#3D2418]">Kelola Pengguna</a>
                <a href="{{ route('admin.reports') }}" class="rounded-xl border border-[#E4D4B8] bg-white px-4 py-2.5 text-xs font-semibold text-[#3D2418]">Laporan Penjualan</a>
                <a href="{{ route('admin.export.orders') }}" class="rounded-xl border border-[#E4D4B8] bg-white px-4 py-2.5 text-xs font-semibold text-[#3D2418]">Export Order CSV</a>
                <a href="{{ route('admin.export.stock-movements') }}" class="rounded-xl border border-[#E4D4B8] bg-white px-4 py-2.5 text-xs font-semibold text-[#3D2418]">Export Stok CSV</a>
            </div>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-2xl border border-[#E4D4B8] bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#8B5E3C]">Omzet hari ini</p>
                <p class="mt-2 text-2xl font-bold text-[#2B1810]">Rp{{ number_format($salesToday, 0, ',', '.') }}</p>
            </div>
            <div class="rounded-2xl border border-[#E4D4B8] bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#8B5E3C]">Order selesai</p>
                <p class="mt-2 text-2xl font-bold text-[#2B1810]">{{ $ordersToday }}</p>
            </div>
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Order pending</p>
                <p class="mt-2 text-2xl font-bold text-amber-900">{{ $pendingOrders }}</p>
            </div>
            <div class="rounded-2xl border border-rose-200 bg-rose-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Stok rendah</p>
                <p class="mt-2 text-2xl font-bold text-rose-900">{{ $lowStockCount }}</p>
            </div>
            <div class="rounded-2xl border border-[#E4D4B8] bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#8B5E3C]">Pengguna</p>
                <p class="mt-2 text-2xl font-bold text-[#2B1810]">{{ $userCount }}</p>
            </div>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-[1.35fr_1fr]">
            <section class="overflow-hidden rounded-2xl border border-[#E4D4B8] bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-[#E4D4B8] p-5">
                    <div>
                        <h2 class="text-lg font-bold text-[#2B1810]">Order Terbaru</h2>
                        <p class="mt-1 text-xs text-[#8B5E3C]">Aktivitas transaksi terakhir.</p>
                    </div>
                    <a href="{{ route('admin.products') }}" class="text-xs font-semibold text-[#8B5E3C] hover:text-[#3D2418]">Kelola produk</a>
                </div>
                <div class="divide-y divide-[#E4D4B8]">
                    @forelse($recentOrders as $order)
                        <div class="flex items-center justify-between gap-4 px-5 py-4">
                            <div class="min-w-0">
                                <a href="{{ route('admin.orders.show', $order) }}" class="truncate text-sm font-semibold text-[#2B1810] underline decoration-[#C9A982] underline-offset-2">{{ $order->order_number }}</a>
                                <p class="mt-1 text-xs text-[#8B5E3C]">{{ $order->user?->name ?? 'Guest' }} · {{ $order->created_at->format('d M Y, H:i') }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-sm font-bold text-[#3D2418]">Rp{{ number_format($order->total, 0, ',', '.') }}</p>
                                <p @class(['text-xs font-semibold', 'text-emerald-600' => $order->status === 'completed', 'text-amber-600' => $order->status === 'pending', 'text-rose-600' => $order->status === 'cancelled'])>{{ ucfirst($order->status) }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="px-5 py-12 text-center text-sm text-[#8B5E3C]">Belum ada order.</p>
                    @endforelse
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-[#E4D4B8] bg-white shadow-sm">
                <div class="border-b border-[#E4D4B8] p-5">
                    <h2 class="text-lg font-bold text-[#2B1810]">Produk Terlaris</h2>
                    <p class="mt-1 text-xs text-[#8B5E3C]">Penjualan selesai hari ini.</p>
                </div>
                <div class="divide-y divide-[#E4D4B8]">
                    @forelse($topProducts as $product)
                        <div class="flex items-center justify-between px-5 py-4">
                            <p class="truncate text-sm font-semibold text-[#2B1810]">{{ $product->product_name }}</p>
                            <span class="shrink-0 rounded-full bg-[#F5E9D3] px-3 py-1 text-xs font-bold text-[#3D2418]">{{ $product->total_qty }} terjual</span>
                        </div>
                    @empty
                        <p class="px-5 py-12 text-center text-sm text-[#8B5E3C]">Belum ada penjualan hari ini.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="mt-6 overflow-hidden rounded-2xl border border-[#E4D4B8] bg-white shadow-sm">
            <div class="border-b border-[#E4D4B8] p-5">
                <h2 class="text-lg font-bold text-[#2B1810]">Pergerakan Stok Terbaru</h2>
            </div>
            <div class="grid gap-0 divide-y divide-[#E4D4B8] md:grid-cols-2 md:divide-x md:divide-y-0">
                @forelse($recentMovements as $movement)
                    <div class="flex items-center justify-between gap-4 border-b border-[#E4D4B8] px-5 py-4 last:border-b-0 md:nth-last-child(-n+2):border-b-0">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-[#2B1810]">{{ $movement->ingredient?->name ?? 'Bahan dihapus' }}</p>
                            <p class="mt-1 text-xs text-[#8B5E3C]">{{ ucfirst($movement->reason) }} · {{ $movement->created_at->format('d M, H:i') }}</p>
                        </div>
                        <span @class(['shrink-0 text-sm font-bold', 'text-emerald-700' => $movement->type === 'in', 'text-rose-700' => $movement->type === 'out'])>{{ $movement->type === 'in' ? '+' : '-' }}{{ rtrim(rtrim(number_format((float) $movement->qty, 2, ',', '.'), '0'), ',') }}</span>
                    </div>
                @empty
                    <p class="px-5 py-12 text-center text-sm text-[#8B5E3C] md:col-span-2">Belum ada pergerakan stok.</p>
                @endforelse
            </div>
        </section>
    </main>
@endsection
