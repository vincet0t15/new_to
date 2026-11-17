<?php

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The header bell's two actions.
 *
 * Opening a notice marks it read and then sends the account to the screen the
 * notification was written for. Every lookup is scoped through the signed-in
 * account's own relation, so a guessed identifier cannot reveal or silently
 * mark another person's notice.
 */
final class NotificationController extends Controller
{
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
}
