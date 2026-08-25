<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $this->createMasterTable('offices');
        $this->createMasterTable('provinces');
        $this->createMasterTable('fund_sources');
        $this->createMasterTable('project_types');
        $this->createMasterTable('project_purposes');
        $this->createMasterTable('implementation_modes');
        $this->createMasterTable('beneficiary_sectors');
        $this->createMasterTable('livelihoods');
        $this->createMasterTable('convergence_programs');
    }

    public function down(): void
    {
        Schema::dropIfExists('convergence_programs');
        Schema::dropIfExists('livelihoods');
        Schema::dropIfExists('beneficiary_sectors');
        Schema::dropIfExists('implementation_modes');
        Schema::dropIfExists('project_purposes');
        Schema::dropIfExists('project_types');
        Schema::dropIfExists('fund_sources');
        Schema::dropIfExists('provinces');
        Schema::dropIfExists('offices');
    }

    private function createMasterTable(string $tableName): void
    {
        Schema::create($tableName, function (Blueprint $table) {
            $table->id();

            $table->string('code', 50)
                ->nullable()
                ->unique();

            $table->string('name', 150)
                ->unique();

            $table->text('description')
                ->nullable();

            $table->boolean('is_active')
                ->default(true)
                ->index();

            $table->unsignedSmallInteger('sort_order')
                ->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }
};