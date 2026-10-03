@extends('shared.dashboard', [
    'title' => 'Supplier',
    'description' => 'Kelola supplier dan kontak pemasok bahan baku.',
    'role' => 'admin',
])

@section('content')
    <main class="mx-auto w-full max-w-[1600px] px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#8B5E3C]">Admin</p>
                <h1 class="mt-1 text-2xl font-bold text-[#2B1810]">Supplier</h1>
                <p class="mt-1 text-sm text-[#8B5E3C]">Kelola supplier bahan baku dan kontak pemasok utama.</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="rounded-xl border border-[#E4D4B8] bg-white px-4 py-2.5 text-sm font-semibold text-[#3D2418] hover:bg-[#FAF3E7]">Kembali</a>
        </div>

        @if (session('status'))
            <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('status') }}</div>
        @endif

        <div class="mt-6 grid gap-6 xl:grid-cols-[1.1fr_1.5fr]">
            <section class="rounded-2xl border border-[#E4D4B8] bg-white p-5 shadow-sm">
                <h2 class="text-lg font-bold text-[#2B1810]">Tambah Supplier Baru</h2>
                <form method="POST" action="{{ route('admin.suppliers.store') }}" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label for="name" class="mb-2 block text-sm font-semibold text-[#5C3523]">Nama supplier</label>
                        <input id="name" name="name" type="text" required class="w-full rounded-xl border border-[#E4D4B8] bg-[#FCFAF6] px-3 py-2.5 text-sm outline-none focus:border-[#8B5E3C] focus:ring-2 focus:ring-[#8B5E3C]/20">
                    </div>
                    <div>
                        <label for="phone" class="mb-2 block text-sm font-semibold text-[#5C3523]">No. telepon</label>
                        <input id="phone" name="phone" type="text" class="w-full rounded-xl border border-[#E4D4B8] bg-[#FCFAF6] px-3 py-2.5 text-sm outline-none focus:border-[#8B5E3C] focus:ring-2 focus:ring-[#8B5E3C]/20">
                    </div>
                    <div>
                        <label for="address" class="mb-2 block text-sm font-semibold text-[#5C3523]">Alamat</label>
                        <textarea id="address" name="address" rows="3" class="w-full rounded-xl border border-[#E4D4B8] bg-[#FCFAF6] px-3 py-2.5 text-sm outline-none focus:border-[#8B5E3C] focus:ring-2 focus:ring-[#8B5E3C]/20"></textarea>
                    </div>
                    <label class="flex items-center gap-2 text-sm font-medium text-[#5C3523]">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 rounded border-[#C49A6C] text-[#3D2418] focus:ring-[#3D2418]">
                        Supplier aktif
                    </label>
                    <button type="submit" class="w-full rounded-xl bg-[#3D2418] px-4 py-3 text-sm font-semibold text-[#F5E9D3]">Simpan Supplier</button>
                </form>
            </section>

            <section class="overflow-hidden rounded-2xl border border-[#E4D4B8] bg-white shadow-sm">
                <div class="border-b border-[#E4D4B8] p-5">
                    <h2 class="text-lg font-bold text-[#2B1810]">Daftar Supplier</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-225 text-left text-sm">
                        <thead class="bg-[#FCFAF6] text-xs uppercase tracking-wide text-[#8B5E3C]">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Nama</th>
                                <th class="px-5 py-3 font-semibold">Kontak</th>
                                <th class="px-5 py-3 font-semibold">Alamat</th>
                                <th class="px-5 py-3 font-semibold">Update</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E4D4B8]">
                            @forelse($suppliers as $supplier)
                                <tr class="hover:bg-[#FCFAF6]">
                                    <td colspan="4" class="px-5 py-4">
                                        <form method="POST" action="{{ route('admin.suppliers.update', $supplier) }}" class="grid gap-2 sm:grid-cols-[1fr_150px_1.5fr_auto_auto] sm:items-center">
                                            @csrf
                                            @method('PATCH')
                                            <input name="name" value="{{ $supplier->name }}" required aria-label="Nama supplier" class="min-w-0 rounded-lg border border-[#E4D4B8] px-2.5 py-2 text-xs outline-none focus:border-[#8B5E3C]">
                                            <input name="phone" value="{{ $supplier->phone }}" aria-label="Telepon supplier" placeholder="Telepon" class="min-w-0 rounded-lg border border-[#E4D4B8] px-2.5 py-2 text-xs outline-none focus:border-[#8B5E3C]">
                                            <input name="address" value="{{ $supplier->address }}" aria-label="Alamat supplier" placeholder="Alamat" class="min-w-0 rounded-lg border border-[#E4D4B8] px-2.5 py-2 text-xs outline-none focus:border-[#8B5E3C]">
                                            <input type="hidden" name="is_active" value="0">
                                            <label class="flex items-center gap-1.5 whitespace-nowrap text-xs text-[#5C3523]">
                                                <input type="checkbox" name="is_active" value="1" @checked($supplier->is_active) class="h-4 w-4 rounded border-[#C49A6C] text-[#3D2418] focus:ring-[#3D2418]">
                                                Aktif
                                            </label>
                                            <button type="submit" class="rounded-lg border border-[#8B5E3C] px-3 py-2 text-xs font-semibold text-[#8B5E3C] hover:bg-[#FAF3E7]">Simpan</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-5 py-14 text-center text-sm text-[#8B5E3C]">Belum ada supplier.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($suppliers->hasPages())
                    <div class="border-t border-[#E4D4B8] p-5">{{ $suppliers->links() }}</div>
                @endif
            </section>
        </div>
    </main>
@endsection
