<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class AdminManualController extends Controller
{
    public function index(): View
    {
        $isThaiUi = app()->getLocale() === 'th';
        $t = static fn (string $thai, string $english): string => $isThaiUi ? $thai : $english;

        return view('admin.manual', [
            'title' => $t('คู่มือผู้ดูแลระบบ', 'Admin Manual'),
            'manualLabel' => $t('คู่มือผู้ดูแลระบบ', 'Admin Manual'),
        ]);
    }
}
