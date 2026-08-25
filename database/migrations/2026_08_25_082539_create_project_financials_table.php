<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_financials', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal(
                'equipment_materials_tools',
                15,
                2
            )->default(0);

            $table->decimal(
                'insurance',
                15,
                2
            )->default(0);

            $table->decimal(
                'training',
                15,
                2
            )->default(0);

            $table->decimal(
                'proponent_partner_equity',
                15,
                2
            )->default(0);

            $table->decimal(
                'beneficiary_equity',
                15,
                2
            )->default(0);

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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_financials');
    }
};