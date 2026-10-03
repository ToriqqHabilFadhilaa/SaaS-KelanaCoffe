<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Kelana Coffee</title>
    <meta name="description" content="Pulihkan akses akun Kelana Coffee POS melalui email terdaftar.">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Kelana Coffee">
    <meta property="og:title" content="Reset Password - Kelana Coffee POS">
    <meta property="og:description" content="Pulihkan akses ke workspace operasional Kelana Coffee.">
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

            @if (session('status'))
                <p class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700" role="status">{{ session('status') }}</p>
            @endif

            <x-card title="Lupa password?" description="Masukkan email akun Anda untuk menerima link reset password.">
                <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-5">
                    @csrf
                    <x-form-field label="Email" name="email" type="email" placeholder="nama@email.com" autocomplete="email" required autofocus />
                    <x-button type="submit" class="w-full">Kirim Link Reset</x-button>
                </form>
            </x-card>

            <div class="mt-6 text-center text-sm">
                <a href="{{ route('login') }}" class="font-semibold text-[#3D2418] underline underline-offset-2">Kembali ke login</a>
            </div>
        </div>
    </main>
</body>
</html>
