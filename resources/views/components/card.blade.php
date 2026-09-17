@props([
    'title' => null,
    'description' => null,
])

<section {{ $attributes->merge(['class' => 'rounded-3xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/50 sm:p-8']) }}>
    @if ($title || $description)
        <header class="mb-8 flex flex-col gap-2">
            @if ($title)
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                    {{ $title }}
                </h1>
            @endif

            @if ($description)
                <p class="text-sm leading-6 text-slate-500">
                    {{ $description }}
                </p>
            @endif
        </header>
    @endif

    {{ $slot }}
</section>
