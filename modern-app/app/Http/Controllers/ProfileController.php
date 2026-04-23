<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request, \App\Models\User $user): View
    {
        abort_unless($request->user()?->canViewMemberProfiles(), 403);

        return view('profile.show', [
            'title' => $this->label(
                $user->displayName().' โปรไฟล์',
                $user->displayName().' Profile'
            ),
            'profileUser' => $user->load('profile')->loadCount(['topics', 'posts']),
        ]);
    }

    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'title' => $this->label('ตั้งค่าโปรไฟล์', 'Profile Settings'),
            'user' => $request->user()->load('profile'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user()->load('profile');

        $validated = $request->validate([
            'email' => [
                'nullable',
                'email:rfc',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'display_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'hide_address' => ['nullable', 'boolean'],
            'biography' => ['nullable', 'string'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,bmp,webp', 'max:4096'],
        ]);

        $user->forceFill([
            'email' => $validated['email'] ?: null,
        ])->save();

        $avatarPath = $user->profile?->avatar_path;

        if ($request->hasFile('avatar')) {
            $avatarPath = $this->storeUploadedAvatar(
                $request->file('avatar'),
                $user->id,
                $user->profile?->normalizedAvatarPath(),
            );
        }

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'display_name' => ($validated['display_name'] ?? null) ?: $user->profile?->display_name,
                'phone' => ($validated['phone'] ?? null) ?: null,
                'province' => ($validated['province'] ?? null) ?: null,
                'postal_code' => ($validated['postal_code'] ?? null) ?: null,
                'address' => ($validated['address'] ?? null) ?: null,
                'hide_address' => $request->boolean('hide_address'),
                'biography' => ($validated['biography'] ?? null) ?: null,
                'avatar_path' => $avatarPath,
            ],
        );

        return redirect()
            ->route('profile.edit')
            ->with('status', $this->label('อัปเดตโปรไฟล์เรียบร้อยแล้ว', 'Your profile has been updated.'));
    }

    private function label(string $thai, string $english): string
    {
        return app()->getLocale() === 'th' ? $thai : $english;
    }

    private function storeUploadedAvatar(UploadedFile $avatar, int $userId, ?string $existingPath): string
    {
        $root = (string) config('peoplecine.legacy_wboard_root');
        $iconsDirectory = rtrim($root, '\\/').DIRECTORY_SEPARATOR.'icons';

        File::ensureDirectoryExists($iconsDirectory);

        if ($existingPath !== null && str_starts_with($existingPath, 'icons/member-avatar-')) {
            $existingAbsolute = $iconsDirectory.DIRECTORY_SEPARATOR.basename($existingPath);

            if (File::exists($existingAbsolute)) {
                File::delete($existingAbsolute);
            }
        }

        $extension = strtolower($avatar->getClientOriginalExtension() ?: $avatar->extension() ?: 'jpg');
        $fileName = 'member-avatar-'.$userId.'-'.Str::random(16).'.'.$extension;

        $avatar->move($iconsDirectory, $fileName);

        return 'icons/'.$fileName;
    }
}
