<?php

namespace App\Http\Controllers\Administration;

use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Models\DataImportBatch;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportFileController extends Controller
{
    public function download(DataImportBatch $batch): StreamedResponse
    {
        Gate::authorize(PermissionName::DataImportsView->value);
        abort_unless(Storage::disk('local')->exists($batch->stored_path), 404);

        return Storage::disk('local')->download(
            $batch->stored_path,
            $batch->original_filename
        );
    }
}
