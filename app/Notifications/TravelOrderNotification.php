<?php

namespace App\Notifications;

use App\Enums\SystemRole;
use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The notification sent around a travel order's route.
 *
 * The in-app row is always written. An email copy is added only when the
 * account actually has an address, so an account without one does not make
 * the mail channel fail the whole notification.
 */
final class TravelOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly TravelOrder $order,
        private readonly string $title,
        private readonly string $message,
        private readonly string $kind,
    ) {
        //
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof User && filled($notifiable->email)
            ? ['database', 'mail']
            : ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'kind' => $this->kind,
            'url' => $this->urlFor($notifiable),
            'travel_order_id' => $this->order->id,
            'revision_number' => $this->order->revision_number,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->line($this->message)
            ->action(__('Open travel order'), $this->urlFor($notifiable))
            ->line(__('You are receiving this because a travel order is addressed to your account.'));
    }

    /**
     * The screen this account can actually open for the order.
     *
     * The employee show page is creator-only, so an approver's notification
     * must point at the shared inbox screen instead.
     */
    private function urlFor(object $notifiable): string
    {
        if (! $notifiable instanceof User) {
            return route('dashboard');
        }

        if ($this->order->created_by === $notifiable->id) {
            return route('travel-orders.show', $this->order);
        }

        if ($notifiable->hasAnyRoleSlug([
            SystemRole::BudgetOfficer->value,
            SystemRole::Mayor->value,
            SystemRole::ViceMayor->value,
            SystemRole::Hrmo->value,
        ])) {
            return route('approvals.travel-orders.show', $this->order);
        }

        if ($notifiable->hasRoleSlug(SystemRole::DepartmentHead->value)) {
            return route('department-head.travel-orders.show', $this->order);
        }

        return route('dashboard');
    }
}
