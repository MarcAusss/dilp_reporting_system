<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('barangays', function (Blueprint $table) {
            $table->id();

            $table->foreignId('municipality_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('code', 50)
                ->nullable()
                ->unique();

            $table->string('name', 150);

            $table->text('description')
                ->nullable();

            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->unsignedSmallInteger('sort_order')
                ->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->unique([
                'municipality_id',
                'name',
            ]);

            $table->index([
                'municipality_id',
                'is_active',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barangays');
    }
};