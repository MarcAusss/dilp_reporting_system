<?php

namespace App\Livewire\Projects;

use App\Enums\PermissionName;
use App\Enums\ProjectDocumentCategory;
use App\Enums\ProjectDocumentStatus;
use App\Models\Project;
use App\Models\ProjectDocument;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class DocumentDetails extends Component
{
    use WithFileUploads;

    public int $projectId;
    public bool $showForm = false;
    public ?int $editingId = null;

    public string $category = 'proposal';
    public string $name = '';
    public string $reference_number = '';
    public string $document_date = '';
    public string $due_date = '';
    public string $status = 'pending';
    public string $remarks = '';
    public $upload = null;

    public function mount(int $projectId): void
    {
        Gate::authorize(PermissionName::ProjectDocumentsView->value);
        Project::query()->findOrFail($projectId);
        $this->projectId = $projectId;
    }

    public function startDocument(): void
    {
        $this->authorizeUpdate();
        $this->resetForm();
        $this->showForm = true;
    }

    public function editDocument(int $id): void
    {
        $this->authorizeUpdate();

        $document = ProjectDocument::query()
            ->where('project_id', $this->projectId)
            ->findOrFail($id);

        $this->editingId = $document->id;
        $this->category = $document->category->value;
        $this->name = $document->name;
        $this->reference_number = $document->reference_number ?? '';
        $this->document_date = $document->document_date?->format('Y-m-d') ?? '';
        $this->due_date = $document->due_date?->format('Y-m-d') ?? '';
        $this->status = $document->status->value;
        $this->remarks = $document->remarks ?? '';
        $this->upload = null;
        $this->showForm = true;
    }

    public function saveDocument(): void
    {
        $this->authorizeUpdate();

        $validated = $this->validate([
            'category' => ['required', Rule::enum(ProjectDocumentCategory::class)],
            'name' => ['required', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:150'],
            'document_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(ProjectDocumentStatus::class)],
            'remarks' => ['nullable', 'string', 'max:3000'],
            'upload' => [
                'nullable', 'file', 'max:20480',
                'mimes:pdf,doc,docx,xls,xlsx,csv,jpg,jpeg,png',
            ],
        ]);

        $document = $this->editingId
            ? ProjectDocument::query()->where('project_id', $this->projectId)->findOrFail($this->editingId)
            : new ProjectDocument([
                'project_id' => $this->projectId,
                'created_by' => auth()->id(),
            ]);

        if (
            $validated['status'] === ProjectDocumentStatus::Verified->value
            && ! $this->upload
            && ! $document->hasFile()
        ) {
            throw ValidationException::withMessages([
                'upload' => 'A file must be attached before a document can be marked as verified.',
            ]);
        }

        $oldDisk = $document->file_disk;
        $oldPath = $document->file_path;

        if ($this->upload) {
            $extension = strtolower($this->upload->getClientOriginalExtension() ?: 'bin');
            $storedName = Str::uuid()->toString().'.'.$extension;
            $path = $this->upload->storeAs(
                'project-documents/'.$this->projectId,
                $storedName,
                'local'
            );

            if (! $path) {
                throw ValidationException::withMessages([
                    'upload' => 'The document file could not be stored.',
                ]);
            }

            $document->file_disk = 'local';
            $document->file_path = $path;
            $document->original_name = $this->upload->getClientOriginalName();
            $document->mime_type = $this->upload->getMimeType();
            $document->file_size = $this->upload->getSize();
        }

        $status = ProjectDocumentStatus::from($validated['status']);
        $submittedAt = $document->submitted_at;
        $verifiedAt = $document->verified_at;
        $verifiedBy = $document->verified_by;

        if (in_array($status, [ProjectDocumentStatus::Submitted, ProjectDocumentStatus::Verified], true)) {
            $submittedAt ??= now();
        }

        if ($status === ProjectDocumentStatus::Verified) {
            $verifiedAt = now();
            $verifiedBy = auth()->id();
        } else {
            $verifiedAt = null;
            $verifiedBy = null;
        }

        $document->fill([
            'category' => $validated['category'],
            'name' => trim($validated['name']),
            'reference_number' => $this->nullableTrim($validated['reference_number'] ?? null),
            'document_date' => $validated['document_date'] ?: null,
            'due_date' => $validated['due_date'] ?: null,
            'status' => $status,
            'submitted_at' => $submittedAt,
            'verified_at' => $verifiedAt,
            'verified_by' => $verifiedBy,
            'remarks' => $this->nullableTrim($validated['remarks'] ?? null),
            'updated_by' => auth()->id(),
        ])->save();

        if ($this->upload && $oldPath && $oldPath !== $document->file_path) {
            Storage::disk($oldDisk ?: 'local')->delete($oldPath);
        }

        $this->resetForm();
        session()->flash('document-status', 'Project document saved successfully.');
    }

    public function deleteDocument(int $id): void
    {
        $this->authorizeUpdate();

        $document = ProjectDocument::query()
            ->where('project_id', $this->projectId)
            ->findOrFail($id);

        if ($document->hasFile()) {
            Storage::disk($document->file_disk ?: 'local')->delete($document->file_path);
        }

        $document->delete();
        $this->resetForm();
        session()->flash('document-status', 'Project document removed.');
    }

    public function cancelDocument(): void
    {
        $this->resetForm();
    }

    public function render(): View
    {
        Gate::authorize(PermissionName::ProjectDocumentsView->value);

        $project = Project::query()
            ->with([
                'proponent',
                'documents' => fn ($query) => $query->with('verifier')->orderBy('category')->orderBy('name'),
            ])
            ->findOrFail($this->projectId);

        $documents = $project->documents;

        return view('livewire.projects.document-details', [
            'project' => $project,
            'documents' => $documents,
            'categories' => ProjectDocumentCategory::cases(),
            'statuses' => ProjectDocumentStatus::cases(),
            'summary' => [
                'total' => $documents->count(),
                'verified' => $documents->where('status', ProjectDocumentStatus::Verified)->count(),
                'missing' => $documents->where('status', ProjectDocumentStatus::Missing)->count(),
                'overdue' => $documents->filter(fn (ProjectDocument $record): bool => $record->isOverdue())->count(),
            ],
        ]);
    }

    private function authorizeUpdate(): void
    {
        Gate::authorize(PermissionName::ProjectDocumentsUpdate->value);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->category = ProjectDocumentCategory::Proposal->value;
        $this->name = '';
        $this->reference_number = '';
        $this->document_date = '';
        $this->due_date = '';
        $this->status = ProjectDocumentStatus::Pending->value;
        $this->remarks = '';
        $this->upload = null;
        $this->showForm = false;
        $this->resetValidation();
    }

    private function nullableTrim(?string $value): ?string
    {
        return filled($value) ? trim($value) : null;
    }
}
