<?php

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The header bell's actions and the account's notification inbox.
 *
 * Opening a notice marks it read and then sends the account to the screen the
 * notification was written for. Every lookup is scoped through the signed-in
 * account's own relation, so a guessed identifier cannot reveal or silently
 * mark another person's notice.
 */
final class NotificationController extends Controller
{
    /**
     * The full list of notifications addressed to the signed-in account.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $notificationItems = $user->notifications()
            ->latest()
            ->paginate(20)
            ->through(fn (DatabaseNotification $notification): array => $this->present($notification));

        return Inertia::render('notifications/index', [
            'notificationItems' => $notificationItems,
        ]);
    }

    /**
     * Open one notification and mark it as read.
     */
    public function open(Request $request, string $notification): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $notice = $user->notifications()->whereKey($notification)->firstOrFail();
        $notice->markAsRead();

        $url = $notice->data['url'] ?? null;

        return redirect(is_string($url) && $url !== '' ? $url : route('dashboard'));
    }

    /**
     * Mark every unread notification of the signed-in account as read.
     */
    public function readAll(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $user->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }

    /**
     * The compact row the inbox screen renders.
     *
     * @return array{id: string, title: string, message: string, kind: string, url: string|null, read_at: string|null, created_at: string|null}
     */
    private function present(DatabaseNotification $notification): array
    {
        $data = $notification->data;

        return [
            'id' => $notification->id,
            'title' => is_string($data['title'] ?? null) ? $data['title'] : __('Notification'),
            'message' => is_string($data['message'] ?? null) ? $data['message'] : '',
            'kind' => is_string($data['kind'] ?? null) ? $data['kind'] : 'general',
            'url' => is_string($data['url'] ?? null) ? $data['url'] : null,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }
}
