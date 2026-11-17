<?php

namespace App\Services;

use App\Models\TravelOrder;
use App\Models\User;
use App\Models\WorkflowStep;
use App\Notifications\TravelOrderNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Sends the three travel order notifications the workflow requires.
 *
 * Wording includes the revision number so repeated returns and resubmissions
 * are distinguishable in an inbox. Nothing here suppresses a repeat: a second
 * return is a new event and must notify again.
 */
final class TravelOrderNotifier
{
    public function returned(TravelOrder $order, User $returnedBy): void
    {
        $creator = User::query()->find($order->created_by);

        if (! $creator instanceof User) {
            return;
        }

        $step = $order->returned_from_step ?? 'the approval chain';

        Notification::send($creator, new TravelOrderNotification(
            $order,
            __('Travel order returned for revision'),
            __('Travel Order :number was returned by :actor at :step (revision :revision). :remarks', [
                'number' => $this->number($order),
                'actor' => $returnedBy->name,
                'step' => $step,
                'revision' => $order->revision_number,
                'remarks' => $order->return_remarks,
            ]),
            'returned',
        ));
    }

    public function routed(TravelOrder $order, User $recipient, WorkflowStep $step): void
    {
        $revision = $order->revision_number;

        $title = $revision > 1
            ? __('Travel order resubmitted for review')
            : __('Travel order routed to you');

        $message = $revision > 1
            ? __('Travel Order :number was resubmitted as revision :revision and is waiting at :step.', [
                'number' => $this->number($order),
                'revision' => $revision,
                'step' => $step->displayLabel(),
            ])
            : __('Travel Order :number is waiting for your action at :step.', [
                'number' => $this->number($order),
                'step' => $step->displayLabel(),
            ]);

        Notification::send($recipient, new TravelOrderNotification(
            $order,
            $title,
            $message,
            'routed',
        ));
    }

    private function number(TravelOrder $order): string
    {
        return $order->control_no
            ?? $order->to_number
            ?? '#'.$order->id;
    }
}
