<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_beneficiaries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('reference_number', 100)
                ->nullable();

            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100);
            $table->string('suffix', 30)->nullable();
            $table->string('sex', 20)->nullable()->index();
            $table->date('birth_date')->nullable();
            $table->string('contact_number', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->text('remarks')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique([
                'project_id',
                'reference_number',
            ]);

            $table->index([
                'project_id',
                'last_name',
                'first_name',
            ]);
        });

        Schema::create('project_beneficiary_sector', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_beneficiary_id')
                ->constrained('project_beneficiaries')
                ->cascadeOnDelete();

            $table->foreignId('beneficiary_sector_id')
                ->constrained()
                ->restrictOnDelete();

            $table->timestamps();

            $table->unique([
                'project_beneficiary_id',
                'beneficiary_sector_id',
            ], 'project_beneficiary_sector_unique');
        });

        Schema::create('project_livelihoods', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('livelihood_id')
                ->constrained()
                ->restrictOnDelete();

            $table->boolean('is_primary')
                ->default(false)
                ->index();

            $table->unsignedInteger('target_beneficiaries')
                ->default(0);

            $table->text('description')->nullable();
            $table->text('remarks')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique([
                'project_id',
                'livelihood_id',
            ]);
        });

        Schema::create('project_budget_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('component', 50)->index();
            $table->string('item_description', 255);
            $table->decimal('quantity', 12, 2)->default(1);
            $table->string('unit', 50)->nullable();
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('remarks')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index([
                'project_id',
                'component',
            ]);
        });

        Schema::create('project_convergences', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('convergence_program_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('reference_number', 100)->nullable();
            $table->text('assistance_description')->nullable();
            $table->decimal('assistance_amount', 15, 2)->default(0);
            $table->date('assistance_date')->nullable();
            $table->text('remarks')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index([
                'project_id',
                'convergence_program_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_convergences');
        Schema::dropIfExists('project_budget_items');
        Schema::dropIfExists('project_livelihoods');
        Schema::dropIfExists('project_beneficiary_sector');
        Schema::dropIfExists('project_beneficiaries');
    }
};
