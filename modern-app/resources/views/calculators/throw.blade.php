@extends('layouts.app')

@php
    $isScopeScreen = $screen === 'scope';
    $screenLabel = $isScopeScreen ? 'สโคป' : 'จอตัดซีน / Flat';
    $printFormatLabel = match ($printFormat) {
        'flat' => 'ตัดซีน / Flat',
        'both' => 'รองรับทั้งสโคปและตัดซีน',
        default => 'สโคป',
    };
@endphp

@section('content')
    <section class="panel legacy-calculator">
        <div class="panel__header">
            <h2>{{ $isScopeScreen ? 'คำนวณระยะฉายสำหรับจอสโคป' : 'คำนวณระยะฉายสำหรับจอตัดซีน' }}</h2>
        </div>
        <div class="panel__body">
            @include('partials.calculator-page-nav-localized')

            <div class="container-fluid" data-calculator-page="throw" data-throw-screen="{{ $screen }}" data-throw-print-format="{{ $printFormat }}">
                <div class="row justify-content-center mx-1">
                    <div class="card calculator-original-card">
                        <div class="card-header">{{ $isScopeScreen ? 'ฉายลงจอสโคป' : 'ฉายลงจอตัดซีน' }}</div>
                        <div class="card-body mx-1">
                            <div class="row mx-1">
                                <label class="awesome">ความยาวโฟกัส</label>
                                <input class="form-control" id="focal" name="focal" type="text" value="2.40">
                                <div class="form-group">
                                    <label class="calculator-inline-choice">
                                        <input checked name="focal_unit" type="radio" value="1">
                                        นิ้ว
                                    </label>
                                    <label class="calculator-inline-choice">
                                        <input name="focal_unit" type="radio" value="0.03937008">
                                        มิลลิเมตร
                                    </label>
                                </div>
                            </div>

                            <div class="row mx-1">
                                <label class="awesome">ความกว้างของจอ:</label>
                                <input class="form-control" id="width" name="width" type="text" value="7">
                                <div class="form-group">
                                    <label class="calculator-inline-choice">
                                        <input checked name="width_unit" type="radio" value="1">
                                        เมตร
                                    </label>
                                    <label class="calculator-inline-choice">
                                        <input name="width_unit" type="radio" value="3.28084">
                                        ฟุต
                                    </label>
                                </div>
                            </div>

                            <div class="row justify-content-center mx-1">
                                <div class="row mx-1 justify-content-center">
                                    <a class="btn btn-outline-primary" href="#" id="cal" role="button">คำนวณ</a>
                                </div>
                            </div>

                            <hr>

                            <div class="row mx-1">
                                <svg class="calculator-throw-svg" viewBox="0 0 600 240" aria-label="Throw calculator preview">
                                    <g id="layer1">
                                        <rect x="70" y="85" width="36" height="70" class="legacy-svg-screen"></rect>
                                        <line x1="110" y1="120" x2="500" y2="70" class="legacy-svg-beam"></line>
                                        <line x1="110" y1="120" x2="500" y2="170" class="legacy-svg-beam"></line>
                                        <polygon points="500,55 500,185 520,185 520,55" class="legacy-svg-projector"></polygon>
                                        <text x="130" y="62" id="screen_width" class="legacy-svg-text">0.00 เมตร</text>
                                        <text x="25" y="126" id="screen_height" class="legacy-svg-text">0.00 เมตร</text>
                                        <g id="image_area_flat">
                                            <rect x="70" y="100" width="28" height="42" class="legacy-svg-secondary"></rect>
                                            <text x="135" y="96" id="screen_flat_width" class="legacy-svg-text legacy-svg-text--secondary">0.00 เมตร</text>
                                        </g>
                                        <g id="image_scope">
                                            <rect x="70" y="92" width="28" height="56" class="legacy-svg-secondary"></rect>
                                            <text x="135" y="86" id="scope_label" class="legacy-svg-text legacy-svg-text--secondary">สโคป</text>
                                            <text x="135" y="106" id="screen_scope_height" class="legacy-svg-text legacy-svg-text--secondary">0.00 เมตร</text>
                                        </g>
                                        <text x="245" y="218" id="throw" class="legacy-svg-text">0.00 เมตร</text>
                                    </g>
                                </svg>
                            </div>

                            <div class="calculator-summary">
                                <div class="row">ชนิดของจอ: <p id="Lscreen_type">{{ $screenLabel }}</p></div>
                                <div class="row">ชนิดของฟิล์ม: <p id="Lprint_type">{{ $printFormatLabel }}</p></div>
                                <div class="row">ความกว้างของจอ: <p id="Lscreen_width">0.00 เมตร</p></div>
                                <div class="row">ความสูงของจอ: <p id="Lscreen_height">0.00 เมตร</p></div>
                                <div class="row">ระยะฉาย: <p id="Lthrow_distance">0.00 เมตร</p></div>
                                <div class="row"><p id="Lmatch"></p></div>
                            </div>

                            <div class="calculator-actions calculator-actions--legacy">
                                <a class="btn btn-outline-secondary" href="{{ route('calculator.throw') }}">ย้อนกลับ</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('pageScripts')
    <script src="{{ asset('js/legacy-projector-calculator.js') }}" defer></script>
@endsection
