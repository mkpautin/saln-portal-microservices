<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saln_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique();

            $table->string('compliance_type');
            $table->date('compliance_date')->nullable();
            $table->unsignedSmallInteger('compliance_year')->nullable();

            $table->json('declarant');
            $table->json('spouse');
            $table->string('filing_type');

            $table->json('additional_spouses')->nullable();
            $table->json('children')->nullable();
            $table->json('real_properties')->nullable();
            $table->json('personal_properties')->nullable();
            $table->json('liabilities')->nullable();

            $table->decimal('total_assets', 15, 2)->default(0);
            $table->decimal('total_liabilities', 15, 2)->default(0);
            $table->decimal('net_worth', 15, 2)->default(0);

            $table->json('business_interests')->nullable();
            $table->json('relatives_in_government_service')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saln_forms');
    }
};
