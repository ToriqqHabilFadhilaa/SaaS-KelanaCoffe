@extends('shared.dashboard', [
    'title' => 'Detail Order',
    'description' => 'Detail transaksi Kelana Coffee.',
    'role' => 'admin',
])

@section('content')
    <main class="mx-auto w-full max-w-4xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#8B5E3C]">Admin · Transaksi</p>
                <h1 class="mt-1 text-2xl font-bold text-[#2B1810]">{{ $order->order_number }}</h1>
                <p class="mt-1 text-sm text-[#8B5E3C]">{{ $order->created_at->format('d M Y, H:i') }}</p>
            </div>
            <a href="{{ route('admin.reports') }}" class="rounded-xl border border-[#E4D4B8] bg-white px-4 py-2.5 text-sm font-semibold text-[#3D2418] hover:bg-[#FAF3E7]">Kembali ke Laporan</a>
        </div>

        @if (session('status'))
            <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('status') }}</div>
        @endif
        @if ($errors->has('order'))
            <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first('order') }}</div>
        @endif

        <section class="mt-6 rounded-2xl border border-[#E4D4B8] bg-white p-5 shadow-sm sm:p-6">
            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#8B5E3C]">Kasir</p>
                    <p class="mt-1 text-sm font-semibold text-[#2B1810]">{{ $order->user?->name ?? 'Guest' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#8B5E3C]">Pembayaran</p>
                    <p class="mt-1 text-sm font-semibold uppercase text-[#2B1810]">{{ $order->payment_method }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#8B5E3C]">Status</p>
                    <p @class(['mt-1 text-sm font-semibold', 'text-emerald-700' => $order->status === 'completed', 'text-amber-700' => $order->status === 'pending', 'text-rose-700' => $order->status === 'cancelled'])>{{ ucfirst($order->status) }}</p>
                </div>
            </div>

            <div class="mt-6 overflow-x-auto border-y border-[#E4D4B8] py-2">
                <table class="w-full min-w-125 text-left text-sm">
                    <thead class="text-xs uppercase tracking-wide text-[#8B5E3C]">
                        <tr>
                            <th class="py-3 font-semibold">Produk</th>
                            <th class="py-3 text-right font-semibold">Qty</th>
                            <th class="py-3 text-right font-semibold">Harga</th>
                            <th class="py-3 text-right font-semibold">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E4D4B8]">
                        @foreach ($order->items as $item)
                            <tr>
                                <td class="py-3 font-semibold text-[#2B1810]">{{ $item->product_name }}</td>
                                <td class="py-3 text-right text-[#5C3523]">{{ $item->qty }}</td>
                                <td class="py-3 text-right text-[#5C3523]">Rp{{ number_format((float) $item->price, 0, ',', '.') }}</td>
                                <td class="py-3 text-right font-semibold text-[#2B1810]">Rp{{ number_format((float) $item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <dl class="ml-auto mt-5 grid max-w-sm grid-cols-2 gap-x-6 gap-y-2 text-sm">
                <dt class="text-[#8B5E3C]">Subtotal</dt><dd class="text-right">Rp{{ number_format((float) $order->subtotal, 0, ',', '.') }}</dd>
                <dt class="text-[#8B5E3C]">Pajak</dt><dd class="text-right">Rp{{ number_format((float) $order->tax, 0, ',', '.') }}</dd>
                <dt class="border-t border-[#E4D4B8] pt-2 font-bold text-[#2B1810]">Total</dt><dd class="border-t border-[#E4D4B8] pt-2 text-right font-bold text-[#2B1810]">Rp{{ number_format((float) $order->total, 0, ',', '.') }}</dd>
                <dt class="text-[#8B5E3C]">Dibayar</dt><dd class="text-right">Rp{{ number_format((float) $order->paid_amount, 0, ',', '.') }}</dd>
                <dt class="text-[#8B5E3C]">Kembalian</dt><dd class="text-right">Rp{{ number_format((float) $order->change_amount, 0, ',', '.') }}</dd>
            </dl>
        </section>

        @if ($order->status === 'pending')
            <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" class="mt-4 flex justify-end" onsubmit="return confirm('Batalkan order pending ini dan kembalikan stok?')">
                @csrf
                <button type="submit" class="rounded-xl border border-rose-200 bg-white px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-50">Batalkan Order Pending</button>
            </form>
        @endif
    </main>
@endsection
