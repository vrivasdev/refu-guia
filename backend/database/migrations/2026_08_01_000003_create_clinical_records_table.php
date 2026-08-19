<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pet_id')->constrained('pets')->onDelete('cascade');
            $table->text('trauma_notes')->nullable();
            $table->string('nutritional_status')->default('Estable');
            $table->json('ptsd_symptoms')->nullable();
            $table->json('vaccines_applied')->nullable();
            $table->string('deworming_status')->default('Al día');
            $table->string('chronic_medication')->nullable();
            $table->string('critical_drug_administered')->nullable();
            $table->boolean('qr_scanned_before_medication')->default(false);
            $table->string('veterinarian_name')->default('Dra. Elena Ramos');
            $table->string('audit_hash')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_records');
    }
};
