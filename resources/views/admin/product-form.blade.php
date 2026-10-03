@extends('shared.dashboard', [
    'title' => $product ? 'Edit Produk' : 'Produk Baru',
    'description' => 'Kelola informasi produk dan resep bahan baku.',
    'role' => 'admin',
])

@php
    $formRecipeRows = old('ingredients', $recipeRows);
@endphp

@section('content')
    <main class="mx-auto w-full max-w-5xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#8B5E3C]">Admin · Produk</p>
                <h1 class="mt-1 text-2xl font-bold text-[#2B1810]">{{ $product ? 'Edit Produk' : 'Tambah Produk' }}</h1>
                <p class="mt-1 text-sm text-[#8B5E3C]">Resep bahan di sini akan dipakai Kasir untuk memvalidasi dan mengurangi stok.</p>
            </div>
            <a href="{{ route('admin.products') }}" class="rounded-xl border border-[#E4D4B8] bg-white px-4 py-2.5 text-sm font-semibold text-[#3D2418] hover:bg-[#FAF3E7]">Kembali ke Produk</a>
        </div>

        @if (session('status'))
            <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ $product ? route('admin.products.update', $product) : route('admin.products.store') }}" x-data="productRecipe(@js($formRecipeRows))" class="mt-6 space-y-6">
            @csrf
            @if ($product)
                @method('PUT')
            @endif

            <section class="rounded-2xl border border-[#E4D4B8] bg-white p-5 shadow-sm sm:p-6">
                <h2 class="text-lg font-bold text-[#2B1810]">Informasi Produk</h2>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="name" class="mb-2 block text-sm font-semibold text-[#5C3523]">Nama produk</label>
                        <input id="name" name="name" value="{{ old('name', $product?->name) }}" required maxlength="255" class="w-full rounded-xl border border-[#E4D4B8] bg-[#FCFAF6] px-3 py-2.5 text-sm outline-none focus:border-[#8B5E3C] focus:ring-2 focus:ring-[#8B5E3C]/20">
                    </div>
                    <div>
                        <label for="sku" class="mb-2 block text-sm font-semibold text-[#5C3523]">SKU</label>
                        <input id="sku" name="sku" value="{{ old('sku', $product?->sku) }}" required maxlength="255" class="w-full rounded-xl border border-[#E4D4B8] bg-[#FCFAF6] px-3 py-2.5 text-sm outline-none focus:border-[#8B5E3C] focus:ring-2 focus:ring-[#8B5E3C]/20">
                    </div>
                    <div>
                        <label for="category_id" class="mb-2 block text-sm font-semibold text-[#5C3523]">Kategori</label>
                        <select id="category_id" name="category_id" required class="w-full rounded-xl border border-[#E4D4B8] bg-[#FCFAF6] px-3 py-2.5 text-sm outline-none focus:border-[#8B5E3C]">
                            <option value="">Pilih kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id', $product?->category_id) === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="price" class="mb-2 block text-sm font-semibold text-[#5C3523]">Harga jual</label>
                        <input id="price" name="price" type="number" min="0.01" step="0.01" value="{{ old('price', $product?->price) }}" required class="w-full rounded-xl border border-[#E4D4B8] bg-[#FCFAF6] px-3 py-2.5 text-sm outline-none focus:border-[#8B5E3C] focus:ring-2 focus:ring-[#8B5E3C]/20">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="description" class="mb-2 block text-sm font-semibold text-[#5C3523]">Deskripsi</label>
                        <textarea id="description" name="description" rows="3" class="w-full rounded-xl border border-[#E4D4B8] bg-[#FCFAF6] px-3 py-2.5 text-sm outline-none focus:border-[#8B5E3C] focus:ring-2 focus:ring-[#8B5E3C]/20">{{ old('description', $product?->description) }}</textarea>
                    </div>
                </div>
                <input type="hidden" name="is_active" value="0">
                <label class="mt-4 flex items-center gap-2 text-sm font-medium text-[#5C3523]">
                    <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $product?->is_active ?? true)) class="h-4 w-4 rounded border-[#C49A6C] text-[#3D2418] focus:ring-[#3D2418]">
                    Tampilkan di katalog kasir
                </label>
            </section>

            <section class="rounded-2xl border border-[#E4D4B8] bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                    <div>
                        <h2 class="text-lg font-bold text-[#2B1810]">Resep Bahan</h2>
                        <p class="mt-1 text-xs text-[#8B5E3C]">Jumlah bahan yang dipakai untuk satu porsi produk.</p>
                    </div>
                    <button type="button" @click="addRow()" class="rounded-xl border border-[#8B5E3C] px-3 py-2 text-xs font-semibold text-[#8B5E3C] hover:bg-[#FAF3E7]">Tambah bahan</button>
                </div>

                <div class="mt-4 space-y-3">
                    <template x-for="(row, index) in recipeRows" :key="row.key">
                        <div class="grid gap-3 sm:grid-cols-[1fr_180px_auto] sm:items-end">
                            <div>
                                <label :for="'ingredient-' + row.key" class="mb-2 block text-xs font-semibold text-[#5C3523]">Bahan</label>
                                <select :id="'ingredient-' + row.key" :name="'ingredients[' + index + '][ingredient_id]'" x-model="row.ingredient_id" required class="w-full rounded-xl border border-[#E4D4B8] bg-[#FCFAF6] px-3 py-2.5 text-sm outline-none focus:border-[#8B5E3C]">
                                    <option value="">Pilih bahan</option>
                                    @foreach ($ingredients as $ingredient)
                                        <option value="{{ $ingredient->id }}">{{ $ingredient->name }} ({{ $ingredient->unit }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label :for="'qty-' + row.key" class="mb-2 block text-xs font-semibold text-[#5C3523]">Qty per porsi</label>
                                <div class="flex items-center gap-2">
                                    <input :id="'qty-' + row.key" :name="'ingredients[' + index + '][qty_needed]'" x-model="row.qty_needed" type="number" min="0.01" step="0.01" required class="w-full rounded-xl border border-[#E4D4B8] bg-[#FCFAF6] px-3 py-2.5 text-sm outline-none focus:border-[#8B5E3C]">
                                    <span class="text-xs text-[#8B5E3C]" x-text="ingredientUnit(row.ingredient_id)"></span>
                                </div>
                            </div>
                            <button type="button" @click="removeRow(index)" :disabled="recipeRows.length === 1" aria-label="Hapus bahan" class="h-10 rounded-xl border border-rose-200 px-3 text-xs font-semibold text-rose-600 hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-40">Hapus</button>
                        </div>
                    </template>
                </div>
            </section>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route('admin.products') }}" class="rounded-xl border border-[#E4D4B8] bg-white px-5 py-3 text-center text-sm font-semibold text-[#3D2418]">Batal</a>
                <button type="submit" class="rounded-xl bg-[#3D2418] px-5 py-3 text-sm font-semibold text-[#F5E9D3]">{{ $product ? 'Simpan Perubahan' : 'Buat Produk' }}</button>
            </div>
        </form>
    </main>
@endsection

@push('scripts')
    <script>
        function productRecipe(initialRows) {
            let nextKey = 1;

            return {
                recipeRows: initialRows.map((row) => ({ ...row, key: nextKey++ })),
                ingredientUnits: @js($ingredients->mapWithKeys(fn ($ingredient) => [$ingredient->id => $ingredient->unit])),
                addRow() {
                    this.recipeRows.push({ key: nextKey++, ingredient_id: '', qty_needed: '' });
                },
                removeRow(index) {
                    if (this.recipeRows.length > 1) {
                        this.recipeRows.splice(index, 1);
                    }
                },
                ingredientUnit(ingredientId) {
                    return this.ingredientUnits[ingredientId] ?? 'unit';
                },
            };
        }
    </script>
@endpush
