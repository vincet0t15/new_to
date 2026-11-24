<?php

namespace App\Http\Middleware;

use App\Support\NotificationUrl;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Turns a stale document link into a friendly notification-inbox redirect.
 *
 * Notification links are written when the notice is sent. By the time they are
 * opened, the account may have lost the portal, the permission, the delivery,
 * or the order itself. Those failures should not leave the user on a blank 403
 * page. The middleware wraps only the document show routes so unrelated
 * authorization failures keep their normal response.
 */
final class RedirectInaccessibleDocument
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! in_array($response->getStatusCode(), [403, 404], true)) {
            return $response;
        }

        // A direct visit to a document URL keeps its normal error. Only a
        // notification click gets the friendly "no longer accessible" path.
        if ($request->query(NotificationUrl::SOURCE_KEY) !== NotificationUrl::SOURCE_VALUE) {
            return $response;
        }

        Inertia::flash('toast', [
            'type' => 'error',
            'message' => __('This document is no longer accessible to your account.'),
        ]);

        // Relative Location keeps the Inertia visit on the current origin.
        return new RedirectResponse(route('inbox.index', [], false));
    }
}
