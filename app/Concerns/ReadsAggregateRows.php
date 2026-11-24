<?php

namespace App\Concerns;

/**
 * Normalizes values selected by SQL aliases before they reach typed props.
 *
 * Aggregate queries use scalar aliases such as `liters` or `office_name` that
 * are not declared model properties. These helpers keep the conversion in one
 * place so controllers do not rely on undefined-property magic.
 */
trait ReadsAggregateRows
{
    private function rawText(mixed $value, string $default = ''): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $default;
    }

    private function rawFloat(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }

    private function rawInt(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private function rawNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->rawInt($value);
    }

    private function rawOfficeKey(mixed $row): string
    {
        return $this->rawText(data_get($row, 'office_id'), '0');
    }

    private function rawTypeKey(mixed $row, string $key, string $default = 'null'): string
    {
        return $this->rawText(data_get($row, $key), $default);
    }

    private function rawPairKey(mixed $row): string
    {
        return $this->rawOfficeKey($row).'|'.$this->rawTypeKey($row, 'fuel_type', 'Unspecified');
    }
}
