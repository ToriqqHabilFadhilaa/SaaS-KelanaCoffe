@extends('shared.dashboard', [
    'title' => 'Kelola Pengguna',
    'description' => 'Kelola akses admin, kasir, dan inventory.',
    'role' => 'admin',
])

@section('content')
    <main class="mx-auto w-full max-w-[1600px] px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#8B5E3C]">Admin</p>
                <h1 class="mt-1 text-2xl font-bold text-[#2B1810]">Kelola Pengguna</h1>
                <p class="mt-1 text-sm text-[#8B5E3C]">Atur role dan status akun staf Kelana Coffee.</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="rounded-xl border border-[#E4D4B8] bg-white px-4 py-2.5 text-sm font-semibold text-[#3D2418] hover:bg-[#FAF3E7]">Kembali ke Overview</a>
        </div>

        @if (session('status'))
            <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('status') }}</div>
        @endif
        @if ($errors->has('user'))
            <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first('user') }}</div>
        @endif

        <section class="mt-6 rounded-2xl border border-[#E4D4B8] bg-white p-5 shadow-sm">
            <h2 class="text-lg font-bold text-[#2B1810]">Buat Akun Staf</h2>
            <form method="POST" action="{{ route('admin.users.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                @csrf
                <input name="name" value="{{ old('name') }}" placeholder="Nama lengkap" required class="rounded-xl border border-[#E4D4B8] bg-[#FCFAF6] px-3 py-2.5 text-sm outline-none focus:border-[#8B5E3C]">
                <input name="email" type="email" value="{{ old('email') }}" placeholder="Email" required class="rounded-xl border border-[#E4D4B8] bg-[#FCFAF6] px-3 py-2.5 text-sm outline-none focus:border-[#8B5E3C]">
                <input name="password" type="password" placeholder="Password awal" minlength="8" required class="rounded-xl border border-[#E4D4B8] bg-[#FCFAF6] px-3 py-2.5 text-sm outline-none focus:border-[#8B5E3C]">
                <input name="password_confirmation" type="password" placeholder="Ulangi password" minlength="8" required class="rounded-xl border border-[#E4D4B8] bg-[#FCFAF6] px-3 py-2.5 text-sm outline-none focus:border-[#8B5E3C]">
                <div class="flex gap-2">
                    <select name="role" required class="min-w-0 flex-1 rounded-xl border border-[#E4D4B8] bg-white px-3 py-2.5 text-sm outline-none focus:border-[#8B5E3C]">
                        <option value="kasir">Kasir</option>
                        <option value="inventory">Inventory</option>
                        <option value="admin">Admin</option>
                    </select>
                    <button type="submit" class="rounded-xl bg-[#3D2418] px-4 py-2.5 text-sm font-semibold text-[#F5E9D3]">Buat</button>
                </div>
            </form>
        </section>

        <section class="mt-6 overflow-hidden rounded-2xl border border-[#E4D4B8] bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-225 text-left text-sm">
                    <thead class="bg-[#FCFAF6] text-xs uppercase tracking-wide text-[#8B5E3C]">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Pengguna</th>
                            <th class="px-5 py-3 font-semibold">Role</th>
                            <th class="px-5 py-3 font-semibold">Status</th>
                            <th class="px-5 py-3 font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E4D4B8]">
                        @forelse($users as $managedUser)
                            <tr>
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-[#2B1810]">{{ $managedUser->name }} @if ($managedUser->id === $currentUserId)<span class="text-xs font-normal text-[#8B5E3C]">(Anda)</span>@endif</p>
                                    <p class="mt-1 text-xs text-[#8B5E3C]">{{ $managedUser->email }}</p>
                                </td>
                                @if ($managedUser->id === $currentUserId)
                                    <td class="px-5 py-4"><span class="rounded-full bg-[#F5E9D3] px-2.5 py-1 text-xs font-semibold text-[#3D2418]">Admin</span></td>
                                    <td class="px-5 py-4"><span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Aktif</span></td>
                                    <td class="px-5 py-4 text-xs text-[#8B5E3C]">Akun aktif yang sedang digunakan tidak dapat diubah.</td>
                                @else
                                    <form id="user-form-{{ $managedUser->id }}" method="POST" action="{{ route('admin.users.update', $managedUser) }}">
                                        @csrf
                                        @method('PATCH')
                                    </form>
                                    <td class="px-5 py-4">
                                        <select form="user-form-{{ $managedUser->id }}" name="role" class="rounded-lg border border-[#E4D4B8] bg-white px-2.5 py-2 text-xs outline-none focus:border-[#8B5E3C]">
                                            <option value="admin" @selected($managedUser->role === 'admin')>Admin</option>
                                            <option value="kasir" @selected($managedUser->role === 'kasir')>Kasir</option>
                                            <option value="inventory" @selected($managedUser->role === 'inventory')>Inventory</option>
                                        </select>
                                    </td>
                                    <td class="px-5 py-4">
                                        <input form="user-form-{{ $managedUser->id }}" type="hidden" name="is_active" value="0">
                                        <label class="flex items-center gap-2 text-xs text-[#5C3523]">
                                            <input form="user-form-{{ $managedUser->id }}" type="checkbox" name="is_active" value="1" @checked($managedUser->is_active) class="h-4 w-4 rounded border-[#C49A6C] text-[#3D2418] focus:ring-[#3D2418]">
                                            Aktif
                                        </label>
                                    </td>
                                    <td class="px-5 py-4">
                                        <button form="user-form-{{ $managedUser->id }}" type="submit" class="rounded-lg border border-[#8B5E3C] px-3 py-2 text-xs font-semibold text-[#8B5E3C] hover:bg-[#FAF3E7]">Simpan</button>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-14 text-center text-sm text-[#8B5E3C]">Belum ada pengguna.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($users->hasPages())
                <div class="border-t border-[#E4D4B8] p-5">{{ $users->links() }}</div>
            @endif
        </section>
    </main>
@endsection
