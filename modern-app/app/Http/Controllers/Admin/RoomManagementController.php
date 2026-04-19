<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoomManagementController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.rooms.index', [
            'title' => 'Room Management',
            'rooms' => Room::query()
                ->withCount('topics')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:rooms,slug'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'name_color' => ['nullable', 'string', 'regex:/^#?[0-9A-Fa-f]{6}$/'],
            'description' => ['nullable', 'string'],
            'access_level' => ['required', 'integer', Rule::in([0, 1, 2, 3, 4, 9])],
            'sort_order' => ['required', 'integer', 'between:0,9999'],
            'is_archived' => ['nullable', 'boolean'],
        ]);

        $slug = trim((string) ($validated['slug'] ?? ''));
        $nameColor = trim((string) ($validated['name_color'] ?? ''));

        Room::query()->create([
            'slug' => $slug !== ''
                ? Str::lower($slug)
                : $this->generateUniqueSlug($validated['name']),
            'name' => trim($validated['name']),
            'name_en' => $this->nullableTrim($validated['name_en'] ?? null),
            'name_color' => $nameColor !== ''
                ? '#'.ltrim($nameColor, '#')
                : null,
            'description' => $this->nullableTrim($validated['description'] ?? null),
            'access_level' => (int) $validated['access_level'],
            'sort_order' => (int) $validated['sort_order'],
            'is_archived' => $request->boolean('is_archived'),
        ]);

        return redirect()
            ->route('admin.rooms.index')
            ->with('status', 'New forum room created.');
    }

    public function update(Request $request, Room $room): RedirectResponse
    {
        $validated = $request->validate([
            'access_level' => ['required', 'integer', Rule::in([0, 1, 2, 3, 4, 9])],
            'sort_order' => ['required', 'integer', 'between:0,9999'],
            'is_archived' => ['nullable', 'boolean'],
        ]);

        $room->forceFill([
            'access_level' => (int) $validated['access_level'],
            'sort_order' => (int) $validated['sort_order'],
            'is_archived' => $request->boolean('is_archived'),
        ])->save();

        return redirect()
            ->route('admin.rooms.index')
            ->with('status', "Updated room {$room->name}.");
    }

    private function nullableTrim(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'room';
        $slug = $baseSlug;
        $suffix = 2;

        while (Room::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
