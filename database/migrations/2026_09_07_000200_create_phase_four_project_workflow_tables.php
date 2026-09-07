<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_workflow_states', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('stage', 40)->index();
            $table->string('status', 30)->index();
            $table->string('assigned_role', 50)->nullable()->index();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_action_at')->nullable();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['assigned_role', 'status']);
            $table->index(['stage', 'status']);
        });

        Schema::create('project_workflow_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('from_stage', 40)->nullable();
            $table->string('from_status', 30)->nullable();
            $table->string('to_stage', 40);
            $table->string('to_status', 30);
            $table->string('action', 50)->index();
            $table->string('assigned_role', 50)->nullable()->index();
            $table->string('reference_number', 150)->nullable();
            $table->text('remarks')->nullable();

            $table->foreignId('acted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('acted_at')->index();
            $table->timestamps();

            $table->index(['project_id', 'acted_at']);
            $table->index(['to_stage', 'to_status']);
        });

        $now = now();

        DB::table('projects')
            ->select([
                'id',
                'created_by',
                'created_at',
            ])
            ->orderBy('id')
            ->chunkById(250, function ($projects) use ($now): void {
                foreach ($projects as $project) {
                    $actedAt = $project->created_at ?? $now;

                    DB::table('project_workflow_states')->insert([
                        'project_id' => $project->id,
                        'stage' => 'evaluation',
                        'status' => 'pending',
                        'assigned_role' => 'dilp_coordinator',
                        'started_at' => $actedAt,
                        'last_action_at' => $actedAt,
                        'updated_by' => $project->created_by,
                        'created_at' => $actedAt,
                        'updated_at' => $actedAt,
                    ]);

                    DB::table('project_workflow_events')->insert([
                        'project_id' => $project->id,
                        'from_stage' => null,
                        'from_status' => null,
                        'to_stage' => 'evaluation',
                        'to_status' => 'pending',
                        'action' => 'registered',
                        'assigned_role' => 'dilp_coordinator',
                        'reference_number' => null,
                        'remarks' => null,
                        'acted_by' => $project->created_by,
                        'acted_at' => $actedAt,
                        'created_at' => $actedAt,
                        'updated_at' => $actedAt,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_workflow_events');
        Schema::dropIfExists('project_workflow_states');
    }
};
