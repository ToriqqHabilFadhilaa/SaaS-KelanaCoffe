@extends('shared.dashboard', [
    'title' => 'Laporan Penjualan',
    'description' => 'Laporan omzet berdasarkan waktu pembayaran.',
    'role' => 'admin',
])

@section('content')
    <main class="mx-auto w-full max-w-[1600px] px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#8B5E3C]">Admin</p>
                <h1 class="mt-1 text-2xl font-bold text-[#2B1810]">Laporan Penjualan</h1>
                <p class="mt-1 text-sm text-[#8B5E3C]">Omzet dicatat berdasarkan waktu pembayaran berhasil.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.reports.export', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="rounded-xl bg-[#3D2418] px-4 py-2.5 text-sm font-semibold text-[#F5E9D3]">Export CSV</a>
                <a href="{{ route('admin.dashboard') }}" class="rounded-xl border border-[#E4D4B8] bg-white px-4 py-2.5 text-sm font-semibold text-[#3D2418] hover:bg-[#FAF3E7]">Overview</a>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.reports') }}" class="mt-6 flex flex-col gap-3 rounded-2xl border border-[#E4D4B8] bg-white p-4 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="from" class="mb-1.5 block text-xs font-semibold text-[#5C3523]">Dari tanggal</label>
                <input id="from" type="date" name="from" value="{{ $from->toDateString() }}" class="w-full rounded-xl border border-[#E4D4B8] px-3 py-2.5 text-sm outline-none focus:border-[#8B5E3C]">
            </div>
            <div class="flex-1">
                <label for="to" class="mb-1.5 block text-xs font-semibold text-[#5C3523]">Sampai tanggal</label>
                <input id="to" type="date" name="to" value="{{ $to->toDateString() }}" class="w-full rounded-xl border border-[#E4D4B8] px-3 py-2.5 text-sm outline-none focus:border-[#8B5E3C]">
            </div>
            <button type="submit" class="rounded-xl bg-[#3D2418] px-5 py-2.5 text-sm font-semibold text-[#F5E9D3]">Tampilkan</button>
        </form>

        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border border-[#E4D4B8] bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#8B5E3C]">Total omzet</p>
                <p class="mt-2 text-3xl font-bold text-[#2B1810]">Rp{{ number_format($salesTotal, 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-[#A78868]">{{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</p>
            </div>
            <div class="rounded-2xl border border-[#E4D4B8] bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#8B5E3C]">Transaksi berhasil</p>
                <p class="mt-2 text-3xl font-bold text-[#2B1810]">{{ $ordersCount }}</p>
                <p class="mt-1 text-xs text-[#A78868]">Order berstatus selesai dan sudah dibayar</p>
            </div>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-[0.85fr_1.15fr]">
            <section class="overflow-hidden rounded-2xl border border-[#E4D4B8] bg-white shadow-sm">
                <div class="border-b border-[#E4D4B8] p-5">
                    <h2 class="text-lg font-bold text-[#2B1810]">Metode Pembayaran</h2>
                </div>
                <div class="divide-y divide-[#E4D4B8]">
                    @forelse($paymentBreakdown as $payment)
                        <div class="flex items-center justify-between gap-4 px-5 py-4">
                            <div>
                                <p class="text-sm font-semibold uppercase text-[#2B1810]">{{ $payment->payment_method }}</p>
                                <p class="mt-1 text-xs text-[#8B5E3C]">{{ $payment->orders_count }} transaksi</p>
                            </div>
                            <p class="text-sm font-bold text-[#3D2418]">Rp{{ number_format((float) $payment->sales_total, 0, ',', '.') }}</p>
                        </div>
                    @empty
                        <p class="px-5 py-10 text-center text-sm text-[#8B5E3C]">Belum ada pembayaran di periode ini.</p>
                    @endforelse
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-[#E4D4B8] bg-white shadow-sm">
                <div class="border-b border-[#E4D4B8] p-5">
                    <h2 class="text-lg font-bold text-[#2B1810]">Ringkasan Harian</h2>
                </div>
                <div class="divide-y divide-[#E4D4B8]">
                    @forelse($dailySales as $day)
                        <div class="flex items-center justify-between gap-4 px-5 py-3.5">
                            <div>
                                <p class="text-sm font-semibold text-[#2B1810]">{{ \Illuminate\Support\Carbon::parse($day->report_date)->format('d M Y') }}</p>
                                <p class="mt-1 text-xs text-[#8B5E3C]">{{ $day->orders_count }} transaksi</p>
                            </div>
                            <p class="text-sm font-bold text-[#3D2418]">Rp{{ number_format((float) $day->sales_total, 0, ',', '.') }}</p>
                        </div>
                    @empty
                        <p class="px-5 py-10 text-center text-sm text-[#8B5E3C]">Belum ada pembayaran di periode ini.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="mt-6 overflow-hidden rounded-2xl border border-[#E4D4B8] bg-white shadow-sm">
            <div class="border-b border-[#E4D4B8] p-5">
                <h2 class="text-lg font-bold text-[#2B1810]">Transaksi Berhasil</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-225 text-left text-sm">
                    <thead class="bg-[#FCFAF6] text-xs uppercase tracking-wide text-[#8B5E3C]">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Order</th>
                            <th class="px-5 py-3 font-semibold">Dibayar</th>
                            <th class="px-5 py-3 font-semibold">Kasir</th>
                            <th class="px-5 py-3 font-semibold">Metode</th>
                            <th class="px-5 py-3 font-semibold">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E4D4B8]">
                        @forelse($orders as $order)
                            <tr>
                                <td class="px-5 py-4 font-semibold text-[#2B1810]"><a href="{{ route('admin.orders.show', $order) }}" class="underline decoration-[#C9A982] underline-offset-2">{{ $order->order_number }}</a></td>
                                <td class="px-5 py-4 text-[#5C3523]">{{ $order->paid_at?->format('d M Y, H:i') ?? '-' }}</td>
                                <td class="px-5 py-4 text-[#5C3523]">{{ $order->user?->name ?? 'Guest' }}</td>
                                <td class="px-5 py-4 uppercase text-[#5C3523]">{{ $order->payment_method }}</td>
                                <td class="px-5 py-4 font-bold text-[#3D2418]">Rp{{ number_format($order->total, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-[#8B5E3C]">Tidak ada transaksi pada rentang tanggal tersebut.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($orders->hasPages())
                <div class="border-t border-[#E4D4B8] p-5">{{ $orders->links() }}</div>
            @endif
        </section>
    </main>
@endsection
