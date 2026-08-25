<div class="space-y-6">

    @if (session()->has('financial-status'))
        <x-ui.alert variant="success" title="Financial details saved">
            {{ session('financial-status') }}
        </x-ui.alert>
    @endif

    {{-- Project identity --}}
    <x-ui.card>

        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_auto]">
            <div>

                <div class="flex flex-wrap items-center gap-2">

                    <span class="font-mono text-xs font-semibold text-[#164b73]">
                        {{ $project->registry_number }}
                    </span>

                    <x-ui.badge>
                        FY {{ $project->fiscal_year }}
                    </x-ui.badge>

                    @if ($project->project_code)
                        <x-ui.badge variant="info">
                            {{ $project->project_code }}
                        </x-ui.badge>
                    @endif

                </div>

                <h2 class="mt-3 text-xl font-bold tracking-tight text-slate-950">
                    {{ $project->title }}
                </h2>

                <p class="mt-2 text-sm text-slate-500">
                    {{ $project->proponent->name }}
                </p>

            </div>

            <div class="grid gap-1 text-right">

                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                    Classification
                </span>

                <span class="text-sm font-semibold text-slate-700">
                    {{ $project->projectType->name }}
                    /
                    {{ $project->projectPurpose->name }}
                </span>

            </div>
        </div>

    </x-ui.card>

    {{-- Calculated totals --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <x-ui.card>
            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">
                DOLE Share
            </p>

            <p class="mt-3 text-xl font-bold text-slate-950">
                ₱{{ number_format($doleShare, 2) }}
            </p>

            <p class="mt-1 text-xs text-slate-500">
                Materials + insurance + training
            </p>
        </x-ui.card>

        <x-ui.card>
            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">
                Total Equity
            </p>

            <p class="mt-3 text-xl font-bold text-slate-950">
                ₱{{ number_format($totalEquity, 2) }}
            </p>

            <p class="mt-1 text-xs text-slate-500">
                Proponent/partner + beneficiary
            </p>
        </x-ui.card>

        <x-ui.card>
            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">
                Total Project Cost
            </p>

            <p class="mt-3 text-xl font-bold text-[#164b73]">
                ₱{{ number_format($totalProjectCost, 2) }}
            </p>

            <p class="mt-1 text-xs text-slate-500">
                DOLE share + total equity
            </p>
        </x-ui.card>

        <x-ui.card>
            <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">
                Equity Percentage
            </p>

            <p class="mt-3 text-xl font-bold text-slate-950">
                {{ number_format($equityPercentage, 2) }}%
            </p>

            <p class="mt-1 text-xs text-slate-500">
                Safe division when cost is zero
            </p>
        </x-ui.card>

    </div>

    {{-- Form --}}
    <x-ui.card>

        <div class="flex flex-col gap-2 border-b border-slate-100 pb-5">
            <h3 class="text-base font-bold text-slate-900">
                Financial Breakdown
            </h3>

            <p class="text-sm leading-6 text-slate-500">
                Enter only the source financial amounts.
                Calculated totals are generated automatically.
            </p>
        </div>

        <form wire:submit="save" class="mt-6 space-y-8">

            {{-- DOLE Share --}}
            <section>

                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.15em] text-[#1e5a8a]">
                        DOLE Share Components
                    </p>

                    <p class="mt-1 text-xs text-slate-500">
                        Assistance funded by DOLE.
                    </p>
                </div>

                <div class="mt-5 grid gap-5 md:grid-cols-3">

                    <div>
                        <label class="block text-sm font-medium text-slate-700">
                            Equipment / Materials / Tools
                        </label>

                        <div class="relative mt-2">
                            <span
                                class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400">
                                ₱
                            </span>

                            <input type="number" min="0" step="0.01"
                                wire:model.blur="equipment_materials_tools"
                                class="
                                    block w-full rounded-lg border border-slate-300
                                    bg-white py-2.5 pl-8 pr-3.5 text-right
                                    text-sm outline-none
                                    focus:border-[#1e5a8a]
                                    focus:ring-4 focus:ring-blue-100
                                ">
                        </div>

                        @error('equipment_materials_tools')
                            <p class="mt-1.5 text-xs text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">
                            Insurance
                        </label>

                        <div class="relative mt-2">
                            <span
                                class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400">
                                ₱
                            </span>

                            <input type="number" min="0" step="0.01" wire:model.blur="insurance"
                                class="
                                    block w-full rounded-lg border border-slate-300
                                    bg-white py-2.5 pl-8 pr-3.5 text-right
                                    text-sm outline-none
                                    focus:border-[#1e5a8a]
                                    focus:ring-4 focus:ring-blue-100
                                ">
                        </div>

                        @error('insurance')
                            <p class="mt-1.5 text-xs text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">
                            Training
                        </label>

                        <div class="relative mt-2">
                            <span
                                class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400">
                                ₱
                            </span>

                            <input type="number" min="0" step="0.01" wire:model.blur="training"
                                class="
                                    block w-full rounded-lg border border-slate-300
                                    bg-white py-2.5 pl-8 pr-3.5 text-right
                                    text-sm outline-none
                                    focus:border-[#1e5a8a]
                                    focus:ring-4 focus:ring-blue-100
                                ">
                        </div>

                        @error('training')
                            <p class="mt-1.5 text-xs text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                </div>

            </section>

            {{-- Equity --}}
            <section class="border-t border-slate-100 pt-7">

                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.15em] text-[#1e5a8a]">
                        Equity
                    </p>

                    <p class="mt-1 text-xs text-slate-500">
                        Counterpart contribution to the project.
                    </p>
                </div>

                <div class="mt-5 grid gap-5 md:grid-cols-2">

                    <div>
                        <label class="block text-sm font-medium text-slate-700">
                            Proponent / Partner Equity
                        </label>

                        <div class="relative mt-2">
                            <span
                                class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400">
                                ₱
                            </span>

                            <input type="number" min="0" step="0.01"
                                wire:model.blur="proponent_partner_equity"
                                class="
                                    block w-full rounded-lg border border-slate-300
                                    bg-white py-2.5 pl-8 pr-3.5 text-right
                                    text-sm outline-none
                                    focus:border-[#1e5a8a]
                                    focus:ring-4 focus:ring-blue-100
                                ">
                        </div>

                        @error('proponent_partner_equity')
                            <p class="mt-1.5 text-xs text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">
                            Beneficiary Equity
                        </label>

                        <div class="relative mt-2">
                            <span
                                class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400">
                                ₱
                            </span>

                            <input type="number" min="0" step="0.01" wire:model.blur="beneficiary_equity"
                                class="
                                    block w-full rounded-lg border border-slate-300
                                    bg-white py-2.5 pl-8 pr-3.5 text-right
                                    text-sm outline-none
                                    focus:border-[#1e5a8a]
                                    focus:ring-4 focus:ring-blue-100
                                ">
                        </div>

                        @error('beneficiary_equity')
                            <p class="mt-1.5 text-xs text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                </div>

            </section>

            {{-- Remarks --}}
            <section class="border-t border-slate-100 pt-7">

                <label class="block text-sm font-medium text-slate-700">
                    Financial Remarks
                </label>

                <textarea rows="4" wire:model="remarks" placeholder="Optional notes regarding the financial breakdown..."
                    class="
                        mt-2 block w-full resize-y rounded-lg
                        border border-slate-300 bg-white px-3.5 py-2.5
                        text-sm outline-none
                        focus:border-[#1e5a8a]
                        focus:ring-4 focus:ring-blue-100
                    "></textarea>

                @error('remarks')
                    <p class="mt-1.5 text-xs text-red-600">
                        {{ $message }}
                    </p>
                @enderror

            </section>

            @can('project-financials.update')
                <div class="flex justify-end border-t border-slate-100 pt-6">
                    <x-ui.button type="submit">
                        Save Financial Details
                    </x-ui.button>
                </div>
            @else
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                    Your account has read-only access to financial details.
                </div>
            @endcan

        </form>

    </x-ui.card>

</div>
