<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk - Kelana Coffee</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body @class(['min-h-screen', 'bg-[#2B1810]', 'text-[#2B1810]'])>
    <main @class(['grid', 'min-h-screen', 'lg:grid-cols-2'])>
        <section @class(['relative', 'hidden', 'overflow-hidden', 'bg-[#3D2418]', 'lg:flex', 'lg:flex-col', 'lg:justify-between', 'lg:p-12'])>
            <div @class(['absolute', '-left-24', '-top-24', 'h-72', 'w-72', 'rounded-full', 'bg-[#8B5E3C]/20', 'blur-3xl'])></div>
            <div @class(['absolute', '-bottom-32', '-right-20', 'h-96', 'w-96', 'rounded-full', 'bg-[#C49A6C]/20', 'blur-3xl'])></div>

            
            <div @class(['relative', 'flex', 'items-center', 'gap-3', 'text-[#F5E9D3]'])>
                <div @class(['flex', 'h-9', 'w-9', 'items-center', 'justify-center', 'overflow-hidden', 'rounded-xl'])>
                    <img src="{{ asset('assets/KelanaCoffe.png') }}" alt="Kelana Coffee" @class(['h-full', 'w-full', 'object-cover'])>
                </div>
                <span @class(['text-sm', 'font-semibold', 'tracking-wide'])>Kelana Coffee</span>
            </div>

            <div @class(['relative', 'max-w-md'])>
                <p @class(['mb-4', 'text-sm', 'font-semibold', 'uppercase', 'tracking-[0.25em]', 'text-[#D9A66C]'])>
                    Coffee &amp; comfort
                </p>
                <h2 @class(['text-4xl', 'font-bold', 'leading-tight', 'text-[#F5E9D3]'])>
                    Kelola kedai dengan lebih sederhana.
                </h2>
                <p @class(['mt-5', 'text-base', 'leading-7', 'text-[#D8C3A5]'])>
                    Satu ruang kerja untuk mengatur penjualan, produk, dan persediaan Kelana Coffee.
                </p>
            </div>

            <p @class(['relative', 'text-sm', 'text-[#A78868]'])>
                © {{ now()->year }} Kelana Coffee
            </p>
        </section>

        <section @class(['flex', 'items-center', 'justify-center', 'bg-[#FAF3E7]', 'px-5', 'py-10', 'sm:px-8']) style="background-image: url('{{ asset('assets/Background.png') }}');">
            <div @class(['w-full', 'max-w-md'])>
                <div @class(['mb-8', 'flex', 'items-center', 'gap-3', 'lg:hidden'])>
                    <div @class(['flex', 'h-10', 'w-10', 'items-center', 'justify-center', 'overflow-hidden', 'rounded-xl'])>
                        <img src="{{ asset('assets/KelanaCoffe.png') }}" alt="Kelana Coffee" @class(['h-full', 'w-full', 'object-cover'])>
                    </div>
                    <span @class(['text-sm', 'font-semibold', 'tracking-wide', 'text-[#2B1810]'])>Kelana Coffee</span>
                </div>

                @if (session('status'))
                    <p @class(['mb-4', 'rounded-xl', 'bg-emerald-50', 'px-4', 'py-3', 'text-sm', 'text-emerald-700']) role="status">
                        {{ session('status') }}
                    </p>
                @endif

                <x-card title="Selamat datang kembali" description="Masuk untuk melanjutkan ke dashboard Kelana Coffee.">
                    <form method="POST" action="{{ route('login.process') }}" @class(['flex', 'flex-col', 'gap-5'])>
                        @csrf

                        <x-form-field
                            label="Email"
                            name="email"
                            type="email"
                            :value="old('email')"
                            placeholder="nama@email.com"
                            autocomplete="username"
                            required
                            autofocus
                        />

                        <x-form-field
                            label="Password"
                            name="password"
                            type="password"
                            placeholder="Masukkan password"
                            autocomplete="current-password"
                            required
                        />

                        <div class="-mt-2 flex justify-end">
                            <a href="{{ route('password.request') }}" class="text-xs font-semibold text-[#8B5E3C] underline underline-offset-2 hover:text-[#3D2418]">
                                Lupa password?
                            </a>
                        </div>

                        <label @class(['flex', 'items-center', 'gap-3', 'text-sm', 'font-medium', 'text-[#4A2D1F]'])>
                            <input type="checkbox" name="remember" value="1" @class(['h-4', 'w-4', 'rounded', 'border-[#C49A6C]', 'text-[#3D2418]', 'focus:ring-[#3D2418]'])>
                            Ingat saya
                        </label>

                        <x-button type="submit" @class(['mt-2', 'w-full'])>
                            Masuk ke Dashboard
                        </x-button>
                    </form>
                </x-card>

                <div @class(['mt-6', 'flex', 'justify-center'])>
                    <p @class(['rounded-full', 'bg-white/80', 'px-4', 'py-2', 'text-center', 'text-xs', 'font-medium', 'leading-5', 'text-[#6B4632]', 'backdrop-blur-sm'])>
                        Dengan masuk, Anda menyetujui kebijakan penggunaan Kelana Coffee.
                        Belum punya akun?
                        <a href="{{ route('register') }}" @class(['font-semibold', 'text-[#3D2418]', 'underline', 'underline-offset-2'])>Daftar di sini</a>.
                    </p>
                </div>
            </div>
        </section>
    </main>

    @stack('scripts')
</body>
</html>