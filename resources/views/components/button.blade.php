@props([
    'type' => 'button',
    'variant' => 'primary',
    'loadingText' => 'Memproses...',
])

@php
    $variantClasses = match ($variant) {
        'secondary' => 'border border-[#D8C3A5] bg-white text-[#3D2418] hover:border-[#8B5E3C] hover:bg-[#FAF3E7]',
        'danger' => 'bg-rose-600 text-white hover:bg-rose-700 focus:ring-rose-600/20',
        default => 'bg-[#3D2418] text-[#F5E9D3] hover:bg-[#2B1810] focus:ring-[#8B5E3C]/30 disabled:bg-[#3D2418] disabled:text-[#F5E9D3]/70',
    };
@endphp

<button
    type="{{ $type }}"
    data-loading-button
    data-loading-text="{{ $loadingText }}"
    {{ $attributes->merge([
        'class' => "appearance-none relative inline-flex items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold shadow-sm outline-none transition-all duration-150 ease-out focus:ring-4 active:scale-[0.97] active:shadow-none disabled:cursor-not-allowed disabled:opacity-100 disabled:active:scale-100 {$variantClasses}",
    ]) }}
>
    <svg
        data-loading-spinner
        class="hidden h-4 w-4 animate-spin"
        viewBox="0 0 24 24"
        fill="none"
    >
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
    </svg>

    <span data-loading-label>{{ $slot }}</span>
</button>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const setLoading = (button, isLoading) => {
                    const spinner = button.querySelector('[data-loading-spinner]');
                    const label = button.querySelector('[data-loading-label]');

                    button.disabled = isLoading;
                    spinner?.classList.toggle('hidden', !isLoading);

                    if (!label) return;

                    if (isLoading) {
                        // Simpan teks asli sekali saja, biar gak ketiban kalau ke-trigger 2x
                        if (!label.dataset.originalText) {
                            label.dataset.originalText = label.textContent.trim();
                        }
                        label.textContent = button.dataset.loadingText || 'Memproses...';
                    } else if (label.dataset.originalText) {
                        label.textContent = label.dataset.originalText;
                    }
                };

                // Submit button di dalam <form> → tunggu event 'submit' (respect validasi HTML5)
                document.addEventListener('submit', (e) => {
                    const submitButton = e.target.querySelector('[data-loading-button][type="submit"]');
                    if (submitButton) setLoading(submitButton, true);
                });

                // Button biasa (type="button") → langsung react ke klik
                document.addEventListener('click', (e) => {
                    const button = e.target.closest('[data-loading-button][type="button"]');
                    if (button) setLoading(button, true);
                });
            });
        </script>
    @endpush
@endonce