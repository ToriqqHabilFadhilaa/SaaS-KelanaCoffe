@extends('shared.dashboard', [
    'title' => 'Cashier Dashboard',
    'description' => 'Kelola transaksi dan aktivitas kasir.',
    'role' => 'kasir',
])

@php
    $initialProducts = $products->map(function ($product) {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'price' => (float) $product->price,
            'category' => $product->category?->slug ?? '',
            'image' => $product->image_url ?? null,
            'stock' => $product->available_stock,
        ];
    })->values()->all();
@endphp

@section('content')
    <div x-data="posCart(@js($initialProducts))" @class(['flex', 'h-[calc(100vh-65px)]', 'flex-col'])>
        <main @class(['grid', 'min-h-0', 'flex-1', 'grid-cols-1', 'overflow-hidden', 'lg:grid-cols-[1fr_380px]'])>
            <section @class(['mx-auto', 'flex', 'min-h-0', 'w-full', 'max-w-7xl', 'flex-col', 'overflow-hidden', 'px-4', 'py-5', 'sm:px-6', 'lg:px-8'])>
                <div @class(['mb-4', 'flex', 'shrink-0', 'flex-col', 'gap-3', 'sm:flex-row', 'sm:items-end', 'sm:justify-between', 'sm:gap-4'])>
                    <div>
                        <p @class(['text-[11px]', 'font-semibold', 'uppercase', 'tracking-[0.18em]', 'text-[#8B5E3C]'])>Menu Hari Ini</p>
                        <h1 @class(['mt-1', 'text-2xl', 'font-bold', 'text-[#2B1810]'])>Katalog Produk</h1>
                        <p @class(['mt-1', 'text-xs', 'text-[#A78868]']) x-text="filteredProducts().length + ' produk tersedia'"></p>
                    </div>
                    <div @class(['relative', 'w-full', 'sm:max-w-xs'])>
                        <input type="text" x-model="searchQuery" placeholder="Cari produk..."
                        @class([
                            'w-full', 'rounded-xl', 'border', 'border-[#E4D4B8]', 'bg-white',
                            'py-3', 'pl-11', 'pr-10', 'text-sm', 'outline-none',
                            'focus:ring-2', 'focus:ring-[#8B5E3C]/40', 'focus:border-[#8B5E3C]',
                        ])
                        >
                        <svg @class(['pointer-events-none', 'absolute', 'left-4', 'top-1/2', '-translate-y-1/2', 'h-4', 'w-4', 'text-[#A78868]']) fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
                        </svg>
                        <button type="button" x-show="searchQuery.length > 0"
                            @click="searchQuery = ''"
                            aria-label="Hapus pencarian"
                            @class(['absolute', 'right-3', 'top-1/2', '-translate-y-1/2', 'text-[#A78868]', 'hover:text-[#3D2418]'])
                        >
                            <svg @class(['h-4', 'w-4']) fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div @class(['mb-5', 'shrink-0'])>
                    <p @class(['mb-2', 'text-xs', 'font-semibold', 'text-[#5C3523]'])>Pilih kategori</p>
                    <div @class(['scrollbar-hidden', 'flex', 'gap-2', 'overflow-x-auto', 'pb-1'])>
                        <button type="button" @click="activeCategory = 'all'" :class="activeCategory === 'all' ? 'border-[#3D2418] bg-[#3D2418] text-[#F5E9D3]' : 'border-[#E4D4B8] bg-white text-[#8B5E3C]'" @class(['shrink-0', 'rounded-xl', 'border', 'px-4', 'py-2.5', 'text-xs', 'font-semibold', 'transition'])>
                            Semua Produk
                        </button>

                        @foreach($categories as $category)
                            <button type="button" @click="activeCategory = '{{ $category->slug }}'" :class="activeCategory === '{{ $category->slug }}' ? 'border-[#3D2418] bg-[#3D2418] text-[#F5E9D3]' : 'border-[#E4D4B8] bg-white text-[#8B5E3C]'" @class(['shrink-0', 'rounded-xl', 'border', 'px-4', 'py-2.5', 'text-xs', 'font-semibold', 'transition'])>
                                {{ $category->name }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div @class(['scrollbar-hidden', 'grid', 'flex-1', 'auto-rows-min', 'grid-cols-2', 'gap-4', 'overflow-y-auto', 'pb-4', 'sm:grid-cols-3', 'xl:grid-cols-4'])>
                    <template x-for="product in filteredProducts()" :key="product.id">
                        <button @click="addToCart(product)" :disabled="product.stock === 0"
                            @class([
                                'group', 'relative', 'overflow-hidden', 'rounded-2xl', 'border', 'border-[#E4D4B8]',
                                'bg-white', 'text-left', 'shadow-sm', 'transition',
                                'hover:border-[#8B5E3C]', 'hover:shadow-md', 'active:scale-[0.97]',
                                'disabled:cursor-not-allowed', 'disabled:opacity-50', 'disabled:active:scale-100',
                            ])
                        >
                            <div @class(['relative', 'aspect-[4/3]', 'w-full', 'overflow-hidden', 'bg-white'])>
                                <img :src="product.image || placeholderImage" :alt="product.name" x-on:error="$event.target.src = placeholderImage" @class(['h-full', 'w-full', 'object-cover', 'transition', 'group-hover:scale-105'])>
                                <span x-show="product.stock === 0" @class(['absolute', 'inset-0', 'flex', 'items-center', 'justify-center', 'bg-black/50', 'text-xs', 'font-bold', 'text-white'])>
                                    Habis
                                </span>
                                <span x-show="product.stock !== null && product.stock > 0" @class(['absolute', 'left-2', 'top-2', 'rounded-md', 'bg-white/90', 'px-2', 'py-1', 'text-[10px]', 'font-bold', 'text-[#3D2418]', 'shadow-sm']) x-text="product.stock + ' tersisa'"></span>
                            </div>
                            <div @class(['flex', 'min-h-[76px]', 'flex-col', 'justify-between', 'p-3'])>
                                <p @class(['truncate', 'text-sm', 'font-semibold']) x-text="product.name"></p>
                                <div @class(['mt-2', 'flex', 'items-center', 'justify-between', 'gap-2'])>
                                    <p @class(['text-sm', 'font-bold', 'text-[#8B5E3C]']) x-text="'Rp' + formatNumber(product.price)"></p>
                                    <span @class(['text-[10px]', 'font-semibold', 'text-[#A78868]'])>Tambah</span>
                                </div>
                            </div>
                        </button>
                    </template>

                    <div x-show="filteredProducts().length === 0" @class(['col-span-full', 'flex', 'flex-col', 'items-center', 'justify-center', 'py-16', 'text-center', 'text-[#A78868]'])>
                        <p @class(['text-sm', 'font-semibold'])>Produk tidak ditemukan</p>
                        <p @class(['text-xs'])>Coba kata kunci lain atau ganti kategori</p>
                    </div>
                </div>
            </section>

            <aside @class(['flex', 'min-h-0', 'flex-col', 'overflow-hidden', 'border-l', 'border-[#E4D4B8]', 'bg-white'])>
                <div @class(['flex', 'shrink-0', 'items-start', 'justify-between', 'border-b', 'border-[#E4D4B8]', 'px-5', 'py-4'])>
                    <div>
                        <p @class(['text-[11px]', 'font-semibold', 'uppercase', 'tracking-[0.18em]', 'text-[#8B5E3C]'])>Checkout</p>
                        <h2 @class(['mt-1', 'text-lg', 'font-bold', 'text-[#2B1810]'])>Pesanan Saat Ini</h2>
                        <p @class(['mt-0.5', 'text-xs', 'text-[#A78868]']) x-text="items.length + ' produk dipilih'"></p>
                    </div>
                    <button type="button" x-show="items.length > 0" @click="clearCart" @class(['rounded-lg', 'px-2', 'py-1', 'text-xs', 'font-semibold', 'text-rose-600', 'hover:bg-rose-50'])>
                        Kosongkan
                    </button>
                </div>

                <div @class(['scrollbar-hidden', 'min-h-0', 'flex-1', 'overflow-y-auto', 'bg-[#FCFAF6]', 'p-4'])>
                    <template x-if="items.length === 0">
                        <div @class(['flex', 'h-full', 'min-h-48', 'flex-col', 'items-center', 'justify-center', 'text-center', 'text-[#A78868]'])>
                            <div @class(['mb-3', 'flex', 'h-12', 'w-12', 'items-center', 'justify-center', 'rounded-full', 'bg-[#F5E9D3]'])>
                                <svg @class(['h-5', 'w-5', 'text-[#8B5E3C]']) fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 4h2l1.5 11h11L19 8H6m2.5 11.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm9 0a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                                </svg>
                            </div>
                            <p @class(['text-sm', 'font-semibold', 'text-[#5C3523]'])>Belum ada pesanan</p>
                            <p @class(['mt-1', 'text-xs'])>Pilih produk dari katalog</p>
                        </div>
                    </template>

                    <div class="space-y-2">
                        <template x-for="item in items" :key="item.id">
                            <div @class(['rounded-xl', 'border', 'border-[#E4D4B8]', 'bg-white', 'p-3', 'shadow-sm'])>
                                <div @class(['flex', 'items-start', 'justify-between', 'gap-3'])>
                                    <div @class(['min-w-0', 'flex-1'])>
                                        <p @class(['truncate', 'text-sm', 'font-semibold', 'text-[#2B1810]']) x-text="item.name"></p>
                                        <p @class(['mt-1', 'text-xs', 'text-[#8B5E3C]']) x-text="'Rp' + formatNumber(item.price) + ' / item'"></p>
                                    </div>
                                    <p @class(['shrink-0', 'text-sm', 'font-bold', 'text-[#3D2418]']) x-text="'Rp' + formatNumber(item.price * item.qty)"></p>
                                </div>

                                <div @class(['mt-3', 'flex', 'items-center', 'justify-between'])>
                                    <button type="button" @click="removeItem(item.id)" @class(['text-xs', 'font-semibold', 'text-[#A78868]', 'hover:text-rose-600'])>Hapus</button>
                                    <div @class(['flex', 'items-center', 'gap-1', 'rounded-lg', 'border', 'border-[#E4D4B8]', 'bg-[#FCFAF6]', 'p-1'])>
                                        <button type="button" @click="decrementQty(item.id)" aria-label="Kurangi jumlah" @class(['flex', 'h-6', 'w-6', 'items-center', 'justify-center', 'rounded', 'text-sm', 'font-bold', 'text-[#8B5E3C]', 'hover:bg-white'])>-</button>
                                        <span @class(['w-7', 'text-center', 'text-xs', 'font-bold', 'text-[#2B1810]']) x-text="item.qty"></span>
                                        <button type="button" @click="incrementQty(item.id)" :disabled="isMaxStock(item)" aria-label="Tambah jumlah" @class(['flex', 'h-6', 'w-6', 'items-center', 'justify-center', 'rounded', 'text-sm', 'font-bold', 'text-[#8B5E3C]', 'hover:bg-white', 'disabled:cursor-not-allowed', 'disabled:opacity-40'])>+</button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div @class(['shrink-0', 'border-t', 'border-[#E4D4B8]', 'bg-white', 'p-5'])>
                    <div @class(['space-y-2', 'text-sm'])>
                        <div @class(['flex', 'justify-between', 'text-[#8B5E3C]'])>
                            <span>Subtotal</span>
                            <span x-text="'Rp' + formatNumber(subtotal())"></span>
                        </div>
                        <div @class(['flex', 'justify-between', 'text-[#8B5E3C]'])>
                            <span>Pajak <span class="text-xs">(10%)</span></span>
                            <span x-text="'Rp' + formatNumber(tax())"></span>
                        </div>
                        <div @class(['mt-3', 'flex', 'items-end', 'justify-between', 'border-t', 'border-[#E4D4B8]', 'pt-3'])>
                            <span @class(['text-sm', 'font-semibold', 'text-[#2B1810]'])>Total Pembayaran</span>
                            <span @class(['text-xl', 'font-bold', 'text-[#3D2418]']) x-text="'Rp' + formatNumber(total())"></span>
                        </div>
                    </div>

                    <div @class(['mt-5'])>
                        <p @class(['mb-2', 'text-xs', 'font-semibold', 'text-[#5C3523]'])>Metode Pembayaran</p>
                        <div @class(['grid', 'grid-cols-2', 'gap-2'])>
                            <button type="button" @click="paymentMethod = 'cash'" :class="paymentMethod === 'cash' ? 'border-[#3D2418] bg-[#3D2418] text-[#F5E9D3]' : 'border-[#E4D4B8] bg-white text-[#8B5E3C]'" @class(['rounded-xl', 'border', 'px-3', 'py-2.5', 'text-sm', 'font-semibold', 'transition'])>
                                Cash
                            </button>
                            <button type="button" @click="paymentMethod = 'qris'" :class="paymentMethod === 'qris' ? 'border-[#3D2418] bg-[#3D2418] text-[#F5E9D3]' : 'border-[#E4D4B8] bg-white text-[#8B5E3C]'" @class(['rounded-xl', 'border', 'px-3', 'py-2.5', 'text-sm', 'font-semibold', 'transition'])>
                                QRIS
                            </button>
                        </div>
                    </div>

                    <div x-show="paymentMethod === 'cash'" x-transition @class(['mt-4'])>
                        <label @class(['mb-1.5', 'block', 'text-xs', 'font-semibold', 'text-[#5C3523]'])>Uang Diterima</label>
                        <input type="number" x-model.number="cashReceived" placeholder="Masukkan nominal"
                            @class(['w-full', 'rounded-xl', 'border', 'border-[#E4D4B8]', 'bg-[#FCFAF6]', 'px-3', 'py-2.5', 'text-sm', 'outline-none', 'focus:border-[#8B5E3C]', 'focus:ring-2', 'focus:ring-[#8B5E3C]/20'])
                        >
                        <div @class(['mt-2', 'flex', 'flex-wrap', 'gap-1.5'])>
                            <template x-for="amount in suggestedCashAmounts()" :key="amount">
                                <button type="button" @click="cashReceived = amount" @class(['rounded-lg', 'border', 'border-[#E4D4B8]', 'bg-white', 'px-2.5', 'py-1.5', 'text-xs', 'font-semibold', 'text-[#8B5E3C]', 'hover:bg-[#FAF3E7]']) x-text="'Rp' + formatNumber(amount)"></button>
                            </template>
                        </div>
                        <p x-show="cashReceived >= total() && total() > 0" @class(['mt-2', 'text-xs', 'font-semibold', 'text-emerald-600'])>
                            Kembalian: <span x-text="'Rp' + formatNumber(cashReceived - total())"></span>
                        </p>
                    </div>

                    <p x-show="paymentMethod === 'qris'" x-transition @class(['mt-4', 'rounded-xl', 'bg-[#F5E9D3]', 'px-3', 'py-2.5', 'text-xs', 'leading-relaxed', 'text-[#5C3523]'])>
                        Pesanan QRIS akan berstatus pending sampai pembayaran dikonfirmasi.
                    </p>

                    <form :action="checkoutUrl" method="POST" @submit="prepareCheckout" @class(['mt-4'])>
                        @csrf
                        <input type="hidden" name="items" :value="JSON.stringify(items.map(i => ({ product_id: i.id, qty: i.qty })))">
                        <input type="hidden" name="payment_method" :value="paymentMethod">
                        <input type="hidden" name="cash_received" :value="cashReceived">

                        <x-button type="submit" @class(['w-full', 'py-3']) loading-text="Memproses Pembayaran..." x-bind:disabled="items.length === 0 || (paymentMethod === 'cash' && cashReceived < total())">
                            <span x-text="paymentMethod === 'qris' ? 'Buat Pesanan QRIS' : 'Bayar Sekarang'"></span>
                        </x-button>
                    </form>
                </div>
            </aside>
        </main>
    </div>
@endsection

@push('scripts')
    <script>
        function posCart(initialProducts) {
            return {
                products: initialProducts,
                items: [],
                searchQuery: '',
                activeCategory: 'all',
                paymentMethod: 'cash',
                cashReceived: 0,
                checkoutUrl: '{{ route('cashier.checkout') }}',
                placeholderImage: "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' fill='%23FFFFFF'/%3E%3Ctext x='50' y='58' font-family='sans-serif' font-size='34' font-weight='700' text-anchor='middle' fill='%238B5E3C'%3EK%3C/text%3E%3C/svg%3E",
                taxRate: 0.10,

                filteredProducts() {
                    const query = this.searchQuery.trim().toLowerCase();

                    return this.products.filter((product) => {
                        const matchesCategory = this.activeCategory === 'all' || product.category === this.activeCategory;
                        const matchesSearch = query === '' || product.name.toLowerCase().includes(query);
                        return matchesCategory && matchesSearch;
                    });
                },

                addToCart(product) {
                    if (product.stock === 0) return;

                    const existing = this.items.find(i => i.id === product.id);

                    if (existing) {
                        if (this.isMaxStock(existing)) return;
                        existing.qty++;
                    } else {
                        this.items.push({ ...product, qty: 1 });
                    }
                },

                incrementQty(id) {
                    const item = this.items.find(i => i.id === id);
                    if (item && !this.isMaxStock(item)) item.qty++;
                },

                decrementQty(id) {
                    const item = this.items.find(i => i.id === id);
                    if (item && item.qty > 1) {
                        item.qty--;
                    } else {
                        this.removeItem(id);
                    }
                },

                removeItem(id) {
                    this.items = this.items.filter(i => i.id !== id);
                },

                clearCart() {
                    if (this.items.length === 0) return;
                    if (confirm('Batalkan semua item di keranjang?')) {
                        this.items = [];
                        this.cashReceived = 0;
                    }
                },

                isMaxStock(item) {
                    return item.stock !== null && item.qty >= item.stock;
                },

                subtotal() {
                    return this.items.reduce((sum, i) => sum + (i.price * i.qty), 0);
                },

                tax() {
                    return Math.round(this.subtotal() * this.taxRate);
                },

                total() {
                    return this.subtotal() + this.tax();
                },

                suggestedCashAmounts() {
                    const total = this.total();
                    if (total <= 0) return [];

                    const denominations = [20000, 50000, 100000, 150000, 200000];
                    const suggestions = new Set([total]); // "Uang Pas"

                    denominations.forEach((d) => {
                        if (d >= total) suggestions.add(d);
                    });

                    return Array.from(suggestions).sort((a, b) => a - b).slice(0, 4);
                },

                formatNumber(num) {
                    return new Intl.NumberFormat('id-ID').format(num);
                },

                prepareCheckout(e) {
                    if (this.items.length === 0) {
                        e.preventDefault();
                        alert('Keranjang masih kosong.');
                        return;
                    }
                    if (this.paymentMethod === 'cash' && this.cashReceived < this.total()) {
                        e.preventDefault();
                        alert('Uang diterima kurang dari total.');
                        return;
                    }
                },
            };
        }
    </script>
@endpush