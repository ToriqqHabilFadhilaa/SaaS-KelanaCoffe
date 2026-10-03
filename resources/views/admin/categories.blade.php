@extends('shared.dashboard', [
    'title' => 'Kategori Produk',
    'description' => 'Kelola kategori katalog Kasir.',
    'role' => 'admin',
])

@section('content')
    <main class="mx-auto w-full max-w-[1600px] px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#8B5E3C]">Admin · Katalog</p>
                <h1 class="mt-1 text-2xl font-bold text-[#2B1810]">Kategori Produk</h1>
                <p class="mt-1 text-sm text-[#8B5E3C]">Kategori aktif akan muncul sebagai filter di halaman Kasir.</p>
            </div>
            <a href="{{ route('admin.products') }}" class="rounded-xl border border-[#E4D4B8] bg-white px-4 py-2.5 text-sm font-semibold text-[#3D2418] hover:bg-[#FAF3E7]">Kembali ke Produk</a>
        </div>

        @if (session('status'))
            <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
        @endif

        <section class="mt-6 rounded-2xl border border-[#E4D4B8] bg-white p-5 shadow-sm">
            <h2 class="text-lg font-bold text-[#2B1810]">Tambah Kategori</h2>
            <form method="POST" action="{{ route('admin.categories.store') }}" class="mt-4 grid gap-3 sm:grid-cols-[1fr_auto_auto] sm:items-end">
                @csrf
                <div>
                    <label for="category-name" class="mb-1.5 block text-xs font-semibold text-[#5C3523]">Nama kategori</label>
                    <input id="category-name" name="name" value="{{ old('name') }}" required maxlength="255" class="w-full rounded-xl border border-[#E4D4B8] bg-[#FCFAF6] px-3 py-2.5 text-sm outline-none focus:border-[#8B5E3C]">
                </div>
                <input type="hidden" name="is_active" value="0">
                <label class="flex items-center gap-2 pb-2 text-sm text-[#5C3523]">
                    <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 rounded border-[#C49A6C] text-[#3D2418] focus:ring-[#3D2418]">
                    Aktif
                </label>
                <button type="submit" class="rounded-xl bg-[#3D2418] px-4 py-2.5 text-sm font-semibold text-[#F5E9D3]">Tambah Kategori</button>
            </form>
        </section>

        <section class="mt-6 overflow-hidden rounded-2xl border border-[#E4D4B8] bg-white shadow-sm">
            <div class="border-b border-[#E4D4B8] p-5">
                <h2 class="text-lg font-bold text-[#2B1810]">Daftar Kategori</h2>
            </div>
            <div class="divide-y divide-[#E4D4B8]">
                @forelse($categories as $category)
                    <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="grid gap-3 px-5 py-4 sm:grid-cols-[1fr_auto_auto_auto] sm:items-center">
                        @csrf
                        @method('PATCH')
                        <div class="min-w-0">
                            <input name="name" value="{{ $category->name }}" required maxlength="255" aria-label="Nama kategori" class="w-full rounded-lg border border-[#E4D4B8] px-3 py-2 text-sm font-semibold text-[#2B1810] outline-none focus:border-[#8B5E3C]">
                            <p class="mt-1 text-xs text-[#A78868]">Slug: {{ $category->slug }} · {{ $category->products_count }} produk</p>
                        </div>
                        <input type="hidden" name="is_active" value="0">
                        <label class="flex items-center gap-2 text-xs text-[#5C3523]">
                            <input type="checkbox" name="is_active" value="1" @checked($category->is_active) class="h-4 w-4 rounded border-[#C49A6C] text-[#3D2418] focus:ring-[#3D2418]">
                            Aktif di kasir
                        </label>
                        <span class="rounded-full px-2.5 py-1 text-center text-xs font-semibold {{ $category->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                        <button type="submit" class="rounded-lg border border-[#8B5E3C] px-3 py-2 text-xs font-semibold text-[#8B5E3C] hover:bg-[#FAF3E7]">Simpan</button>
                    </form>
                @empty
                    <p class="px-5 py-12 text-center text-sm text-[#8B5E3C]">Belum ada kategori.</p>
                @endforelse
            </div>
            @if ($categories->hasPages())
                <div class="border-t border-[#E4D4B8] p-5">{{ $categories->links() }}</div>
            @endif
        </section>
    </main>
@endsection
