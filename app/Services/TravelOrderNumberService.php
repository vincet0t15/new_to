<?php

namespace App\Services;

use App\Models\TravelOrder;

/**
 * Assigns the two numbers a travel order carries.
 *
 * A filed order gets a submission number (TO-YYYY-NNNN) that counts the
 * orders filed in that year. Once HRMO clears its step, the same order gets
 * a series number (HRMO-TO-YYYY-NNNN) that counts the orders HRMO approved
 * in that year. The two sequences are independent, so a document keeps its
 * filing place in the year even when HRMO handles it out of order.
 *
 * Callers already hold the order row lock inside a transaction. The queries
 * here lock the existing numbers for the year as well, so two filings cannot
 * receive the same suffix.
 */
final class TravelOrderNumberService
{
    public const SUBMISSION_PREFIX = 'TO';

    public const SERIES_PREFIX = 'HRMO-TO';

    /**
     * The next filing number for the year, e.g. TO-2026-0001.
     */
    public function nextSubmissionNumber(?int $year = null): string
    {
        return $this->next(
            'to_number',
            self::SUBMISSION_PREFIX,
            $year ?? (int) now()->year,
        );
    }

    /**
     * The next HRMO series number for the year, e.g. HRMO-TO-2026-0001.
     */
    public function nextSeriesNumber(?int $year = null): string
    {
        return $this->next(
            'control_no',
            self::SERIES_PREFIX,
            $year ?? (int) now()->year,
        );
    }

    /**
     * Build the next value for one numbered column.
     *
     * Existing values are parsed rather than counted so a deleted or manually
     * entered row can never make a later order reuse a number.
     */
    private function next(string $column, string $prefix, int $year): string
    {
        $stem = $prefix.'-'.$year.'-';

        $highest = TravelOrder::query()
            ->where($column, 'like', $stem.'%')
            ->lockForUpdate()
            ->pluck($column)
            ->reduce(function (int $carry, mixed $number): int {
                if (! is_string($number)) {
                    return $carry;
                }

                $suffix = substr($number, strrpos($number, '-') + 1);

                return ctype_digit($suffix) ? max($carry, (int) $suffix) : $carry;
            }, 0);

        return $stem.str_pad((string) ($highest + 1), 4, '0', STR_PAD_LEFT);
    }
}
