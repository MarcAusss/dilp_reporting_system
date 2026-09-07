<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('data_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('original_filename', 255);
            $table->string('stored_path', 500);
            $table->string('file_type', 20)->index();
            $table->string('status', 30)->default('uploaded')->index();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('warning_rows')->default(0);
            $table->unsignedInteger('error_rows')->default(0);
            $table->unsignedInteger('duplicate_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('skipped_rows')->default(0);
            $table->json('headers')->nullable();
            $table->json('header_map')->nullable();
            $table->json('options')->nullable();
            $table->json('validation_summary')->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('committed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('data_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('data_import_batches')->cascadeOnDelete();
            $table->string('source_sheet', 150)->nullable();
            $table->unsignedInteger('row_number');
            $table->json('raw_data');
            $table->json('normalized_data')->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->json('messages')->nullable();
            $table->foreignId('duplicate_project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('imported_project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->timestamps();

            $table->unique(['batch_id', 'source_sheet', 'row_number'], 'data_import_row_source_unique');
            $table->index(['batch_id', 'status']);
        });


        Schema::create('fund_targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('fiscal_year')->index();
            $table->foreignId('fund_source_id')->constrained()->restrictOnDelete();
            $table->foreignId('office_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('scope_key', 80);
            $table->decimal('allocation_amount', 18, 2)->default(0);
            $table->unsignedInteger('target_projects')->default(0);
            $table->unsignedInteger('target_beneficiaries')->default(0);
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['fiscal_year', 'fund_source_id', 'scope_key'], 'fund_target_scope_unique');
            $table->index(['fiscal_year', 'office_id']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 50)->index();
            $table->string('action', 80)->index();
            $table->string('auditable_type', 180)->nullable()->index();
            $table->unsignedBigInteger('auditable_id')->nullable()->index();
            $table->string('description', 500);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['category', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('fund_targets');
        Schema::dropIfExists('data_import_rows');
        Schema::dropIfExists('data_import_batches');
    }
};
