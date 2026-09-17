@extends('shared.dashboard', [
    'title' => $pageTitle,
    'description' => 'Daftar transaksi kasir.',
    'role' => 'kasir',
])

@section('content')
    <main class="mx-auto w-full max-w-6xl p-4 sm:p-6">
        <div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[#8B5E3C]">Kelana Coffee</p>
                <h1 class="mt-1 text-2xl font-bold">{{ $pageTitle }}</h1>
                <p class="mt-1 text-sm text-[#8B5E3C]">Pantau transaksi yang diproses oleh akun kasir ini.</p>
            </div>
            <a href="{{ route('cashier.dashboard') }}" class="rounded-xl bg-[#3D2418] px-4 py-2.5 text-center text-sm font-semibold text-[#F5E9D3]">
                Buat Pesanan Baru
            </a>
        </div>

        @if (session('status'))
            <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->has('order'))
            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ $errors->first('order') }}
            </div>
        @endif

        <form method="GET" action="{{ route($historyRoute) }}" class="mb-4 grid gap-3 rounded-2xl border border-[#E4D4B8] bg-white p-4 sm:grid-cols-[1fr_auto_auto_auto]">
            <input
                type="search"
                name="search"
                value="{{ request('search') }}"
                placeholder="Cari nomor order..."
                class="rounded-xl border border-[#E4D4B8] px-3 py-2 text-sm outline-none focus:border-[#8B5E3C] focus:ring-2 focus:ring-[#8B5E3C]/30"
            >
            @if ($historyRoute === 'cashier.history')
                <select name="status" class="rounded-xl border border-[#E4D4B8] bg-white px-3 py-2 text-sm outline-none focus:border-[#8B5E3C]">
                    <option value="">Semua status</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                    <option value="completed" @selected(request('status') === 'completed')>Selesai</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>Dibatalkan</option>
                </select>
            @endif
            <select name="payment_method" class="rounded-xl border border-[#E4D4B8] bg-white px-3 py-2 text-sm outline-none focus:border-[#8B5E3C]">
                <option value="">Semua metode</option>
                <option value="cash" @selected(request('payment_method') === 'cash')>Cash</option>
                <option value="qris" @selected(request('payment_method') === 'qris')>QRIS</option>
                <option value="debit" @selected(request('payment_method') === 'debit')>Kartu</option>
                <option value="credit" @selected(request('payment_method') === 'credit')>Credit</option>
            </select>
            <button type="submit" class="rounded-xl bg-[#3D2418] px-4 py-2 text-sm font-semibold text-[#F5E9D3]">Filter</button>
        </form>

        <div class="overflow-hidden rounded-2xl border border-[#E4D4B8] bg-white shadow-sm">
            @forelse($orders as $order)
                <div class="flex flex-col gap-3 border-b border-[#E4D4B8] p-4 last:border-b-0 hover:bg-[#FAF3E7] sm:flex-row sm:items-center sm:justify-between">
                    <a href="{{ route('cashier.receipt', $order) }}" class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold text-[#2B1810]">{{ $order->order_number }}</p>
                        <p class="mt-1 text-xs text-[#8B5E3C]">{{ $order->created_at->format('d M Y, H:i') }} · {{ $order->items->sum('qty') }} item · {{ strtoupper($order->payment_method) }}</p>
                    </a>
                    <div class="flex items-center justify-between gap-4 sm:justify-end">
                        <div class="text-left sm:text-right">
                            <p class="text-sm font-bold text-[#3D2418]">Rp{{ number_format($order->total, 0, ',', '.') }}</p>
                            <p @class([
                                'text-xs font-semibold',
                                'text-emerald-600' => $order->status === 'completed',
                                'text-amber-600' => $order->status === 'pending',
                                'text-rose-600' => $order->status === 'cancelled',
                            ])>{{ ucfirst($order->status) }}</p>
                        </div>
                        @if ($order->status === 'pending')
                            <form method="POST" action="{{ route('cashier.cancel', $order) }}" onsubmit="return confirm('Batalkan pesanan ini dan kembalikan stok?')">
                                @csrf
                                <button type="submit" class="rounded-lg border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50">Batalkan</button>
                            </form>
                        @else
                            <span class="text-lg text-[#A78868]" aria-hidden="true">&rarr;</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-6 py-16 text-center">
                    <p class="text-sm font-semibold">Belum ada transaksi</p>
                    <p class="mt-1 text-xs text-[#8B5E3C]">Transaksi yang selesai akan muncul di halaman ini.</p>
                </div>
            @endforelse
        </div>

        @if ($orders->hasPages())
            <div class="mt-5">
                {{ $orders->links() }}
            </div>
        @endif
    </main>
@endsection