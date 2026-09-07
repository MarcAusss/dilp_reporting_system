<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('category', 40)->index();
            $table->string('name');
            $table->string('reference_number', 150)->nullable();
            $table->date('document_date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->string('file_disk', 40)->default('local');
            $table->string('file_path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'category']);
            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'due_date']);
        });

        Schema::create('project_monitoring_visits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('visit_type', 40)->index();
            $table->date('visit_date');
            $table->string('status', 30)->default('scheduled')->index();
            $table->string('conducted_by_name')->nullable();
            $table->string('location')->nullable();
            $table->unsignedTinyInteger('accomplishment_percentage')->default(0);
            $table->text('observations')->nullable();
            $table->text('recommendations')->nullable();
            $table->date('next_monitoring_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'visit_date']);
            $table->index(['project_id', 'next_monitoring_date']);
        });

        Schema::create('project_monitoring_findings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('monitoring_visit_id')->nullable()->constrained('project_monitoring_visits')->nullOnDelete();
            $table->text('finding');
            $table->text('corrective_action')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status', 30)->default('open')->index();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'due_date']);
        });

        Schema::create('project_monitoring_follow_ups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('monitoring_finding_id')->constrained('project_monitoring_findings')->cascadeOnDelete();
            $table->date('follow_up_date');
            $table->text('notes');
            $table->date('next_follow_up_date')->nullable();
            $table->string('status_after', 30)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['monitoring_finding_id', 'follow_up_date'], 'pmfu_finding_date_idx');
            $table->index('next_follow_up_date');
        });

        Schema::create('project_compliance_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('report_type', 120);
            $table->string('period_label', 120)->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->unsignedTinyInteger('accomplishment_percentage')->nullable();
            $table->foreignId('document_id')->nullable()->constrained('project_documents')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_compliance_reports');
        Schema::dropIfExists('project_monitoring_follow_ups');
        Schema::dropIfExists('project_monitoring_findings');
        Schema::dropIfExists('project_monitoring_visits');
        Schema::dropIfExists('project_documents');
    }
};
