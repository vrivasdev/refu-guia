<?php

namespace App\Services\Ai;

class PromptSanitizerService
{
    protected array $blacklistedPatterns = [
        '/ignore all previous instructions/i',
        '/ignora todas las instrucciones anteriores/i',
        '/olvida tus instrucciones/i',
        '/bypass/i',
        '/reveal system prompt/i',
        '/drop table/i',
        '/system:\s*role/i',
        '/eval\(/i',
    ];

    public function sanitize(string $input): string
    {
        $cleaned = trim($input);
        foreach ($this->blacklistedPatterns as $pattern) {
            $cleaned = preg_replace($pattern, '[CONTENIDO_FILTRADO_POR_SEGURIDAD]', $cleaned);
        }
        return $cleaned;
    }
}
