<?php

namespace App\Http\Controllers;

use App\Models\DirectMessagePreference;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DirectMessagePreferenceController extends Controller
{
    public function block(Request $request, User $user): RedirectResponse
    {
        $this->ensureValidTarget($request->user(), $user);
        $this->upsertPreference($request->user(), $user, ['is_blocked' => true]);

        return back()->with('status', 'This member has been blocked from private messages.');
    }

    public function unblock(Request $request, User $user): RedirectResponse
    {
        $this->ensureValidTarget($request->user(), $user);
        $this->upsertPreference($request->user(), $user, ['is_blocked' => false]);

        return back()->with('status', 'This member can message you again.');
    }

    public function mute(Request $request, User $user): RedirectResponse
    {
        $this->ensureValidTarget($request->user(), $user);
        $this->upsertPreference($request->user(), $user, ['is_muted' => true]);

        return back()->with('status', 'This member has been muted. Their new messages will not raise alerts.');
    }

    public function unmute(Request $request, User $user): RedirectResponse
    {
        $this->ensureValidTarget($request->user(), $user);
        $this->upsertPreference($request->user(), $user, ['is_muted' => false]);

        return back()->with('status', 'This member is no longer muted.');
    }

    private function ensureValidTarget(User $viewer, User $target): void
    {
        abort_if((int) $viewer->id === (int) $target->id, 422);
    }

    /**
     * @param array{is_blocked?: bool, is_muted?: bool} $changes
     */
    private function upsertPreference(User $viewer, User $target, array $changes): void
    {
        $preference = DirectMessagePreference::query()->firstOrNew([
            'user_id' => $viewer->id,
            'target_user_id' => $target->id,
        ]);

        $preference->fill(array_merge([
            'is_blocked' => $preference->is_blocked ?? false,
            'is_muted' => $preference->is_muted ?? false,
        ], $changes));

        if (! $preference->is_blocked && ! $preference->is_muted) {
            if ($preference->exists) {
                $preference->delete();
            }

            return;
        }

        $preference->save();
    }
}
