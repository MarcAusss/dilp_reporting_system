<?php

namespace App\Http\Controllers;

use App\Enums\PermissionName;
use App\Models\Project;
use App\Models\ProjectDocument;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectDocumentController extends Controller
{
    public function download(Project $project, ProjectDocument $document): StreamedResponse
    {
        Gate::authorize(PermissionName::ProjectDocumentsView->value);

        abort_unless($document->project_id === $project->id, 404);
        abort_unless($document->hasFile(), 404);

        $disk = Storage::disk($document->file_disk ?: 'local');
        abort_unless($disk->exists($document->file_path), 404);

        return $disk->download(
            $document->file_path,
            $document->original_name ?: basename($document->file_path)
        );
    }
}
