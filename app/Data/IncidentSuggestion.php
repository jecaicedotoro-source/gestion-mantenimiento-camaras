<?php

namespace App\Data;

use App\Enums\IncidentCategory;

/** Resultado validado del proveedor, sin valores de confianza inventados. */
final readonly class IncidentSuggestion
{
    public function __construct(
        public IncidentCategory $category,
        public string $explanation,
        public string $model,
        public ?int $inputTokens = null,
        public ?int $outputTokens = null,
    ) {}
}
