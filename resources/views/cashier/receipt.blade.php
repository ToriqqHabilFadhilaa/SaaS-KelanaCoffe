<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Nota - {{ $order->order_number }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#FAF3E7] p-6 text-[#2B1810]">
    <div class="mx-auto max-w-xl rounded-3xl border border-[#E4D4B8] bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between border-b border-[#E4D4B8] pb-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-[#8B5E3C]">Kelana Coffee</p>
                <h1 class="mt-2 text-2xl font-bold">Nota Pembayaran</h1>
            </div>
            <span class="rounded-full bg-[#F5E9D3] px-3 py-1 text-xs font-semibold text-[#3D2418]">
                {{ $order->order_number }}
            </span>
        </div>

        <div class="mt-5 space-y-1 text-sm text-[#5C3523]">
            <div class="flex justify-between">
                <span>Kasir</span>
                <span class="font-semibold">{{ $order->user->name ?? 'Guest' }}</span>
            </div>
            <div class="flex justify-between">
                <span>Tanggal</span>
                <span>{{ $order->created_at->format('d M Y, H:i') }}</span>
            </div>
            <div class="flex justify-between">
                <span>Metode</span>
                <span class="font-semibold uppercase">{{ $order->payment_method }}</span>
            </div>
        </div>

        <div class="mt-6 border-y border-[#E4D4B8] py-4">
            @foreach ($order->items as $item)
                <div class="mb-3 flex items-start justify-between gap-4 text-sm">
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold text-[#2B1810]">{{ $item->product_name }}</p>
                        <p class="text-[#8B5E3C]">{{ $item->qty }} x Rp{{ number_format($item->price, 0, ',', '.') }}</p>
                    </div>
                    <p class="font-semibold text-[#2B1810]">
                        Rp{{ number_format($item->subtotal, 0, ',', '.') }}
                    </p>
                </div>
            @endforeach
        </div>

        <div class="mt-5 space-y-2 text-sm text-[#5C3523]">
            <div class="flex justify-between">
                <span>Subtotal</span>
                <span>Rp{{ number_format($order->subtotal, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between">
                <span>Pajak</span>
                <span>Rp{{ number_format($order->tax, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between border-t border-[#E4D4B8] pt-2 text-base font-bold text-[#2B1810]">
                <span>Total</span>
                <span>Rp{{ number_format($order->total, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between">
                <span>Bayar</span>
                <span>Rp{{ number_format($order->paid_amount, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between">
                <span>Kembali</span>
                <span>Rp{{ number_format($order->change_amount, 0, ',', '.') }}</span>
            </div>
        </div>

        @if ($order->payment_method === 'qris' && $order->status === 'pending')
            <div class="mt-6 rounded-2xl border border-[#E4D4B8] bg-[#FAF3E7] p-4">
                <p class="text-sm font-bold text-[#2B1810]">Pembayaran QRIS belum selesai</p>
                <p class="mt-1 text-xs leading-relaxed text-[#8B5E3C]">
                    Buka pembayaran Sandbox Midtrans untuk melanjutkan transaksi.
                </p>

                @if ($midtransToken)
                    <button id="pay-with-midtrans" type="button" class="mt-4 w-full rounded-xl bg-[#3D2418] px-4 py-3 text-sm font-semibold text-[#F5E9D3] hover:bg-[#2B1810]">
                        Bayar dengan QRIS Sandbox
                    </button>
                @elseif (! $midtransConfigured)
                    <p class="mt-3 rounded-lg bg-white px-3 py-2 text-xs text-amber-700">
                        MIDTRANS_SERVER_KEY dan MIDTRANS_CLIENT_KEY belum diisi di file .env.
                    </p>
                @else
                    <p class="mt-3 rounded-lg bg-white px-3 py-2 text-xs text-rose-700">
                        Token pembayaran belum berhasil dibuat. Coba muat ulang halaman ini.
                    </p>
                @endif

                <form method="POST" action="{{ route('cashier.cancel', $order) }}" class="mt-2" onsubmit="return confirm('Batalkan pesanan QRIS ini dan kembalikan stok?')">
                    @csrf
                    <button type="submit" class="w-full rounded-xl border border-rose-200 bg-white px-4 py-2.5 text-sm font-semibold text-rose-600 hover:bg-rose-50">
                        Batalkan Pesanan
                    </button>
                </form>
            </div>
        @endif

        <div class="mt-8 flex gap-3">
            <a href="{{ route('cashier.dashboard') }}" class="flex-1 rounded-xl bg-[#3D2418] px-4 py-3 text-center text-sm font-semibold text-[#F5E9D3]">
                Kembali ke Kasir
            </a>
            <button type="button" onclick="window.print()" class="flex-1 rounded-xl border border-[#E4D4B8] bg-white px-4 py-3 text-sm font-semibold text-[#3D2418]">
                Cetak Nota
            </button>
        </div>
    </div>

    @if ($midtransToken)
        <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('services.midtrans.client_key') }}"></script>
        <script>
            document.getElementById('pay-with-midtrans')?.addEventListener('click', () => {
                window.snap.pay(@js($midtransToken), {
                    onSuccess: () => window.location.reload(),
                    onPending: () => window.location.reload(),
                    onError: () => window.location.reload(),
                    onClose: () => window.location.reload(),
                });
            });
        </script>
    @endif
</body>
</html>
