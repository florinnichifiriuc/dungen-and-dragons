<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

class NotificationCenterController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->paginate(20)
            ->through(function (DatabaseNotification $notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $notification->data,
                    'read_at' => optional($notification->read_at)?->toIso8601String(),
                    'created_at' => $notification->created_at?->toIso8601String(),
                ];
            });

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            $notification->notifiable_id === $user->getKey() && $notification->notifiable_type === $user::class,
            404
        );

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return redirect()->back();
    }

    public function markAll(Request $request): RedirectResponse
    {
        $user = $request->user();

        $user->unreadNotifications->each->markAsRead();

        return redirect()->back()->with('success', __('app.notifications.cleared'));
    }
}
