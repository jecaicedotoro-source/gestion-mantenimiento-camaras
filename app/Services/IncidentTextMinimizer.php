<?php

namespace App\Services;

/** Reduce datos identificables antes de mostrar la vista previa que revisa el usuario. */
final class IncidentTextMinimizer
{
    /** @param list<string|null> $privateValues Valores conocidos que permanecen solo en el servidor. */
    public function minimize(string $description, array $privateValues = []): string
    {
        $text = trim($description);

        // Se eliminan primero valores largos para evitar revelar fragmentos de identificadores.
        usort($privateValues, fn (?string $a, ?string $b): int => mb_strlen($b ?? '') <=> mb_strlen($a ?? ''));
        foreach ($privateValues as $value) {
            if ($value !== null && mb_strlen(trim($value)) >= 3) {
                $text = preg_replace('/'.preg_quote(trim($value), '/').'/iu', '[dato omitido]', $text) ?? $text;
            }
        }

        $patterns = [
            '~https?://\S+~iu',
            '/[\w.+-]+@[\w.-]+\.[a-z]{2,}/iu',
            '/\b(?:\d{1,3}\.){3}\d{1,3}\b/u',
            '/\b(?:[a-f0-9]{0,4}:){2,}[a-f0-9:]{0,39}\b/iu',
            '/\bsk-[a-z0-9_-]{8,}\b/iu',
            '/\b(?:bearer|token|api[_ -]?key|clave|contrase(?:ñ|n)a|password)\s*[:=]?\s*\S+/iu',
            '/\b(?:serie|serial|inventario|activo fijo|c[oó]digo)\s*[:#=-]?\s*[a-z0-9_-]+/iu',
            '/\b(?:bloque|edificio|piso|oficina|aula|sal[oó]n)\s*[:#=-]?\s*[a-z0-9_-]+/iu',
            '/\b\d{7,}\b/u',
        ];

        return trim(preg_replace($patterns, '[dato omitido]', $text) ?? '');
    }
}
