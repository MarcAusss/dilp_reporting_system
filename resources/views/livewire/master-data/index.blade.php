<div class="space-y-6">

    @if (session()->has('master-data-status'))
        <x-ui.alert variant="success" title="Changes saved">
            {{ session('master-data-status') }}
        </x-ui.alert>
    @endif

    <div class="grid gap-6 xl:grid-cols-[260px_minmax(0,1fr)]">

        {{-- Categories --}}
        <x-ui.card :padding="false">
            <div class="border-b border-slate-100 px-4 py-4">
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">
                    Reference Tables
                </p>

                <p class="mt-1 text-xs leading-5 text-slate-500">
                    Select a master-data category.
                </p>
            </div>

            <nav class="space-y-1 p-2">
                @foreach ($categories as $key => $config)
                    <button type="button" wire:click="selectCategory('{{ $key }}')"
                        class="
                            flex w-full items-center justify-between rounded-lg
                            px-3 py-2.5 text-left text-sm font-medium transition
                            {{ $category === $key ? 'bg-blue-50 text-[#164b73]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}
                        ">
                        <span>
                            {{ $config['label'] }}
                        </span>

                        @if ($category === $key)
                            <span class="h-1.5 w-1.5 rounded-full bg-[#164b73]"></span>
                        @endif
                    </button>
                @endforeach
            </nav>
        </x-ui.card>

        {{-- Main --}}
        <div class="min-w-0 space-y-5">

            <x-ui.card>

                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-[#1e5a8a]">
                            Master Data
                        </p>

                        <h2 class="mt-1 text-xl font-bold text-slate-950">
                            {{ $currentCategory['label'] }}
                        </h2>

                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                            {{ $currentCategory['description'] }}
                        </p>
                    </div>

                    @can('master-data.create')
                        <x-ui.button wire:click="startCreate">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4">
                                <path d="M12 5v14M5 12h14" />
                            </svg>

                            Add Record
                        </x-ui.button>
                    @endcan
                </div>

                <div class="mt-5">
                    <div class="relative max-w-md">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400">
                            <circle cx="11" cy="11" r="7" />
                            <path d="m16.5 16.5 4 4" />
                        </svg>

                        <input type="search" wire:model.live.debounce.300ms="search"
                            placeholder="Search name or code..."
                            class="
                                w-full rounded-lg border border-slate-300
                                bg-white py-2.5 pl-9 pr-3 text-sm text-slate-900
                                outline-none transition
                                placeholder:text-slate-400
                                focus:border-[#1e5a8a]
                                focus:ring-4 focus:ring-blue-100
                            ">
                    </div>
                </div>

            </x-ui.card>

            {{-- Form --}}
            @if ($showForm)
                <x-ui.card>

                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">
                                {{ $editingId ? 'Edit Record' : 'Add Record' }}
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                {{ $currentCategory['label'] }}
                            </p>
                        </div>

                        <button type="button" wire:click="cancel"
                            class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                class="h-5 w-5">
                                <path d="m6 6 12 12M18 6 6 18" />
                            </svg>
                        </button>
                    </div>

                    <form wire:submit="save" class="mt-6 space-y-5">
                        <div class="grid gap-5 md:grid-cols-2">

                            <div>
                                <label for="master-name" class="block text-sm font-medium text-slate-700">
                                    Name
                                    <span class="text-red-500">*</span>
                                </label>

                                <input id="master-name" type="text" wire:model="name"
                                    class="
                                        mt-2 block w-full rounded-lg border
                                        border-slate-300 bg-white px-3.5 py-2.5
                                        text-sm outline-none
                                        focus:border-[#1e5a8a]
                                        focus:ring-4 focus:ring-blue-100
                                    ">

                                @error('name')
                                    <p class="mt-1.5 text-xs text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div>
                                <label for="master-code" class="block text-sm font-medium text-slate-700">
                                    Code
                                </label>

                                <input id="master-code" type="text" wire:model="code" placeholder="Optional"
                                    class="
                                        mt-2 block w-full rounded-lg border
                                        border-slate-300 bg-white px-3.5 py-2.5
                                        text-sm uppercase outline-none
                                        focus:border-[#1e5a8a]
                                        focus:ring-4 focus:ring-blue-100
                                    ">

                                @error('code')
                                    <p class="mt-1.5 text-xs text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                        </div>

                        <div>
                            <label for="master-description" class="block text-sm font-medium text-slate-700">
                                Description
                            </label>

                            <textarea id="master-description" wire:model="description" rows="3"
                                class="
                                    mt-2 block w-full resize-y rounded-lg
                                    border border-slate-300 bg-white px-3.5 py-2.5
                                    text-sm outline-none
                                    focus:border-[#1e5a8a]
                                    focus:ring-4 focus:ring-blue-100
                                "></textarea>

                            @error('description')
                                <p class="mt-1.5 text-xs text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <div class="grid gap-5 md:grid-cols-2">

                            <div>
                                <label for="master-order" class="block text-sm font-medium text-slate-700">
                                    Sort Order
                                </label>

                                <input id="master-order" type="number" min="0" wire:model="sort_order"
                                    class="
                                        mt-2 block w-full rounded-lg border
                                        border-slate-300 bg-white px-3.5 py-2.5
                                        text-sm outline-none
                                        focus:border-[#1e5a8a]
                                        focus:ring-4 focus:ring-blue-100
                                    ">

                                @error('sort_order')
                                    <p class="mt-1.5 text-xs text-red-600">
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>

                            <div>
                                <span class="block text-sm font-medium text-slate-700">
                                    Status
                                </span>

                                <label
                                    class="
                                        mt-2 flex min-h-10.5 cursor-pointer
                                        items-center gap-3 rounded-lg border
                                        border-slate-300 px-3.5
                                    ">
                                    <input type="checkbox" wire:model="is_active"
                                        class="
                                            h-4 w-4 rounded border-slate-300
                                            text-[#164b73] focus:ring-[#164b73]
                                        ">

                                    <span class="text-sm text-slate-700">
                                        Active
                                    </span>
                                </label>
                            </div>

                        </div>

                        <div class="flex items-center justify-end gap-2 border-t border-slate-100 pt-5">
                            <x-ui.button type="button" variant="secondary" wire:click="cancel">
                                Cancel
                            </x-ui.button>

                            <x-ui.button type="submit">
                                {{ $editingId ? 'Save Changes' : 'Create Record' }}
                            </x-ui.button>
                        </div>

                    </form>

                </x-ui.card>
            @endif

            {{-- Table --}}
            <x-ui.card :padding="false">

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">
                            <tr>
                                <th
                                    class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    Code
                                </th>

                                <th
                                    class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    Name
                                </th>

                                <th
                                    class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    Status
                                </th>

                                <th
                                    class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    Order
                                </th>

                                <th
                                    class="px-5 py-3 text-right text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                    Actions
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 bg-white">

                            @forelse ($items as $item)
                                <tr wire:key="master-record-{{ $category }}-{{ $item->id }}"
                                    class="transition hover:bg-slate-50/70">
                                    <td class="whitespace-nowrap px-5 py-4">
                                        @if ($item->code)
                                            <span class="font-mono text-xs font-semibold text-slate-600">
                                                {{ $item->code }}
                                            </span>
                                        @else
                                            <span class="text-xs text-slate-300">
                                                —
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-5 py-4">
                                        <p class="text-sm font-semibold text-slate-800">
                                            {{ $item->name }}
                                        </p>

                                        @if ($item->description)
                                            <p class="mt-1 max-w-lg truncate text-xs text-slate-500">
                                                {{ $item->description }}
                                            </p>
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4">
                                        <x-ui.badge :variant="$item->is_active ? 'success' : 'neutral'">
                                            {{ $item->is_active ? 'Active' : 'Inactive' }}
                                        </x-ui.badge>
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="text-xs text-slate-500">
                                            {{ $item->sort_order }}
                                        </span>
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 text-right">

                                        <div class="flex items-center justify-end gap-2">

                                            @can('master-data.update')
                                                <button type="button" wire:click="edit({{ $item->id }})"
                                                    class="
                                                        rounded-lg border border-slate-200
                                                        bg-white px-3 py-1.5 text-xs
                                                        font-semibold text-slate-600
                                                        transition hover:bg-slate-50
                                                        hover:text-slate-900
                                                    ">
                                                    Edit
                                                </button>
                                            @endcan

                                            @can('master-data.toggle')
                                                <button type="button" wire:click="toggleStatus({{ $item->id }})"
                                                    class="
                                                        rounded-lg border border-slate-200
                                                        bg-white px-3 py-1.5 text-xs
                                                        font-semibold
                                                        transition
                                                        {{ $item->is_active ? 'text-amber-600 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50' }}
                                                    ">
                                                    {{ $item->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            @endcan

                                        </div>

                                    </td>
                                </tr>

                            @empty

                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <p class="text-sm font-semibold text-slate-600">
                                            No records found
                                        </p>

                                        <p class="mt-1 text-xs text-slate-400">
                                            Add a record or change your search.
                                        </p>
                                    </td>
                                </tr>
                            @endforelse

                        </tbody>

                    </table>
                </div>

                @if ($items->hasPages())
                    <div class="border-t border-slate-100 px-5 py-4">
                        {{ $items->links() }}
                    </div>
                @endif

            </x-ui.card>

        </div>

    </div>

</div>
