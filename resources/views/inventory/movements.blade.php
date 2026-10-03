@extends('shared.dashboard', [
    'title' => 'Riwayat Stok',
    'description' => 'Audit pergerakan stok inventory.',
    'role' => 'inventory',
])

@section('content')
    <main class="mx-auto w-full max-w-[1600px] px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#8B5E3C]">Inventory</p>
                <h1 class="mt-1 text-2xl font-bold text-[#2B1810]">Riwayat Pergerakan Stok</h1>
                <p class="mt-1 text-sm text-[#8B5E3C]">Audit semua stok masuk, keluar, penjualan, kerusakan, dan koreksi.</p>
            </div>
            <a href="{{ route('inventory.dashboard') }}" class="rounded-xl border border-[#E4D4B8] bg-white px-4 py-2.5 text-sm font-semibold text-[#3D2418] hover:bg-[#FAF3E7]">Kembali ke Inventory</a>
        </div>

        <form method="GET" action="{{ route('inventory.movements') }}" class="mt-6 grid gap-3 rounded-2xl border border-[#E4D4B8] bg-white p-4 sm:grid-cols-2 xl:grid-cols-5">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari bahan atau SKU" class="rounded-xl border border-[#E4D4B8] px-3 py-2 text-sm outline-none focus:border-[#8B5E3C]">
            <select name="type" class="rounded-xl border border-[#E4D4B8] bg-white px-3 py-2 text-sm outline-none focus:border-[#8B5E3C]">
                <option value="">Semua tipe</option>
                <option value="in" @selected(request('type') === 'in')>Stok masuk</option>
                <option value="out" @selected(request('type') === 'out')>Stok keluar</option>
            </select>
            <select name="reason" class="rounded-xl border border-[#E4D4B8] bg-white px-3 py-2 text-sm outline-none focus:border-[#8B5E3C]">
                <option value="">Semua alasan</option>
                <option value="purchase" @selected(request('reason') === 'purchase')>Pembelian</option>
                <option value="sale" @selected(request('reason') === 'sale')>Penjualan</option>
                <option value="damage" @selected(request('reason') === 'damage')>Rusak</option>
                <option value="adjustment" @selected(request('reason') === 'adjustment')>Koreksi</option>
            </select>
            <input type="date" name="from" value="{{ request('from') }}" aria-label="Dari tanggal" class="rounded-xl border border-[#E4D4B8] px-3 py-2 text-sm outline-none focus:border-[#8B5E3C]">
            <input type="date" name="to" value="{{ request('to') }}" aria-label="Sampai tanggal" class="rounded-xl border border-[#E4D4B8] px-3 py-2 text-sm outline-none focus:border-[#8B5E3C]">
            <button type="submit" class="rounded-xl bg-[#3D2418] px-4 py-2 text-sm font-semibold text-[#F5E9D3]">Filter</button>
        </form>

        <section class="mt-5 overflow-hidden rounded-2xl border border-[#E4D4B8] bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-225 text-left text-sm">
                    <thead class="bg-[#FCFAF6] text-xs uppercase tracking-wide text-[#8B5E3C]">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Waktu</th>
                            <th class="px-5 py-3 font-semibold">Bahan</th>
                            <th class="px-5 py-3 font-semibold">Tipe</th>
                            <th class="px-5 py-3 font-semibold">Alasan</th>
                            <th class="px-5 py-3 font-semibold">Jumlah</th>
                            <th class="px-5 py-3 font-semibold">Stok setelah</th>
                            <th class="px-5 py-3 font-semibold">Supplier</th>
                            <th class="px-5 py-3 font-semibold">User</th>
                            <th class="px-5 py-3 font-semibold">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E4D4B8]">
                        @forelse($movements as $movement)
                            <tr class="hover:bg-[#FCFAF6]">
                                <td class="whitespace-nowrap px-5 py-4 text-xs text-[#8B5E3C]">{{ $movement->created_at->format('d M Y, H:i') }}</td>
                                <td class="px-5 py-4 font-semibold text-[#2B1810]">{{ $movement->ingredient?->name ?? 'Bahan dihapus' }}</td>
                                <td class="px-5 py-4"><span class="rounded-full bg-[#F5E9D3] px-2.5 py-1 text-xs font-semibold text-[#3D2418]">{{ strtoupper($movement->type) }}</span></td>
                                <td class="px-5 py-4 text-[#5C3523]">{{ ucfirst($movement->reason) }}</td>
                                <td @class(['px-5 py-4 font-bold', 'text-emerald-700' => $movement->type === 'in', 'text-rose-700' => $movement->type === 'out'])>{{ $movement->type === 'in' ? '+' : '-' }}{{ rtrim(rtrim(number_format((float) $movement->qty, 2, ',', '.'), '0'), ',') }}</td>
                                <td class="px-5 py-4 text-[#5C3523]">{{ rtrim(rtrim(number_format((float) $movement->stock_after, 2, ',', '.'), '0'), ',') }}</td>
                                <td class="px-5 py-4 text-[#5C3523]">{{ $movement->supplier?->name ?? '-' }}</td>
                                <td class="px-5 py-4 text-[#5C3523]">{{ $movement->user?->name ?? 'System' }}</td>
                                <td class="max-w-xs px-5 py-4 text-xs text-[#8B5E3C]">{{ $movement->note ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-5 py-14 text-center text-sm text-[#8B5E3C]">Belum ada pergerakan stok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($movements->hasPages())
                <div class="border-t border-[#E4D4B8] p-5">{{ $movements->links() }}</div>
            @endif
        </section>
    </main>
@endsection
