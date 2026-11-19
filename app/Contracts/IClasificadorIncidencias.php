<?php

namespace App\Contracts;

use App\Data\IncidentSuggestion;

/** Aísla la aplicación de OpenAI y permite sustitutos explícitos en pruebas. */
interface IClasificadorIncidencias
{
    public function classify(string $minimizedDescription): IncidentSuggestion;
}
