<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lost_pet_id')->constrained('pets')->onDelete('cascade');
            $table->foreignId('found_pet_id')->constrained('pets')->onDelete('cascade');
            $table->float('similarity_score');
            $table->boolean('species_match')->default(true);
            $table->float('visual_score')->default(0.0);
            $table->float('nlp_semantic_score')->default(0.0);
            $table->float('geo_distance_km')->default(0.0);
            $table->enum('status', ['pending_review', 'alert_sent', 'confirmed_by_human', 'rejected_by_human'])->default('pending_review');
            $table->text('human_feedback_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_logs');
    }
};
