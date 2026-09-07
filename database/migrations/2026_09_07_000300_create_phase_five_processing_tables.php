<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_procurements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('reference_number', 150)->nullable();
            $table->string('description');
            $table->string('supplier', 255)->nullable();
            $table->date('procurement_date')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('status', 40)->default('planned')->index();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'status']);
        });

        Schema::create('project_obligations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('obligation_number', 150)->nullable();
            $table->date('obligation_date')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('status', 30)->default('pending')->index();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'status']);
        });

        Schema::create('project_disbursements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('disbursement_number', 150)->nullable();
            $table->string('payment_reference', 150)->nullable();
            $table->string('payee', 255)->nullable();
            $table->date('disbursement_date')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('status', 30)->default('for_payment')->index();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'status']);
        });

        Schema::create('project_insurances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 255)->nullable();
            $table->string('policy_number', 150)->nullable();
            $table->date('coverage_start')->nullable();
            $table->date('coverage_end')->nullable();
            $table->decimal('premium_amount', 15, 2)->default(0);
            $table->decimal('covered_amount', 15, 2)->default(0);
            $table->string('status', 30)->default('pending')->index();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'status']);
        });

        Schema::create('project_implementations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('start_date')->nullable();
            $table->date('target_completion_date')->nullable();
            $table->date('actual_completion_date')->nullable();
            $table->unsignedTinyInteger('accomplishment_percentage')->default(0);
            $table->string('status', 30)->default('not_started')->index();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('project_replacement_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('reference_number', 150)->nullable();
            $table->string('item_description');
            $table->text('reason');
            $table->decimal('requested_amount', 15, 2)->default(0);
            $table->date('request_date')->nullable();
            $table->date('resolution_date')->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_replacement_requests');
        Schema::dropIfExists('project_implementations');
        Schema::dropIfExists('project_insurances');
        Schema::dropIfExists('project_disbursements');
        Schema::dropIfExists('project_obligations');
        Schema::dropIfExists('project_procurements');
    }
};
