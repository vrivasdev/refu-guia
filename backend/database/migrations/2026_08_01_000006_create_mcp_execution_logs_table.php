<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mcp_execution_logs', function (Blueprint $table) {
            $table->id();
            $table->string('agent_name');
            $table->string('tool_name');
            $table->json('input_payload')->nullable();
            $table->json('output_payload')->nullable();
            $table->float('execution_time_ms')->default(0.0);
            $table->string('status')->default('success');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mcp_execution_logs');
    }
};
