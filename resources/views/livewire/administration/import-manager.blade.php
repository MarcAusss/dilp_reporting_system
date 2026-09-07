<div class="space-y-7">
    <x-ui.page-header
        title="Legacy Data Import"
        eyebrow="Administration"
        description="Safely migrate legacy DILP project spreadsheets through upload, column mapping, dry-run validation, duplicate review, and controlled commit."
    />

    @if (session()->has('import-status'))
        <x-ui.alert variant="success" title="Legacy Import">{{ session('import-status') }}</x-ui.alert>
    @endif

    @error('import')
        <x-ui.alert variant="danger" title="Legacy Import">{{ $message }}</x-ui.alert>
    @enderror

    <div class="grid gap-6 xl:grid-cols-[0.75fr_1.25fr]">
        <x-ui.card>
            <div class="border-b border-slate-100 pb-4">
                <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-[#1e5a8a]">Step 1</p>
                <h2 class="mt-1 text-base font-bold text-slate-950">Upload Legacy Spreadsheet</h2>
                <p class="mt-1 text-xs leading-5 text-slate-500">
                    Supported formats: CSV and XLSX. The importer is create-only; it never overwrites an existing project.
                </p>
            </div>

            @can('data-imports.manage')
                <form wire:submit="uploadBatch" class="mt-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Spreadsheet File</label>
                        <input
                            type="file"
                            wire:model="upload"
                            accept=".csv,.xlsx"
                            class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-slate-700"
                        >
                        @error('upload')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        <p class="mt-2 text-[11px] leading-5 text-slate-400">Maximum {{ $maxFileMb }} MB. XLSX requires PHP ext-zip on the server.</p>
                    </div>

                    <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="uploadBatch,upload">
                        <span wire:loading.remove wire:target="uploadBatch">Upload & Read File</span>
                        <span wire:loading wire:target="uploadBatch">Reading spreadsheet...</span>
                    </x-ui.button>
                </form>
            @else
                <div class="mt-5 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                    Your account has read-only access to import history.
                </div>
            @endcan

            <div class="mt-6 rounded-xl border border-blue-100 bg-blue-50/60 p-4">
                <p class="text-xs font-bold text-[#164b73]">Safe migration workflow</p>
                <ol class="mt-2 space-y-1.5 text-xs leading-5 text-slate-600">
                    <li>1. Upload the old workbook.</li>
                    <li>2. Confirm or adjust column mapping.</li>
                    <li>3. Run dry-run validation.</li>
                    <li>4. Resolve errors and duplicates.</li>
                    <li>5. Commit only validated rows.</li>
                </ol>
            </div>
        </x-ui.card>

        <x-ui.card :padding="false">
            <div class="border-b border-slate-100 px-5 py-5 sm:px-6">
                <h2 class="text-sm font-bold text-slate-900">Import History</h2>
                <p class="mt-1 text-xs text-slate-500">Every migration batch is retained for traceability.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 sm:px-6">Batch / File</th>
                            <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">Status</th>
                            <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">Rows</th>
                            <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">Uploaded</th>
                            <th class="px-5 py-3 text-right text-[10px] font-bold uppercase tracking-wide text-slate-500 sm:px-6">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($batches as $batch)
                            <tr class="align-top hover:bg-slate-50/70">
                                <td class="min-w-[260px] px-5 py-4 sm:px-6">
                                    <p class="font-mono text-[11px] font-semibold text-[#164b73]">Batch #{{ $batch->id }}</p>
                                    <p class="mt-1 text-sm font-bold text-slate-900">{{ $batch->original_filename }}</p>
                                    <p class="mt-1 text-[11px] uppercase tracking-wide text-slate-400">{{ $batch->file_type }}</p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <x-ui.badge :variant="$batch->status->badgeVariant()">{{ $batch->status->label() }}</x-ui.badge>
                                    @if($batch->failure_message)<p class="mt-2 max-w-[220px] text-[11px] leading-4 text-red-600">{{ $batch->failure_message }}</p>@endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-xs text-slate-600">
                                    <p>{{ number_format($batch->total_rows) }} total</p>
                                    @if($batch->validated_at)
                                        <p class="mt-1 text-emerald-700">{{ number_format($batch->valid_rows + $batch->warning_rows) }} importable</p>
                                        @if($batch->error_rows)<p class="mt-1 text-red-600">{{ number_format($batch->error_rows) }} error</p>@endif
                                        @if($batch->duplicate_rows)<p class="mt-1 text-amber-700">{{ number_format($batch->duplicate_rows) }} duplicate</p>@endif
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-xs text-slate-600">
                                    <p>{{ $batch->created_at->format('M d, Y') }}</p>
                                    <p class="mt-1 text-[11px] text-slate-400">{{ $batch->uploader?->name ?? 'System' }}</p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right sm:px-6">
                                    <div class="flex justify-end gap-3">
                                        <a href="{{ route('data-imports.review', $batch) }}" class="text-xs font-bold text-[#164b73] hover:text-[#0f2a44]">Review</a>
                                        <a href="{{ route('data-imports.download', $batch) }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">Source</a>
                                        @can('data-imports.manage')
                                            @if(!in_array($batch->status, [\App\Enums\DataImportStatus::Completed, \App\Enums\DataImportStatus::Cancelled], true))
                                                <button type="button" wire:click="cancelBatch({{ $batch->id }})" wire:confirm="Cancel this import batch? Its audit record will remain." class="text-xs font-semibold text-red-600 hover:text-red-800">Cancel</button>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500 sm:px-6">No legacy import batches have been uploaded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($batches->hasPages())<div class="border-t border-slate-100 px-5 py-4 sm:px-6">{{ $batches->links() }}</div>@endif
        </x-ui.card>
    </div>
</div>
