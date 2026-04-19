<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CalculatorController extends Controller
{
    public function throwSelector(): View
    {
        return view('calculators.throw-selector-localized', [
            'title' => __('Throw Distance Calculator'),
        ]);
    }

    public function throwCalculator(Request $request, string $screen): View
    {
        $printFormat = $request->string('print_format')->lower()->value();

        if (! in_array($printFormat, ['scope', 'flat', 'both'], true)) {
            $printFormat = 'scope';
        }

        return view('calculators.throw-localized', [
            'title' => __('Throw Distance Calculator'),
            'screen' => $screen,
            'printFormat' => $printFormat,
        ]);
    }

    public function lensSimulation(): View
    {
        return view('calculators.lenssim-localized', [
            'title' => __('Lens Pair Image Simulator'),
        ]);
    }

    public function screenDesign(): View
    {
        return view('calculators.screendesign-localized', [
            'title' => __('Screen Size Calculator'),
        ]);
    }
}
