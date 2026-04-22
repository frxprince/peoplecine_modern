<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\BannerManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BannerManagementController extends Controller
{
    public function __construct(private readonly BannerManager $bannerManager)
    {
    }

    public function index(): View
    {
        return view('admin.banners.index', [
            'title' => $this->label('จัดการแบนเนอร์', 'Banner Management'),
            'bannerSections' => [
                'sidebar' => [
                    'label' => $this->label('แบนเนอร์ฝั่งซ้าย', 'Left Panel Banners'),
                    'items' => $this->bannerManager->sidebarBanners(),
                ],
                'landing' => [
                    'label' => $this->label('แบนเนอร์หน้าแรก', 'Landing Page Banners'),
                    'items' => $this->bannerManager->landingBanners(),
                ],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'section' => ['required', 'string', Rule::in(['sidebar', 'landing'])],
            'alt' => ['nullable', 'string', 'max:255'],
            'image' => ['required', 'image', 'max:8192'],
        ]);

        $this->bannerManager->add(
            $validated['section'],
            $request->file('image'),
            $validated['alt'] ?? null
        );

        return redirect()
            ->route('admin.banners.index')
            ->with('status', $this->label('เพิ่มแบนเนอร์เรียบร้อยแล้ว', 'Banner added successfully.'));
    }

    public function update(Request $request, string $section, string $bannerId): RedirectResponse
    {
        $validated = $request->validate([
            'sort_order' => ['required', 'integer', 'between:0,99999'],
            'alt' => ['nullable', 'string', 'max:255'],
        ]);

        $this->bannerManager->update(
            $section,
            $bannerId,
            (int) $validated['sort_order'],
            $validated['alt'] ?? null
        );

        return redirect()
            ->route('admin.banners.index')
            ->with('status', $this->label('อัปเดตแบนเนอร์เรียบร้อยแล้ว', 'Banner updated successfully.'));
    }

    public function destroy(string $section, string $bannerId): RedirectResponse
    {
        $this->bannerManager->delete($section, $bannerId);

        return redirect()
            ->route('admin.banners.index')
            ->with('status', $this->label('ลบแบนเนอร์เรียบร้อยแล้ว', 'Banner deleted successfully.'));
    }

    private function label(string $thai, string $english): string
    {
        return app()->getLocale() === 'th' ? $thai : $english;
    }
}
