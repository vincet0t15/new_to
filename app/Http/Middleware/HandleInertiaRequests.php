<?php

namespace App\Http\Middleware;

use App\Enums\Portal;
use App\Models\User;
use App\Repositories\Contracts\TravelOrderRepository;
use App\Services\PortalResolver;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function __construct(
        private readonly PortalResolver $portals,
        private readonly TravelOrderRepository $orders,
    ) {
        //
    }

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $current = null;

        if ($user instanceof User) {
            // Settings and other screens outside a portal still belong to the
            // account's primary workspace, so they show that portal's shell.
            $current = $this->portals->currentFor($request)
                ?? $this->portals->defaultFor($user);
        }

        $portals = $user instanceof User
            ? $this->portals->availableFor($user)
            : [];

        $pendingInboxCount = $user instanceof User && $this->hasProcessingPortal($portals)
            ? $this->orders->pendingInboxCount($user)
            : 0;

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'roles' => $request->user()?->roles->pluck('slug')->values()->all() ?? [],
                'permissions' => $request->user()?->getAllPermissions()->pluck('name')->values()->all() ?? [],
            ],
            'portals' => $user instanceof User
                ? $this->portals->optionsFor($user, $current, $pendingInboxCount)
                : [],
            'currentPortal' => $current?->value,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'notifications' => $this->notifications($request),
            'unreadNotificationsCount' => $this->unreadNotificationsCount($request),
            'flash' => fn () => [
                'toast' => $request->session()->get('toast'),
                'success' => $request->session()->get('success'),
                'warning' => $request->session()->get('warning'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }

    /**
     * The latest in-app notifications shown under the header bell.
     *
     * Only the compact shape the bell needs is shared: the full notification
     * row stays on the server and is fetched by opening it.
     *
     * @return array<int, array{id: string, title: string, message: string, url: string|null, read_at: string|null, created_at: string|null}>
     */
    private function notifications(Request $request): array
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return [];
        }

        return $user->notifications()
            ->limit(8)
            ->get()
            ->map(function (DatabaseNotification $notification): array {
                $data = $notification->data;

                return [
                    'id' => $notification->id,
                    'title' => is_string($data['title'] ?? null) ? $data['title'] : '',
                    'message' => is_string($data['message'] ?? null) ? $data['message'] : '',
                    'url' => is_string($data['url'] ?? null) ? $data['url'] : null,
                    'read_at' => $notification->read_at?->toIso8601String(),
                    'created_at' => $notification->created_at?->toIso8601String(),
                ];
            })
            ->all();
    }

    /**
     * How many notifications the signed-in account has not opened yet.
     */
    private function unreadNotificationsCount(Request $request): int
    {
        $user = $request->user();

        return $user instanceof User
            ? $user->unreadNotifications()->count()
            : 0;
    }

    /**
     * Whether the account may enter a portal that owns a processing inbox.
     *
     * Only those accounts need the live-queue count, so employee-only and
     * administrator-only accounts never pay for the extra query.
     *
     * @param  array<int, Portal>  $portals
     */
    private function hasProcessingPortal(array $portals): bool
    {
        foreach ($portals as $portal) {
            if ($portal->inboxRouteName() !== null) {
                return true;
            }
        }

        return false;
    }
}
