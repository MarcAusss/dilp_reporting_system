<?php

namespace App\Http\Controllers\Administration;

use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Services\SpreadsheetParityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SpreadsheetParityExportController extends Controller
{
    public function __invoke(
        Request $request,
        SpreadsheetParityService $service
    ): StreamedResponse {
        Gate::authorize(PermissionName::SpreadsheetParityView->value);

        $fields = $service->filteredFields(
            $request->string('search')->toString(),
            $request->string('sheet')->toString(),
            $request->string('coverage')->toString(),
            $request->string('domain')->toString(),
        );

        return response()->streamDownload(
            function () use ($fields): void {
                $handle = fopen('php://output', 'wb');

                fputcsv($handle, [
                    'Worksheet',
                    'Column',
                    'Group',
                    'Header',
                    'Kind',
                    'Domain',
                    'Coverage',
                    'Current System Destination',
                    'Target Module',
                    'Target Phase',
                ]);

                foreach ($fields as $field) {
                    fputcsv($handle, [
                        $field['sheet'],
                        $field['column'],
                        $field['group'],
                        $field['header'],
                        $field['kind'],
                        $field['domain'],
                        $field['coverage'],
                        $field['current_destination'],
                        $field['target_module'],
                        $field['target_phase'],
                    ]);
                }

                fclose($handle);
            },
            'DILP_Spreadsheet_Parity_Audit.csv',
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]
        );
    }
}
