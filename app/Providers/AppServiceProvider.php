<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::before(function (User $user, string $ability): ?bool {
            return $user->hasRole(UserRole::SuperAdmin->value)
                ? true
                : null;
        });

        Project::created(function (Project $project): void {
            $this->auditProject('created', 'Created project '.$project->registry_number.'.', $project, null, $project->getAttributes());
        });

        Project::updated(function (Project $project): void {
            $changes = collect($project->getChanges())
                ->except(['updated_at'])
                ->all();

            if ($changes === []) {
                return;
            }

            $old = [];
            foreach (array_keys($changes) as $key) {
                $old[$key] = $project->getRawOriginal($key);
            }

            $this->auditProject('updated', 'Updated project '.$project->registry_number.'.', $project, $old, $changes);
        });

        Project::deleted(function (Project $project): void {
            $this->auditProject('archived', 'Archived project '.$project->registry_number.'.', $project);
        });

        Project::restored(function (Project $project): void {
            $this->auditProject('restored', 'Restored project '.$project->registry_number.'.', $project);
        });

        Event::listen(Login::class, function (Login $event): void {
            if (! Schema::hasTable('audit_logs')) {
                return;
            }

            app(AuditLogService::class)->record(
                'authentication',
                'login',
                'User signed in: '.$event->user->email.'.',
                $event->user,
                userId: $event->user->getAuthIdentifier(),
            );
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if (! $event->user || ! Schema::hasTable('audit_logs')) {
                return;
            }

            app(AuditLogService::class)->record(
                'authentication',
                'logout',
                'User signed out: '.$event->user->email.'.',
                $event->user,
                userId: $event->user->getAuthIdentifier(),
            );
        });
    }

    private function auditProject(
        string $action,
        string $description,
        Project $project,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): void {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        app(AuditLogService::class)->record(
            'project',
            $action,
            $description,
            $project,
            $oldValues,
            $newValues,
        );
    }
}
