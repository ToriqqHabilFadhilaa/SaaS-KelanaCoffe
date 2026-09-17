@extends('shared.dashboard', [
    'title' => 'Inventory Dashboard',
    'description' => 'Pantau stok dan pergerakan persediaan.',
    'role' => 'inventory',
])

@section('content')
    <main class="mx-auto w-full max-w-[1600px] px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#8B5E3C]">Operasional</p>
                <h1 class="mt-1 text-2xl font-bold text-[#2B1810]">Inventory</h1>
                <p class="mt-1 text-sm text-[#8B5E3C]">Pantau bahan baku dan pergerakan stok Kelana Coffee.</p>
            </div>
            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">
                    {{ session('status') }}
                </div>
            @endif
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-[#E4D4B8] bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#8B5E3C]">Total bahan</p>
                <p class="mt-2 text-3xl font-bold text-[#2B1810]">{{ $ingredientCount }}</p>
                <p class="mt-1 text-xs text-[#A78868]">Bahan terdaftar</p>
            </div>
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Perlu restock</p>
                <p class="mt-2 text-3xl font-bold text-amber-900">{{ $lowStockCount }}</p>
                <p class="mt-1 text-xs text-amber-700">Stok di bawah minimum</p>
            </div>
            <div class="rounded-2xl border border-[#E4D4B8] bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#8B5E3C]">Supplier aktif</p>
                <p class="mt-2 text-3xl font-bold text-[#2B1810]">{{ $supplierCount }}</p>
                <p class="mt-1 text-xs text-[#A78868]">Mitra pemasok</p>
            </div>
        </div>

        <section class="mt-6 rounded-2xl border border-[#E4D4B8] bg-white shadow-sm">
            <div class="flex flex-col justify-between gap-4 border-b border-[#E4D4B8] p-5 sm:flex-row sm:items-end">
                <div>
                    <h2 class="text-lg font-bold text-[#2B1810]">Daftar Bahan</h2>
                    <p class="mt-1 text-xs text-[#8B5E3C]">Tambahkan stok masuk langsung dari daftar bahan.</p>
                </div>
                <form method="GET" action="{{ route('inventory.dashboard') }}" class="flex flex-col gap-2 sm:flex-row">
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari nama atau SKU..." class="rounded-xl border border-[#E4D4B8] px-3 py-2 text-sm outline-none focus:border-[#8B5E3C] focus:ring-2 focus:ring-[#8B5E3C]/20">
                    <select name="stock" class="rounded-xl border border-[#E4D4B8] bg-white px-3 py-2 text-sm outline-none focus:border-[#8B5E3C]">
                        <option value="">Semua stok</option>
                        <option value="low" @selected(request('stock') === 'low')>Stok rendah</option>
                    </select>
                    <button type="submit" class="rounded-xl bg-[#3D2418] px-4 py-2 text-sm font-semibold text-[#F5E9D3]">Filter</button>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-225 text-left text-sm">
                    <thead class="bg-[#FCFAF6] text-xs uppercase tracking-wide text-[#8B5E3C]">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Bahan</th>
                            <th class="px-5 py-3 font-semibold">Supplier</th>
                            <th class="px-5 py-3 font-semibold">Stok</th>
                            <th class="px-5 py-3 font-semibold">Minimum</th>
                            <th class="px-5 py-3 font-semibold">Pergerakan stok</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E4D4B8]">
                        @forelse($ingredients as $ingredient)
                            <tr class="align-middle hover:bg-[#FCFAF6]">
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-[#2B1810]">{{ $ingredient->name }}</p>
                                    <p class="mt-1 text-xs text-[#A78868]">{{ $ingredient->sku }} · {{ $ingredient->unit }}</p>
                                </td>
                                <td class="px-5 py-4 text-[#5C3523]">{{ $ingredient->supplier?->name ?? 'Internal' }}</td>
                                <td class="px-5 py-4">
                                    <span @class([
                                        'font-bold',
                                        'text-amber-700' => $ingredient->isLowStock(),
                                        'text-emerald-700' => ! $ingredient->isLowStock(),
                                    ])>
                                        {{ rtrim(rtrim(number_format((float) $ingredient->stock, 2, ',', '.'), '0'), ',') }} {{ $ingredient->unit }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-[#5C3523]">{{ rtrim(rtrim(number_format((float) $ingredient->minimum_stock, 2, ',', '.'), '0'), ',') }} {{ $ingredient->unit }}</td>
                                <td class="px-5 py-4">
                                    <div class="space-y-2">
                                        <form method="POST" action="{{ route('inventory.stock-in') }}" class="flex items-center gap-2">
                                            @csrf
                                            <input type="hidden" name="ingredient_id" value="{{ $ingredient->id }}">
                                            <input type="number" name="qty" min="0.01" step="0.01" placeholder="Jumlah masuk" required class="w-28 rounded-lg border border-[#E4D4B8] px-2.5 py-2 text-sm outline-none focus:border-[#8B5E3C] focus:ring-2 focus:ring-[#8B5E3C]/20">
                                            <button type="submit" class="rounded-lg border border-emerald-600 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">+ Masuk</button>
                                        </form>
                                        <form method="POST" action="{{ route('inventory.stock-out') }}" class="flex items-center gap-2">
                                            @csrf
                                            <input type="hidden" name="ingredient_id" value="{{ $ingredient->id }}">
                                            <input type="number" name="qty" min="0.01" step="0.01" placeholder="Jumlah keluar" required class="w-28 rounded-lg border border-[#E4D4B8] px-2.5 py-2 text-sm outline-none focus:border-rose-600 focus:ring-2 focus:ring-rose-600/20">
                                            <select name="reason" class="rounded-lg border border-[#E4D4B8] bg-white px-2 py-2 text-xs outline-none focus:border-rose-600">
                                                <option value="damage">Rusak</option>
                                                <option value="adjustment">Koreksi</option>
                                            </select>
                                            <button type="submit" class="rounded-lg border border-rose-600 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50">- Keluar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-14 text-center text-sm text-[#8B5E3C]">Bahan tidak ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($ingredients->hasPages())
                <div class="border-t border-[#E4D4B8] p-5">{{ $ingredients->links() }}</div>
            @endif
        </section>

        <section class="mt-6 rounded-2xl border border-[#E4D4B8] bg-white shadow-sm">
            <div class="border-b border-[#E4D4B8] p-5">
                <h2 class="text-lg font-bold text-[#2B1810]">Pergerakan Stok Terbaru</h2>
            </div>
            <div class="divide-y divide-[#E4D4B8]">
                @forelse($recentMovements as $movement)
                    <div class="flex flex-col gap-2 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-[#2B1810]">{{ $movement->ingredient?->name ?? 'Bahan dihapus' }}</p>
                            <p class="mt-1 text-xs text-[#8B5E3C]">{{ ucfirst($movement->reason) }} · {{ $movement->user?->name ?? 'System' }} · {{ $movement->created_at->format('d M Y, H:i') }}</p>
                        </div>
                        <div class="text-left sm:text-right">
                            <p @class(['text-sm', 'font-bold', 'text-emerald-700' => $movement->type === 'in', 'text-rose-700' => $movement->type === 'out'])>
                                {{ $movement->type === 'in' ? '+' : '-' }}{{ rtrim(rtrim(number_format((float) $movement->qty, 2, ',', '.'), '0'), ',') }}
                            </p>
                            <p class="text-xs text-[#A78868]">{{ $movement->note }}</p>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-12 text-center text-sm text-[#8B5E3C]">Belum ada pergerakan stok.</p>
                @endforelse
            </div>
        </section>
    </main>
@endsection
