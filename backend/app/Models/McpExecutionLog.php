<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class McpExecutionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_name',
        'tool_name',
        'input_payload',
        'output_payload',
        'execution_time_ms',
        'status'
    ];

    protected $casts = [
        'input_payload' => 'array',
        'output_payload' => 'array',
        'execution_time_ms' => 'float',
    ];
}
