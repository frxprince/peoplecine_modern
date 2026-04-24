<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class EulaController extends Controller
{
    public function __invoke(): View
    {
        $isThai = app()->getLocale() === 'th';

        return view('legal.eula', [
            'title' => $isThai ? 'ข้อตกลงการใช้งานซอฟต์แวร์' : 'Software License Agreement',
        ]);
    }
}
