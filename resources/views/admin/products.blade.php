@extends('shared.dashboard', [
    'title' => 'Kelola Produk',
    'description' => 'Kelola katalog dan gambar produk.',
    'role' => 'admin',
])

@section('content')
    <main class="mx-auto w-full max-w-[1600px] px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#8B5E3C]">Admin</p>
                <h1 class="mt-1 text-2xl font-bold text-[#2B1810]">Kelola Produk</h1>
                <p class="mt-1 text-sm text-[#8B5E3C]">Admin bertanggung jawab atas gambar dan katalog makanan kasir.</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="rounded-xl border border-[#E4D4B8] bg-white px-4 py-2.5 text-center text-sm font-semibold text-[#3D2418] hover:bg-[#FAF3E7]">Kembali ke Overview</a>
        </div>

        @if (session('status'))
            <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="mt-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
            @forelse($products as $product)
                <article class="overflow-hidden rounded-2xl border border-[#E4D4B8] bg-white shadow-sm">
                    <div class="aspect-video overflow-hidden bg-[#FCFAF6]">
                        @if ($product->image_url)
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full items-center justify-center text-4xl font-bold text-[#C9A982]">K</div>
                        @endif
                    </div>
                    <div class="space-y-4 p-4">
                        <div>
                            <p class="font-bold text-[#2B1810]">{{ $product->name }}</p>
                            <p class="mt-1 text-xs text-[#8B5E3C]">{{ $product->sku }} · {{ $product->category?->name ?? 'Tanpa kategori' }}</p>
                        </div>
                        <form method="POST" action="{{ route('admin.products.image', $product) }}" enctype="multipart/form-data" class="space-y-2">
                            @csrf
                            <label class="block text-xs font-semibold text-[#5C3523]" for="image-{{ $product->id }}">Ganti gambar makanan</label>
                            <input id="image-{{ $product->id }}" type="file" name="image" accept="image/jpeg,image/png,image/webp" required class="block w-full rounded-xl border border-[#E4D4B8] bg-[#FCFAF6] px-3 py-2 text-xs text-[#5C3523] file:mr-3 file:rounded-lg file:border-0 file:bg-[#F5E9D3] file:px-3 file:py-2 file:text-xs file:font-semibold file:text-[#3D2418]">
                            <p class="text-[11px] text-[#A78868]">JPG, PNG, atau WebP · maksimal 2 MB · minimal 400x400 px</p>
                            <button type="submit" class="w-full rounded-xl bg-[#3D2418] px-4 py-2.5 text-sm font-semibold text-[#F5E9D3] hover:bg-[#2B1810]">Simpan Gambar</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-[#E4D4B8] bg-white px-6 py-14 text-center text-sm text-[#8B5E3C] sm:col-span-2 xl:col-span-3">Belum ada produk.</div>
            @endforelse
        </div>

        @if ($products->hasPages())
            <div class="mt-6">{{ $products->links() }}</div>
        @endif
    </main>
@endsection
