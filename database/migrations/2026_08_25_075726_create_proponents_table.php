<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('proponents', function (Blueprint $table) {
            $table->id();

            $table->string('type', 30)
                ->index();

            $table->string('name', 200)
                ->index();

            $table->string('contact_person', 150)
                ->nullable();

            $table->string('contact_number', 50)
                ->nullable();

            $table->string('email', 150)
                ->nullable();

            $table->text('address')
                ->nullable();

            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proponents');
    }
};