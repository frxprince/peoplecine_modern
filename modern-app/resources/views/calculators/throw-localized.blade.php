@extends('layouts.app')

@php
    $isEnglish = app()->getLocale() === 'en';
    $t = static fn (string $th, string $en) => $isEnglish ? $en : $th;
    $isScopeScreen = $screen === 'scope';
    $screenLabel = $isScopeScreen
        ? $t('สโคป', 'Scope')
        : $t('จอตัดซีน', 'Flat');
    $printFormatLabel = match ($printFormat) {
        'flat' => $t('ตัดซีน', 'Flat'),
        'both' => $t('สโคป + ตัดซีน', 'Scope + Flat'),
        default => $t('สโคป', 'Scope'),
    };
@endphp

@section('content')
    <section class="panel legacy-calculator">
        <div class="panel__header">
            <h2>
                {{ $isScopeScreen
                    ? $t('คำนวณระยะฉายสำหรับจอสโคป', 'Throw Distance Calculator for Scope Screen')
                    : $t('คำนวณระยะฉายสำหรับจอตัดซีน', 'Throw Distance Calculator for Flat Screen') }}
            </h2>
        </div>
        <div class="panel__body">
            @include('partials.calculator-page-nav-localized')

            <div class="container-fluid" data-calculator-page="throw" data-throw-screen="{{ $screen }}" data-throw-print-format="{{ $printFormat }}">
                <div class="row justify-content-center mx-1">
                    <div class="card calculator-original-card">
                        <div class="card-header">
                            {{ $isScopeScreen
                                ? $t('ฉายลงจอสโคป', 'Projecting onto a Scope Screen')
                                : $t('ฉายลงจอตัดซีน', 'Projecting onto a Flat Screen') }}
                        </div>
                        <div class="card-body mx-1">
                            <div class="row mx-1">
                                <label class="awesome">{{ $t('ความยาวโฟกัส', 'Focal length') }}</label>
                                <input class="form-control" id="focal" name="focal" type="text" value="2.40">
                                <div class="form-group">
                                    <label class="calculator-inline-choice">
                                        <input checked name="focal_unit" type="radio" value="1">
                                        {{ $t('นิ้ว', 'Inches') }}
                                    </label>
                                    <label class="calculator-inline-choice">
                                        <input name="focal_unit" type="radio" value="0.03937008">
                                        {{ $t('มิลลิเมตร', 'Millimeters') }}
                                    </label>
                                </div>
                            </div>

                            <div class="row mx-1">
                                <label class="awesome">{{ $t('ความกว้างของจอ:', 'Screen width:') }}</label>
                                <input class="form-control" id="width" name="width" type="text" value="7">
                                <div class="form-group">
                                    <label class="calculator-inline-choice">
                                        <input checked name="width_unit" type="radio" value="1">
                                        {{ $t('เมตร', 'Meters') }}
                                    </label>
                                    <label class="calculator-inline-choice">
                                        <input name="width_unit" type="radio" value="3.28084">
                                        {{ $t('ฟุต', 'Feet') }}
                                    </label>
                                </div>
                            </div>

                            <div class="row justify-content-center mx-1">
                                <div class="row mx-1 justify-content-center">
                                    <a class="btn btn-outline-primary" href="#" id="cal" role="button">{{ $t('คำนวณ', 'Calculate') }}</a>
                                </div>
                            </div>

                            <hr>

                            <div class="row mx-1">
                                <svg class="calculator-throw-svg" viewBox="0 0 600 240" aria-label="{{ $t('ภาพตัวอย่างการคำนวณระยะฉาย', 'Throw calculator preview') }}">
                                    <g id="layer1">
                                        <rect x="70" y="85" width="36" height="70" class="legacy-svg-screen"></rect>
                                        <line x1="110" y1="120" x2="500" y2="70" class="legacy-svg-beam"></line>
                                        <line x1="110" y1="120" x2="500" y2="170" class="legacy-svg-beam"></line>
                                        <polygon points="500,55 500,185 520,185 520,55" class="legacy-svg-projector"></polygon>
                                        <text x="130" y="62" id="screen_width" class="legacy-svg-text">0.00 {{ $t('เมตร', 'm') }}</text>
                                        <text x="25" y="126" id="screen_height" class="legacy-svg-text">0.00 {{ $t('เมตร', 'm') }}</text>
                                        <g id="image_area_flat">
                                            <rect x="70" y="100" width="28" height="42" class="legacy-svg-secondary"></rect>
                                            <text x="135" y="96" id="screen_flat_width" class="legacy-svg-text legacy-svg-text--secondary">0.00 {{ $t('เมตร', 'm') }}</text>
                                        </g>
                                        <g id="image_scope">
                                            <rect x="70" y="92" width="28" height="56" class="legacy-svg-secondary"></rect>
                                            <text x="135" y="86" id="scope_label" class="legacy-svg-text legacy-svg-text--secondary">{{ $t('สโคป', 'Scope') }}</text>
                                            <text x="135" y="106" id="screen_scope_height" class="legacy-svg-text legacy-svg-text--secondary">0.00 {{ $t('เมตร', 'm') }}</text>
                                        </g>
                                        <text x="245" y="218" id="throw" class="legacy-svg-text">0.00 {{ $t('เมตร', 'm') }}</text>
                                    </g>
                                </svg>
                            </div>

                            <div class="calculator-summary">
                                <div class="row">{{ $t('ชนิดของจอ:', 'Screen type:') }} <p id="Lscreen_type">{{ $screenLabel }}</p></div>
                                <div class="row">{{ $t('ชนิดของฟิล์ม:', 'Print format:') }} <p id="Lprint_type">{{ $printFormatLabel }}</p></div>
                                <div class="row">{{ $t('ความกว้างของจอ:', 'Screen width:') }} <p id="Lscreen_width">0.00 {{ $t('เมตร', 'm') }}</p></div>
                                <div class="row">{{ $t('ความสูงของจอ:', 'Screen height:') }} <p id="Lscreen_height">0.00 {{ $t('เมตร', 'm') }}</p></div>
                                <div class="row">{{ $t('ระยะฉาย:', 'Throw distance:') }} <p id="Lthrow_distance">0.00 {{ $t('เมตร', 'm') }}</p></div>
                                <div class="row"><p id="Lmatch"></p></div>
                            </div>

                            <div class="calculator-actions calculator-actions--legacy">
                                <a class="btn btn-outline-secondary" href="{{ route('calculator.throw') }}">{{ $t('ย้อนกลับ', 'Back') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('pageScripts')
    @include('partials.calculator-i18n-script')
    <script src="{{ asset('js/legacy-projector-calculator-localized.js') }}" defer></script>
@endsection
