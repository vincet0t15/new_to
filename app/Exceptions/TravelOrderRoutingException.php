<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A routing or approval rule refused an action.
 *
 * The message is written for the person who took the action, because it is
 * shown back to them unchanged. Each factory keeps the wording in one place
 * so the services read as rules rather than string literals.
 */
final class TravelOrderRoutingException extends RuntimeException
{
    public static function noOffice(): self
    {
        return new self('This travel order has no filing office, so it cannot be routed.');
    }

    public static function noWorkflow(): self
    {
        return new self('No active approval workflow is configured for your office.');
    }

    public static function noSteps(): self
    {
        return new self('The workflow for your office has no active steps.');
    }

    public static function stepNotReady(string $label, string $reason): self
    {
        return new self("The \"{$label}\" step cannot receive this travel order: {$reason}");
    }

    public static function noRecipientLeft(): self
    {
        return new self('No eligible recipient is available for this travel order at its current approval step.');
    }

    public static function stepUndeliverable(string $label, string $reason): self
    {
        return new self("The \"{$label}\" step cannot receive this travel order right now: {$reason}");
    }

    public static function routeStepMissing(int $stepNumber): self
    {
        return new self("Approval step {$stepNumber} in this travel order's frozen route no longer exists. An administrator needs to review the workflow configuration.");
    }

    public static function notDraft(): self
    {
        return new self('Only a draft travel order can be submitted.');
    }

    public static function notOwner(): self
    {
        return new self('Only the person who created this travel order can submit it.');
    }

    public static function notReturned(): self
    {
        return new self('Only a travel order returned for revision can be resubmitted.');
    }

    public static function notReturnable(): self
    {
        return new self('Only a travel order that has been returned for revision can be edited this way.');
    }

    public static function onlyCreatorCanResubmit(): self
    {
        return new self('Only the person who created this travel order can resubmit it.');
    }

    public static function returnRemarksRequired(): self
    {
        return new self('Please explain what needs to be revised.');
    }

    public static function resubmissionRemarksRequired(): self
    {
        return new self('Please describe what you changed before resubmitting.');
    }

    public static function staleAction(): self
    {
        return new self('This travel order is no longer waiting for your action.');
    }

    public static function wrongStepType(bool $signatory): self
    {
        return $signatory
            ? new self('This step records an approval. Approve it instead of acknowledging it.')
            : new self('This step records a receipt. Acknowledge it instead of approving it.');
    }

    public static function forwardTargetNotAllowed(): self
    {
        return new self('The selected person does not answer this step.');
    }

    public static function budgetActionNotAllowed(): self
    {
        return new self('Only the active Budget holder can perform this fund action.');
    }
}
