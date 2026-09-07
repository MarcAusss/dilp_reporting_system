<div class="space-y-6">
    @if (session()->has('document-status'))
        <x-ui.alert variant="success" title="Project Documents">{{ session('document-status') }}</x-ui.alert>
    @endif

    <x-projects.identity-card :project="$project" />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.card><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Requirements</p><p class="mt-3 text-2xl font-bold text-slate-950">{{ number_format($summary['total']) }}</p><p class="mt-1 text-xs text-slate-500">Document records maintained</p></x-ui.card>
        <x-ui.card><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Verified</p><p class="mt-3 text-2xl font-bold text-emerald-700">{{ number_format($summary['verified']) }}</p><p class="mt-1 text-xs text-slate-500">Verified supporting documents</p></x-ui.card>
        <x-ui.card><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Missing</p><p class="mt-3 text-2xl font-bold text-red-700">{{ number_format($summary['missing']) }}</p><p class="mt-1 text-xs text-slate-500">Requirements without submission</p></x-ui.card>
        <x-ui.card><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">Past Due</p><p class="mt-3 text-2xl font-bold text-amber-700">{{ number_format($summary['overdue']) }}</p><p class="mt-1 text-xs text-slate-500">Unverified requirements past due</p></x-ui.card>
    </div>

    @can('project-documents.update')
        <div class="flex justify-end">
            <x-ui.button wire:click="startDocument">Add Document Requirement</x-ui.button>
        </div>
    @endcan

    @if ($showForm)
        <x-ui.card>
            <div class="border-b border-slate-100 pb-4">
                <h3 class="text-sm font-bold text-slate-900">{{ $editingId ? 'Edit Document' : 'Add Document Requirement' }}</h3>
                <p class="mt-1 text-xs text-slate-500">A requirement can exist without a file so missing/pending documents remain visible in compliance tracking.</p>
            </div>

            <div class="mt-5 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Category</label>
                    <select wire:model="category" class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#1e5a8a] focus:ring-4 focus:ring-blue-100">
                        @foreach ($categories as $option)<option value="{{ $option->value }}">{{ $option->label() }}</option>@endforeach
                    </select>
                    @error('category')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-1 xl:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Document / Requirement Name</label>
                    <input type="text" wire:model="name" class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#1e5a8a] focus:ring-4 focus:ring-blue-100" placeholder="e.g. Approved project proposal">
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Reference Number</label>
                    <input type="text" wire:model="reference_number" class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#1e5a8a] focus:ring-4 focus:ring-blue-100">
                    @error('reference_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Document Date</label>
                    <input type="date" wire:model="document_date" class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#1e5a8a] focus:ring-4 focus:ring-blue-100">
                    @error('document_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Due Date</label>
                    <input type="date" wire:model="due_date" class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#1e5a8a] focus:ring-4 focus:ring-blue-100">
                    @error('due_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Status</label>
                    <select wire:model="status" class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#1e5a8a] focus:ring-4 focus:ring-blue-100">
                        @foreach ($statuses as $option)<option value="{{ $option->value }}">{{ $option->label() }}</option>@endforeach
                    </select>
                    @error('status')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-slate-700">Private Attachment</label>
                    <input type="file" wire:model="upload" accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.jpg,.jpeg,.png" class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-600">
                    <p class="mt-1 text-[11px] text-slate-400">PDF, Word, Excel/CSV, JPG or PNG. Maximum 20 MB. Files are stored on the private disk.</p>
                    @error('upload')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2 xl:col-span-3">
                    <label class="block text-sm font-medium text-slate-700">Remarks</label>
                    <textarea wire:model="remarks" rows="3" class="mt-2 block w-full resize-y rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#1e5a8a] focus:ring-4 focus:ring-blue-100"></textarea>
                    @error('remarks')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mt-5 flex flex-wrap justify-end gap-2">
                <x-ui.button variant="secondary" wire:click="cancelDocument">Cancel</x-ui.button>
                <x-ui.button wire:click="saveDocument" wire:loading.attr="disabled">Save Document</x-ui.button>
            </div>
        </x-ui.card>
    @endif

    <x-ui.card :padding="false">
        <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
            <h3 class="text-sm font-bold text-slate-900">Document Register</h3>
            <p class="mt-1 text-xs text-slate-500">All documentary requirements and supporting attachments linked to this project.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50"><tr>
                    <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500 sm:px-6">Requirement</th>
                    <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">Status</th>
                    <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">Dates</th>
                    <th class="px-5 py-3 text-left text-[10px] font-bold uppercase tracking-wide text-slate-500">Attachment</th>
                    <th class="px-5 py-3 text-right text-[10px] font-bold uppercase tracking-wide text-slate-500 sm:px-6">Action</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($documents as $document)
                        <tr class="align-top hover:bg-slate-50/70">
                            <td class="min-w-[280px] px-5 py-4 sm:px-6">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-[#164b73]">{{ $document->category->label() }}</p>
                                <p class="mt-1 text-sm font-bold text-slate-900">{{ $document->name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $document->reference_number ?: 'No reference number' }}</p>
                                @if ($document->remarks)<p class="mt-2 text-xs leading-5 text-slate-500">{{ $document->remarks }}</p>@endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                <x-ui.badge :variant="$document->isOverdue() ? 'danger' : $document->status->badgeVariant()">
                                    {{ $document->isOverdue() ? 'Past Due' : $document->status->label() }}
                                </x-ui.badge>
                                @if ($document->verified_at)<p class="mt-2 text-[11px] text-slate-400">Verified {{ $document->verified_at->format('M d, Y') }}</p>@endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-xs text-slate-600">
                                <p><span class="text-slate-400">Document:</span> {{ $document->document_date?->format('M d, Y') ?? '—' }}</p>
                                <p class="mt-1"><span class="text-slate-400">Due:</span> {{ $document->due_date?->format('M d, Y') ?? '—' }}</p>
                            </td>
                            <td class="min-w-[220px] px-5 py-4 text-xs text-slate-600">
                                @if ($document->hasFile())
                                    <a href="{{ route('projects.documents.download', [$project, $document]) }}" class="font-semibold text-[#164b73] hover:text-[#0f2a44]">{{ $document->original_name ?: 'Download attachment' }}</a>
                                    <p class="mt-1 text-[11px] text-slate-400">{{ $document->mime_type ?: 'File' }} @if($document->file_size) · {{ number_format($document->file_size / 1024, 1) }} KB @endif</p>
                                @else
                                    <span class="text-slate-400">No file attached</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-right sm:px-6">
                                @can('project-documents.update')
                                    <button type="button" wire:click="editDocument({{ $document->id }})" class="text-xs font-bold text-[#164b73] hover:text-[#0f2a44]">Edit</button>
                                    <button type="button" wire:click="deleteDocument({{ $document->id }})" wire:confirm="Remove this document record and its stored attachment?" class="ml-3 text-xs font-bold text-red-600 hover:text-red-800">Remove</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500 sm:px-6">No project documents or requirements have been recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
