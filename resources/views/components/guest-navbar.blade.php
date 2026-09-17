<nav class="border-b border-slate-200 bg-white">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
        <a href="{{ url('/') }}" class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-900 text-sm font-bold text-white">
                K
            </div>

            <span class="text-sm font-semibold text-slate-900">
                Kelana Coffee
            </span>
        </a>

        @guest
            <a href="#" class="text-sm font-medium text-slate-500 transition hover:text-slate-900">
                Help
            </a>

        @endguest

        @auth
            <div class="flex items-center gap-6">
                <div class="hidden items-center gap-1 md:flex">
                    <a href="{{ route('dashboard') }}" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                        Dashboard
                    </a>

                    @if(auth('web')->user()->role === 'admin')
                        <a href="#" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                            POS
                        </a>

                        <a href="#" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                            Produk
                        </a>

                        <a href="#" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                            Inventory
                        </a>

                        <a href="#" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                            Laporan
                        </a>
                    @endif

                    @if(auth('web')->user()->role === 'cashier')
                        <a href="#" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                            POS
                        </a>

                        <a href="#" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                            Transaksi
                        </a>
                    @endif

                    @if(auth('web')->user()->role === 'inventory')
                        <a href="#" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                            Inventory
                        </a>

                        <a href="#" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                            Barang Masuk
                        </a>

                        <a href="#" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
                            Barang Keluar
                        </a>
                    @endif
                </div>

                <div class="flex items-center gap-3 border-l border-slate-200 pl-5">
                    <div class="hidden text-right sm:block">
                        <p class="text-sm font-semibold text-slate-900">
                            {{ auth('web')->user()->name }}
                        </p>

                        <p class="text-xs capitalize text-slate-400">
                            {{ auth('web')->user()->role }}
                        </p>
                    </div>

                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-sm font-semibold text-slate-700">
                        {{ strtoupper(substr(auth('web')->user()->name, 0, 1)) }}
                    </div>
                </div>
            </div>
        @endauth
    </div>
</nav>