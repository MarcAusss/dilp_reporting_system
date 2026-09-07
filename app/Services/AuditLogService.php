<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    public function record(
        string $category,
        string $action,
        string $description,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null,
    ): AuditLog {
        $runningInConsole = app()->runningInConsole();
        $request = $runningInConsole ? null : request();

        return AuditLog::query()->create([
            'user_id' => $userId ?? auth()->id(),
            'category' => $category,
            'action' => $action,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $runningInConsole ? null : $request?->ip(),
            'user_agent' => $runningInConsole ? null : $request?->userAgent(),
            'created_at' => now(),
        ]);
    }
}
