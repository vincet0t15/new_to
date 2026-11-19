<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\OfficeDesignation;
use App\Models\TravelOrder;
use App\Models\WorkflowStep;
use App\Models\WorkflowStepType;

/**
 * Works out who the printed travel order names in each signature line.
 *
 * A read-only collaborator. The submission service freezes its answer onto the
 * document, and this class only works out what that answer would be. Printing a
 * draft therefore follows the designations as they stand, while a filed order
 * keeps the names it was sent with.
 *
 * An office may name a signatory group. When it has one, the CERTIFIED,
 * APPROVED, and OIC lines take their signer from that group instead of the
 * designation, and the group's title prints over the signer's own position. A
 * blank half of the group leaves that half as the designation resolved it.
 */
final class SignatoryResolver
{
    /**
     * The names the printed travel order fills in at its signature lines.
     *
     * @param  bool  $forceLive  Skip the frozen snapshot and resolve from the
     *                           designations as they stand. Used when the
     *                           snapshot is being replaced rather than read.
     * @return array<string, array{name: string|null, title: string|null, position: string|null, title_override: string|null}>
     */
    public function for(TravelOrder $order, bool $forceLive = false): array
    {
        if (! $forceLive && ! empty($order->signatories)) {
            return $order->signatories;
        }

        // The office may already be loaded as a partial select, so it is
        // re-read through the relation rather than trusted.
        $group = $order->office()
            ->with([
                'signatoryGroup.certifiedSignatory.position',
                'signatoryGroup.approvedSignatory.position',
                'signatoryGroup.oicSignatory.position',
            ])
            ->first()?->signatoryGroup;

        $approvedType = $this->approvedType($order);

        return [
            'department_head_approval' => $this->signatureLine(
                $this->typeMatching($order, ['department head']),
                $order->office_id,
                fallbackAcrossOffices: false,
            ),
            'budget_office_approval' => $this->withGroupSigner(
                $this->signatureLine(
                    $this->typeMatching($order, ['budget']),
                    $order->office_id,
                ),
                $group?->certifiedSignatory,
                $group?->certified_signatory_title,
            ),
            'mayor_approval' => $this->withGroupSigner(
                $this->signatureLine($approvedType, $order->office_id),
                $group?->approvedSignatory,
                $group?->approved_signatory_title,
            ),
            // The OIC line is not tied to a designation, so it prints an empty
            // line until an office's group names one.
            'oic_approval' => $this->withGroupSigner(
                [
                    'name' => null,
                    'title' => null,
                    'position' => null,
                    'title_override' => null,
                ],
                $group?->oicSignatory,
                $group?->oic_signatory_title,
            ),
        ];
    }

    /**
     * Lay a signatory group's signer and title over a resolved line.
     *
     * Either half stands alone: naming an employee replaces the designation's
     * holder while its title still resolves, and naming a title changes what
     * prints under the holder without moving the seat.
     *
     * @param  array{name: string|null, title: string|null, position: string|null, title_override: string|null}  $line
     * @return array{name: string|null, title: string|null, position: string|null, title_override: string|null}
     */
    private function withGroupSigner(array $line, ?Employee $employee, ?string $title): array
    {
        if ($employee !== null) {
            $line['name'] = $employee->full_name;
            $line['position'] = $employee->position?->name;
        }

        $line['title_override'] = filled($title) ? $title : null;

        return $line;
    }

    /**
     * The name a signature line prints, from whoever currently holds the seat.
     *
     * @return array{name: string|null, title: string|null, position: string|null, title_override: string|null}
     */
    private function signatureLine(
        ?WorkflowStepType $type,
        ?int $officeId,
        bool $fallbackAcrossOffices = true,
    ): array {
        $employee = $type === null
            ? null
            : $this->designatedEmployee($type->id, $officeId, $fallbackAcrossOffices);

        return [
            'name' => $employee?->full_name,
            'title' => $type?->signature_title,
            'position' => $employee?->position?->name,
            'title_override' => null,
        ];
    }

    /**
     * The step type that signs the APPROVED line on this order.
     *
     * The order's own frozen route is preferred, so a Legislative chain prints
     * the Vice Mayor while a default chain prints the Mayor. A draft that has
     * never been routed falls back to whichever approval type is configured.
     */
    private function approvedType(TravelOrder $order): ?WorkflowStepType
    {
        $types = $this->routedTypes($order);

        $approved = $types
            ->filter(fn (WorkflowStepType $type): bool => $type->signature_label === 'Approved'
                || in_array($type->name, ['Mayor Approval', 'Vice Mayor Approval'], true))
            ->last();

        if ($approved instanceof WorkflowStepType) {
            return $approved;
        }

        return WorkflowStepType::query()
            ->whereIn('name', ['Mayor Approval', 'Vice Mayor Approval'])
            ->orderByRaw("CASE WHEN name = 'Mayor Approval' THEN 0 ELSE 1 END")
            ->first();
    }

    /**
     * The first step type on the order's route whose name contains any of the
     * given words, falling back to the configured type of the same meaning.
     *
     * @param  list<string>  $needles
     */
    private function typeMatching(TravelOrder $order, array $needles): ?WorkflowStepType
    {
        $types = $this->routedTypes($order);

        $match = $types->first(function (WorkflowStepType $type) use ($needles): bool {
            $name = strtolower($type->name);

            foreach ($needles as $needle) {
                if (str_contains($name, $needle)) {
                    return true;
                }
            }

            return false;
        });

        if ($match instanceof WorkflowStepType) {
            return $match;
        }

        return WorkflowStepType::query()
            ->where(function ($query) use ($needles): void {
                foreach ($needles as $needle) {
                    $query->orWhere('name', 'like', '%'.$needle.'%');
                }
            })
            ->orderBy('sort_order')
            ->first();
    }

    /**
     * The step types of the order's frozen route, in route order.
     *
     * @return \Illuminate\Support\Collection<int, WorkflowStepType>
     */
    private function routedTypes(TravelOrder $order): \Illuminate\Support\Collection
    {
        $ids = $order->routed_steps;

        if (! is_array($ids) || $ids === []) {
            return collect();
        }

        $positions = array_flip(array_map('intval', $ids));

        return WorkflowStep::query()
            ->with('stepType')
            ->whereIn('id', $ids)
            ->get()
            ->pluck('stepType')
            ->filter()
            ->sortBy(fn (WorkflowStepType $type): int => $positions[$type->id] ?? PHP_INT_MAX)
            ->values();
    }

    /**
     * Whoever holds a designation in the given office.
     *
     * The standing holder is preferred and a deputy only stands in for them,
     * which mirrors how the engine routes: a deputy covers a holder who is
     * away, so they sign while the holder is the one the office names.
     */
    private function designatedEmployee(
        int $typeId,
        ?int $officeId,
        bool $fallbackAcrossOffices,
    ): ?Employee {
        $query = OfficeDesignation::query()
            ->current()
            ->where('workflow_step_type_id', $typeId)
            ->with('employee.position')
            ->orderBy('is_deputy')
            ->orderBy('id');

        $designations = $officeId === null
            ? collect()
            : (clone $query)->where('office_id', $officeId)->get();

        if ($designations->isEmpty() && $fallbackAcrossOffices) {
            $designations = $query->get();
        }

        return $designations->first()?->employee;
    }
}
