@props([
    'label',
    'name',
    'id' => null,
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'autocomplete' => null,
    'required' => false,
    'autofocus' => false,
])

@php
    $fieldId = $id ?? $name;
    $validationErrors = $errors ?? new \Illuminate\Support\ViewErrorBag();
    $hasError = $validationErrors->has($name);
    $isPasswordField = $type === 'password';
@endphp

<div class="flex flex-col gap-2">
    <label for="{{ $fieldId }}" class="text-sm font-semibold text-slate-700">
        {{ $label }}
        @if ($required)
            <span class="text-rose-500" aria-hidden="true">*</span>
        @endif
    </label>

    @if ($isPasswordField)
        <div class="relative">
            <x-input
                :type="$type"
                :id="$fieldId"
                :name="$name"
                :value="$value"
                :placeholder="$placeholder"
                :autocomplete="$autocomplete"
                :required="$required"
                :autofocus="$autofocus"
                class="pr-12"
                :aria-invalid="$hasError ? 'true' : 'false'"
                :aria-describedby="$hasError ? $fieldId . '-error' : null"
            />

            <button
                type="button"
                data-password-toggle="{{ $fieldId }}"
                class="absolute inset-y-0 right-3 flex items-center justify-center text-[#5C3523] transition hover:text-[#2B1810] focus:outline-none focus:ring-2 focus:ring-[#3D2418]/20"
                aria-label="Lihat password"
                title="Lihat password"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
            </button>
        </div>
    @else
        <x-input
            :type="$type"
            :id="$fieldId"
            :name="$name"
            :value="$value"
            :placeholder="$placeholder"
            :autocomplete="$autocomplete"
            :required="$required"
            :autofocus="$autofocus"
            :aria-invalid="$hasError ? 'true' : 'false'"
            :aria-describedby="$hasError ? $fieldId . '-error' : null"
        />
    @endif

    @if ($hasError)
        <p id="{{ $fieldId }}-error" class="text-sm text-rose-600" role="alert">
            {{ $validationErrors->first($name) }}
        </p>
    @endif
</div>
