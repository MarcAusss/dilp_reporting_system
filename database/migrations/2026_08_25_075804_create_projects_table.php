<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();

            $table->string('registry_number', 50)
                ->nullable()
                ->unique();

            $table->unsignedSmallInteger('fiscal_year')
                ->index();

            $table->string('project_code', 100)
                ->nullable();

            $table->string('title', 255);

            $table->foreignId('proponent_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('office_id')
                ->nullable()
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('fund_source_id')
                ->nullable()
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('project_type_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('project_purpose_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('implementation_mode_id')
                ->nullable()
                ->constrained()
                ->restrictOnDelete();

            $table->date('date_received')
                ->nullable();

            $table->string('record_status', 30)
                ->default('draft')
                ->index();

            $table->string('source_reference', 150)
                ->nullable();

            $table->text('remarks')
                ->nullable();

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
                'fiscal_year',
                'project_code',
            ]);

            $table->index([
                'fiscal_year',
                'record_status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};