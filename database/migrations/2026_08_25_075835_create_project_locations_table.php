<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_locations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('province_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('municipality_id')
                ->nullable()
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('barangay_id')
                ->nullable()
                ->constrained()
                ->restrictOnDelete();

            $table->string('address_detail', 255)
                ->nullable();

            $table->boolean('is_primary')
                ->default(true)
                ->index();

            $table->timestamps();

            $table->index([
                'project_id',
                'is_primary',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_locations');
    }
};