@props(['title', 'description' => null, 'eyebrow' => null])

<div
    {{ $attributes->merge([
        'class' => 'flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between',
    ]) }}>
    <div class="min-w-0">

        @if ($eyebrow)
            <p class="mb-1 text-[10px] font-bold uppercase tracking-[0.18em] text-[#1e5a8a]">
                {{ $eyebrow }}
            </p>
        @endif

        <h1 class="text-2xl font-bold tracking-tight text-slate-950 sm:text-[28px]">
            {{ $title }}
        </h1>

        @if ($description)
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                {{ $description }}
            </p>
        @endif
    </div>

    @isset($actions)
        <div class="flex shrink-0 items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
