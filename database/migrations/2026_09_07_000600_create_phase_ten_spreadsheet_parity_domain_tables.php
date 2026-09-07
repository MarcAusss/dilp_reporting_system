<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('proponents', function (Blueprint $table): void {
            $table->string('abbreviation', 100)->nullable()->after('name');
            $table->string('head_name', 255)->nullable()->after('abbreviation');
            $table->string('organization_name', 255)->nullable()->after('head_name');
            $table->string('organization_classification', 150)->nullable()->after('organization_name');
        });

        Schema::create('project_funding_details', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('adl_nta_number', 150)->nullable();
            $table->string('fund_sponsor', 255)->nullable();
            $table->string('partylist_nga', 255)->nullable();
            $table->string('legislator', 255)->nullable();
            $table->string('lce_congressman_point_person', 255)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('actual_charging', 255)->nullable();
            $table->text('target_barangays')->nullable();
            $table->text('target_municipalities')->nullable();
            $table->decimal('fund_amount', 15, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('project_endorsements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('sequence_no')->default(1);
            $table->date('endorsement_date')->nullable();
            $table->string('reference_number', 150)->nullable();
            $table->string('endorsement_type', 100)->default('endorsement');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['project_id', 'sequence_no'], 'proj_endorsement_seq_unique');
        });

        Schema::create('project_compliance_communications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50)->default('LCOM');
            $table->string('reference_number', 150)->nullable();
            $table->date('issued_at')->nullable();
            $table->date('received_at')->nullable();
            $table->date('resolved_at')->nullable();
            $table->string('status', 50)->default('open');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['project_id', 'type', 'status'], 'proj_comms_type_status_idx');
        });

        Schema::create('project_dis_validations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('details_tally_with_dis')->nullable();
            $table->string('proposal_status', 150)->nullable();
            $table->boolean('for_updating')->default(false);
            $table->boolean('for_coordination_po')->default(false);
            $table->boolean('for_coordination_co')->default(false);
            $table->text('action_required')->nullable();
            $table->text('resolution')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('project_beneficiary_metrics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('total_beneficiaries')->nullable();
            $table->unsignedInteger('female_beneficiaries')->nullable();
            $table->unsignedInteger('male_beneficiaries')->nullable();
            $table->decimal('female_assistance_amount', 15, 2)->nullable();
            $table->string('beneficiary_type_ies', 255)->nullable();
            $table->string('source', 100)->default('legacy_spreadsheet');
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('project_moa_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('received_at')->nullable();
            $table->date('forwarded_for_signature_at')->nullable();
            $table->date('signed_copy_returned_at')->nullable();
            $table->date('notarial_slip_at')->nullable();
            $table->date('forwarded_to_notarial_at')->nullable();
            $table->date('notarized_at')->nullable();
            $table->date('notarized_copy_received_at')->nullable();
            $table->date('original_folder_forwarded_imsd_at')->nullable();
            $table->text('lacking_requirements')->nullable();
            $table->text('commitment')->nullable();
            $table->text('important_note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('project_gpai_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('beneficiaries_enrolled')->nullable();
            $table->decimal('gpai_amount', 15, 2)->nullable();
            $table->string('enrollment_status', 100)->nullable();
            $table->foreignId('fund_source_id')->nullable()->constrained('fund_sources')->nullOnDelete();
            $table->date('forwarded_for_enrollment_at')->nullable();
            $table->string('cash_advance_payee', 255)->nullable();
            $table->string('responsible_person', 255)->nullable();
            $table->date('or_policy_received_at')->nullable();
            $table->string('or_number', 150)->nullable();
            $table->date('or_date')->nullable();
            $table->string('policy_number', 150)->nullable();
            $table->string('dv_number', 150)->nullable();
            $table->date('check_date')->nullable();
            $table->string('check_number', 150)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['project_id', 'enrollment_status'], 'proj_gpai_status_idx');
        });

        Schema::create('project_stage_metrics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('stage', 80);
            $table->unsignedInteger('beneficiary_count')->nullable();
            $table->unsignedInteger('female_beneficiary_count')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->date('as_of_date')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['project_id', 'stage'], 'proj_stage_metric_idx');
        });


        Schema::create('project_sector_metrics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('beneficiary_sector_id')->nullable()->constrained('beneficiary_sectors')->nullOnDelete();
            $table->string('sector_name', 255)->nullable();
            $table->string('metric_type', 100)->default('assistance');
            $table->unsignedInteger('beneficiary_count')->nullable();
            $table->unsignedInteger('female_count')->nullable();
            $table->decimal('assistance_amount', 15, 2)->nullable();
            $table->text('beneficiary_names')->nullable();
            $table->text('beneficiary_addresses')->nullable();
            $table->text('attribute_detail')->nullable();
            $table->string('reporting_period', 100)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['project_id', 'sector_name'], 'proj_sector_metric_idx');
        });

        Schema::create('project_livelihood_metrics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('livelihood_id')->nullable()->constrained('livelihoods')->nullOnDelete();
            $table->string('livelihood_name', 255)->nullable();
            $table->unsignedInteger('beneficiary_count')->nullable();
            $table->unsignedInteger('female_count')->nullable();
            $table->decimal('assistance_amount', 15, 2)->nullable();
            $table->string('reporting_period', 100)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['project_id', 'livelihood_name'], 'proj_livelihood_metric_idx');
        });

        Schema::create('project_special_program_metrics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('program_name', 255);
            $table->string('metric_name', 255)->nullable();
            $table->unsignedInteger('beneficiary_count')->nullable();
            $table->unsignedInteger('female_count')->nullable();
            $table->unsignedInteger('association_count')->nullable();
            $table->decimal('assistance_amount', 15, 2)->nullable();
            $table->text('beneficiary_or_assistance_detail')->nullable();
            $table->string('status', 100)->nullable();
            $table->date('as_of_date')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['project_id', 'program_name'], 'proj_special_program_idx');
        });

        Schema::create('project_reporting_inclusions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('report_type', 50);
            $table->string('reporting_period', 100)->nullable();
            $table->unsignedTinyInteger('quarter')->nullable();
            $table->string('status', 100)->nullable();
            $table->date('approved_month')->nullable();
            $table->date('reported_at')->nullable();
            $table->unsignedInteger('beneficiary_count')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['project_id', 'report_type'], 'proj_report_inclusion_idx');
        });

        Schema::create('project_post_implementation_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('received_at')->nullable();
            $table->text('inclusions')->nullable();
            $table->string('are_reference', 255)->nullable();
            $table->date('check_awarding_to_proponent_at')->nullable();
            $table->date('awarded_to_beneficiaries_at')->nullable();
            $table->date('forwarded_to_supply_unit_at')->nullable();
            $table->string('status', 100)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('project_convergence_metrics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('convergence_program_id')->nullable()->constrained('convergence_programs')->nullOnDelete();
            $table->string('initiative_name', 255)->nullable();
            $table->unsignedInteger('beneficiary_count')->nullable();
            $table->unsignedInteger('female_count')->nullable();
            $table->string('beneficiary_type', 255)->nullable();
            $table->decimal('assistance_amount', 15, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['project_id', 'initiative_name'], 'proj_convergence_metric_idx');
        });

        Schema::create('project_status_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('dimension', 100);
            $table->string('status', 255)->nullable();
            $table->date('as_of_date')->nullable();
            $table->unsignedInteger('beneficiary_count')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['project_id', 'dimension'], 'proj_status_snapshot_idx');
        });

        Schema::table('project_locations', function (Blueprint $table): void {
            $table->string('district', 100)->nullable()->after('address_detail');
            $table->string('income_class', 100)->nullable()->after('district');
        });

        Schema::table('project_procurements', function (Blueprint $table): void {
            $table->string('procurement_method', 150)->nullable()->after('supplier');
            $table->boolean('is_epa')->default(false)->after('procurement_method');
        });

        Schema::table('project_replacement_requests', function (Blueprint $table): void {
            $table->unsignedInteger('beneficiaries_replaced')->nullable()->after('reason');
            $table->text('original_beneficiary')->nullable()->after('beneficiaries_replaced');
            $table->text('replacement_beneficiary')->nullable()->after('original_beneficiary');
            $table->date('approval_letter_date')->nullable()->after('replacement_beneficiary');
            $table->dateTime('released_at')->nullable()->after('approval_letter_date');
            $table->date('are_insurance_received_at')->nullable()->after('released_at');
        });

        Schema::table('project_disbursements', function (Blueprint $table): void {
            $table->date('prepared_at')->nullable()->after('payee');
            $table->date('forwarded_to_signatories_at')->nullable()->after('prepared_at');
            $table->dateTime('released_at')->nullable()->after('forwarded_to_signatories_at');
            $table->string('check_lddap_number', 150)->nullable()->after('released_at');
            $table->date('check_lddap_date')->nullable()->after('check_lddap_number');
            $table->string('dv_number', 150)->nullable()->after('check_lddap_date');
        });
    }

    public function down(): void
    {

        Schema::table('project_replacement_requests', function (Blueprint $table): void {
            $table->dropColumn(['beneficiaries_replaced','original_beneficiary','replacement_beneficiary','approval_letter_date','released_at','are_insurance_received_at']);
        });
        Schema::table('project_procurements', function (Blueprint $table): void {
            $table->dropColumn(['procurement_method','is_epa']);
        });
        Schema::table('project_locations', function (Blueprint $table): void {
            $table->dropColumn(['district','income_class']);
        });
        Schema::dropIfExists('project_status_snapshots');
        Schema::dropIfExists('project_convergence_metrics');
        Schema::dropIfExists('project_post_implementation_records');
        Schema::dropIfExists('project_reporting_inclusions');
        Schema::dropIfExists('project_special_program_metrics');
        Schema::dropIfExists('project_livelihood_metrics');
        Schema::dropIfExists('project_sector_metrics');

        Schema::table('project_disbursements', function (Blueprint $table): void {
            $table->dropColumn([
                'prepared_at', 'forwarded_to_signatories_at', 'released_at',
                'check_lddap_number', 'check_lddap_date', 'dv_number',
            ]);
        });

        Schema::dropIfExists('project_stage_metrics');
        Schema::dropIfExists('project_gpai_records');
        Schema::dropIfExists('project_moa_records');
        Schema::dropIfExists('project_beneficiary_metrics');
        Schema::dropIfExists('project_dis_validations');
        Schema::dropIfExists('project_compliance_communications');
        Schema::dropIfExists('project_endorsements');
        Schema::dropIfExists('project_funding_details');

        Schema::table('proponents', function (Blueprint $table): void {
            $table->dropColumn(['abbreviation', 'head_name', 'organization_name', 'organization_classification']);
        });
    }
};
