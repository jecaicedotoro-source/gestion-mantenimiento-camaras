<?php

namespace Tests\Fakes;

use App\Contracts\IClasificadorIncidencias;
use App\Data\IncidentSuggestion;
use App\Enums\IncidentCategory;

/** Doble exclusivo de pruebas; no se registra como proveedor de producción. */
final class FakeIncidentClassifier implements IClasificadorIncidencias
{
    /** @var list<string> */
    public array $received = [];

    public function classify(string $minimizedDescription): IncidentSuggestion
    {
        $this->received[] = $minimizedDescription;

        return new IncidentSuggestion(IncidentCategory::Connectivity, 'Los síntomas describen pérdida de conexión.', 'simulado-pruebas', 20, 15);
    }
}
