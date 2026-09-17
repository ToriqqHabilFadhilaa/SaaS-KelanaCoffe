<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Kelana Coffee</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#FAF3E7] text-[#2B1810]" style="background-image: url('{{ asset('assets/Background.png') }}');">
    <main class="flex min-h-screen items-center justify-center px-5 py-10 sm:px-8">
        <div class="w-full max-w-md">
            <div class="mb-6 flex items-center justify-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-xl">
                    <img src="{{ asset('assets/KelanaCoffe.png') }}" alt="Kelana Coffee" class="h-full w-full object-cover">
                </div>
                <span class="text-sm font-semibold tracking-wide">Kelana Coffee</span>
            </div>

            <x-card title="Buat password baru" description="Gunakan password baru untuk masuk kembali ke akun Anda.">
                <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-5">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <x-form-field label="Email" name="email" type="email" :value="$email" autocomplete="email" required autofocus />
                    <x-form-field label="Password baru" name="password" type="password" placeholder="Minimal 8 karakter" autocomplete="new-password" required />
                    <x-form-field label="Konfirmasi password" name="password_confirmation" type="password" placeholder="Ulangi password baru" autocomplete="new-password" required />
                    <x-button type="submit" class="w-full">Simpan Password</x-button>
                </form>
            </x-card>
        </div>
    </main>
</body>
</html>
