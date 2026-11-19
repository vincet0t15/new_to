<?php

namespace App\Services;

use App\Models\Location;
use App\Models\PerDiemConfiguration;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Resolves the per diem configuration that covers an itinerary leg and
 * computes the amount claimable for it.
 *
 * Matching is anchored on the destination and the travel date. A configuration
 * that lists destinations covers only those; one without any destination is
 * the fallback and covers everything. The more specific coverage wins, and
 * ties are broken by the higher daily rate.
 *
 * Meal cutoffs trim only the edges of the trip: a meal is deducted when the
 * traveller leaves the trip origin after its cutoff, or arrives back at the
 * origin before its cutoff. Intra-trip legs keep every meal.
 */
class PerDiemComputationService
{
    /** @var Collection<int, PerDiemConfiguration>|null */
    private ?Collection $configurationCache = null;

    /**
     * The amount claimable for one itinerary leg, or null when no active
     * configuration covers it.
     *
     * @param  int|null  $locationId  The place the rate is based on. A return
     *                                leg passes the place it leaves, so the
     *                                travelled-to allowance still applies.
     * @param  bool  $deductDeparture  Whether this leg leaves the trip origin.
     * @param  bool  $deductArrival  Whether this leg returns to the trip origin.
     */
    public function entryAmount(
        ?int $locationId,
        string $dateFrom,
        string $dateTo,
        ?string $departureTime,
        ?string $arrivalTime,
        bool $deductDeparture = false,
        bool $deductArrival = false,
    ): ?string {
        $location = $locationId === null
            ? null
            : Location::query()->find($locationId);

        $configuration = $this->resolveConfiguration($location, $dateFrom);

        if ($configuration === null) {
            return null;
        }

        $dailyRate = $this->dailyRate($configuration);
        $days = $this->days($dateFrom, $dateTo);
        $deduction = $this->mealDeduction(
            $configuration,
            $departureTime,
            $arrivalTime,
            $deductDeparture,
            $deductArrival,
        );

        $amount = max(0, ($dailyRate * $days) - $deduction);

        return number_format($amount, 2, '.', '');
    }

    /**
     * The active configuration covering a destination on a date. Null when
     * nothing matches, in which case the caller keeps a manual amount.
     */
    public function resolveConfiguration(
        ?Location $location,
        string $date,
    ): ?PerDiemConfiguration {
        $travelDate = Carbon::parse($date);

        $matches = $this->configurations()
            ->filter(fn (PerDiemConfiguration $configuration): bool => $this->covers($configuration, $location, $travelDate));

        if ($matches->isEmpty()) {
            return null;
        }

        return $matches
            ->sortByDesc(fn (PerDiemConfiguration $configuration): string => sprintf(
                '%d-%020.2f',
                $this->specificity($configuration),
                $this->dailyRate($configuration),
            ))
            ->first();
    }

    /**
     * The configured daily rate.
     *
     * The meal breakdown is authoritative when one exists, because the travel
     * setup screen derives the displayed rate from those rows. A configuration
     * saved with only a flat rate keeps that rate.
     */
    public function dailyRate(PerDiemConfiguration $configuration): float
    {
        $mealTotal = (float) $configuration->perDiemMealRules->sum('amount');

        return $mealTotal > 0
            ? $mealTotal
            : (float) $configuration->daily_rate;
    }

    /**
     * The meal amounts deducted for an itinerary leg.
     *
     * Breakfast, lunch and dinner carry cutoffs. The departure cutoff applies
     * only when the leg leaves the trip origin; the arrival cutoff applies
     * only when the leg returns to it.
     */
    public function mealDeduction(
        PerDiemConfiguration $configuration,
        ?string $departureTime,
        ?string $arrivalTime,
        bool $deductDeparture = true,
        bool $deductArrival = true,
    ): float {
        $deducted = [];

        foreach ($configuration->perDiemMealRules as $rule) {
            $cutoff = $rule->cutoff_time?->format('H:i');

            if ($cutoff === null) {
                continue;
            }

            $departedAfter = $deductDeparture
                && $departureTime !== null
                && $departureTime !== ''
                && $departureTime > $cutoff;
            $arrivedBefore = $deductArrival
                && $arrivalTime !== null
                && $arrivalTime !== ''
                && $arrivalTime < $cutoff;

            if ($departedAfter || $arrivedBefore) {
                $deducted[$rule->id] = (float) $rule->amount;
            }
        }

        return array_sum($deducted);
    }

    /**
     * Whether the configuration matches the destination and date.
     */
    private function covers(
        PerDiemConfiguration $configuration,
        ?Location $location,
        Carbon $travelDate,
    ): bool {
        $from = $configuration->effective_from;
        $until = $configuration->effective_until;

        if ($from !== null && $from->gt($travelDate)) {
            return false;
        }

        if ($until !== null && $until->lt($travelDate)) {
            return false;
        }

        // A configuration naming no destinations is the fallback and covers
        // everything. One that names destinations covers exactly those, which
        // is what lets a place carry its own rate.
        if ($configuration->locations->isEmpty()) {
            return true;
        }

        return $location !== null
            && $configuration->locations->contains('id', $location->id);
    }

    /**
     * How specific a configuration's coverage is.
     *
     * 1 = names destinations, 0 = covers every destination.
     */
    private function specificity(PerDiemConfiguration $configuration): int
    {
        return $configuration->locations->isNotEmpty() ? 1 : 0;
    }

    private function days(string $dateFrom, string $dateTo): int
    {
        $from = Carbon::parse($dateFrom);
        $to = Carbon::parse($dateTo);

        if ($to->lt($from)) {
            return 1;
        }

        return (int) $from->diffInDays($to) + 1;
    }

    /**
     * @return Collection<int, PerDiemConfiguration>
     */
    private function configurations(): Collection
    {
        return $this->configurationCache ??= PerDiemConfiguration::query()
            ->active()
            ->with([
                'locations:id',
                'perDiemMealRules:id,per_diem_configuration_id,meal_type,amount,cutoff_time,sort_order',
            ])
            ->get();
    }
}
