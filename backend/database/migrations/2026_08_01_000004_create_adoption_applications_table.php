<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adoption_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pet_id')->constrained('pets')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('monthly_income_usd', 10, 2)->default(0.00);
            $table->string('housing_type')->default('apartment_small');
            $table->boolean('has_closed_patio')->default(false);
            $table->integer('hours_dedicated_daily')->default(2);
            $table->string('family_composition')->default('Adultos sin niños');
            $table->boolean('has_other_pets')->default(false);
            $table->string('experience_level')->default('Intermedio');
            $table->integer('ai_suitability_score')->default(0);
            $table->enum('ai_decision', ['APPROVED', 'REVIEW_REQUIRED', 'REJECTED_HARD_STOP'])->default('REVIEW_REQUIRED');
            $table->text('ai_rationale')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adoption_applications');
    }
};
