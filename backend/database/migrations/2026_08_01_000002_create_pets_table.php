<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pets', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->enum('report_type', ['lost', 'found'])->default('found');
            $table->string('name')->nullable();
            $table->enum('species', ['canine', 'feline', 'other'])->default('canine');
            $table->string('breed')->nullable();
            $table->enum('size', ['small', 'medium', 'large'])->default('medium');
            $table->string('primary_color');
            $table->string('secondary_color')->nullable();
            $table->string('coat_pattern')->nullable();
            $table->text('distinctive_marks')->nullable();
            $table->enum('status', ['lost', 'in_shelter', 'reunified', 'adoptable', 'adopted'])->default('in_shelter');
            $table->text('photo_url')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('location_address')->nullable();
            $table->date('rescue_date')->nullable();
            $table->date('grace_period_ends_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pets');
    }
};
