<?php

namespace App\Enums;

/**
 * Everything that can happen to a document while it is in a workflow.
 *
 * These are the rows of the audit trail.
 */
enum WorkflowEvent: string
{
    case Submitted = 'submitted';

    /** Routed to a person, who now holds the document. */
    case Routed = 'routed';

    case Received = 'received';

    case Approved = 'approved';

    /**
     * The holder confirmed receipt at a step nobody signs at, and the document
     * moved on towards whoever does.
     */
    case Acknowledged = 'acknowledged';

    /** Handed to someone else at the same step. */
    case Forwarded = 'forwarded';

    case Returned = 'returned';

    /**
     * The step does not apply to this document, usually because the filer is
     * the person who would have signed it.
     */
    case StepSkipped = 'step_skipped';

    /**
     * The filer was among the people a step would have asked, and was dropped
     * from it while the step itself stood.
     */
    case FilerWithheld = 'filer_withheld';

    /** The last step cleared and the document is finished. */
    case Completed = 'completed';

    /** The filer took the document back to a draft before it was decided. */
    case PulledBack = 'pulled_back';

    /**
     * The document was withdrawn before it could be decided, or an approved
     * one was voided. Either way it is closed and will not move again.
     */
    case Cancelled = 'cancelled';

    /** The filer resubmitted a returned travel order. */
    case Resubmitted = 'resubmitted';

    /** Budget office recorded fund earmark/allocation details. */
    case Earmarked = 'earmarked';

    /** Budget office assigned the fund source. */
    case FundSourceAssigned = 'fund_source_assigned';

    /** HRMO recorded series/control number and release details. */
    case Recorded = 'recorded';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::Routed => 'Routed',
            self::Received => 'Received',
            self::Approved => 'Approved',
            self::Acknowledged => 'Acknowledged',
            self::Forwarded => 'Forwarded',
            self::Returned => 'Returned',
            self::StepSkipped => 'Step skipped',
            self::FilerWithheld => 'Filer withheld',
            self::Completed => 'Completed',
            self::PulledBack => 'Pulled back',
            self::Cancelled => 'Cancelled',
            self::Resubmitted => 'Resubmitted',
            self::Earmarked => 'Earmarked',
            self::FundSourceAssigned => 'Fund Source Assigned',
            self::Recorded => 'Recorded',
        };
    }
}
