<?php

namespace App\Services;

use Illuminate\Support\Collection;
use RuntimeException;

class SpreadsheetParityService
{
    private ?array $manifest = null;

    public function manifest(): array
    {
        if ($this->manifest !== null) {
            return $this->manifest;
        }

        $path = resource_path('data/dilp_spreadsheet_parity.json');

        if (! is_file($path)) {
            throw new RuntimeException('DILP spreadsheet parity manifest is missing.');
        }

        $decoded = json_decode(
            file_get_contents($path),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        return $this->manifest = $decoded;
    }

    public function workbook(): array
    {
        return $this->manifest()['workbook'];
    }

    public function summary(): array
    {
        return $this->manifest()['summary'];
    }

    public function sheets(): Collection
    {
        return collect($this->manifest()['sheets']);
    }

    public function fields(): Collection
    {
        return collect($this->manifest()['fields']);
    }

    public function domains(): Collection
    {
        return $this->fields()
            ->pluck('domain')
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    public function filteredFields(
        ?string $search = null,
        ?string $sheet = null,
        ?string $coverage = null,
        ?string $domain = null,
    ): Collection {
        $search = mb_strtolower(trim((string) $search));

        return $this->fields()
            ->when(
                filled($sheet),
                fn (Collection $fields) => $fields->where('sheet', $sheet)
            )
            ->when(
                filled($coverage),
                fn (Collection $fields) => $fields->where('coverage', $coverage)
            )
            ->when(
                filled($domain),
                fn (Collection $fields) => $fields->where('domain', $domain)
            )
            ->when(
                filled($search),
                fn (Collection $fields) => $fields->filter(
                    function (array $field) use ($search): bool {
                        $haystack = mb_strtolower(implode(' ', [
                            $field['sheet'] ?? '',
                            $field['column'] ?? '',
                            $field['group'] ?? '',
                            $field['header'] ?? '',
                            $field['domain'] ?? '',
                            $field['current_destination'] ?? '',
                            $field['target_module'] ?? '',
                            $field['target_phase'] ?? '',
                        ]));

                        return str_contains($haystack, $search);
                    }
                )
            )
            ->values();
    }

    public function workingSheetFieldCount(): int
    {
        $workingSheet = $this->workbook()['working_sheet'];

        return $this->fields()
            ->where('sheet', $workingSheet)
            ->count();
    }

    public function hasUnclassifiedFields(): bool
    {
        return $this->fields()
            ->contains(fn (array $field): bool => ($field['domain'] ?? '') === 'unclassified');
    }
}
