@extends('layouts.app')

@php
    $isEnglish = app()->getLocale() === 'en';
    $t = static fn (string $th, string $en) => $isEnglish ? $en : $th;
@endphp

@section('content')
    <section class="panel legacy-calculator">
        <div class="panel__header">
            <h2>{{ $t('คำนวณขนาดจอ', 'Screen Size Calculator') }}</h2>
        </div>
        <div class="panel__body">
            @include('partials.calculator-page-nav-localized')

            <div class="container-fluid" data-calculator-page="screendesign">
                <div class="row justify-content-center mx-1">
                    <div class="card calculator-original-card">
                        <div class="card-header">{{ $t('คำนวณขนาดจอ', 'Screen Size Calculator') }}</div>
                        <div class="card-body mx-1">
                            <div class="row mx-2">
                                <label class="awesome">{{ $t('ความกว้างของจอ:', 'Screen width:') }}</label>
                            </div>
                            <div class="row">
                                <input class="form-control" id="screen_width" name="screen_width" type="text" value="8">
                            </div>

                            <div class="row mx-2">
                                <label class="awesome">{{ $t('ชนิดของฟิล์ม:', 'Format type:') }}</label>
                            </div>

                            <div class="form-group">
                                <div class="row mx-2"><label class="calculator-block-choice"><input checked name="type" type="radio" value="35scope"> {{ $t('สโคป', 'Scope') }}</label></div>
                                <div class="row mx-2"><label class="calculator-block-choice"><input name="type" type="radio" value="35flat"> {{ $t('ตัดซีน', 'Flat') }}</label></div>
                                <div class="row mx-2"><label class="calculator-block-choice"><input name="type" type="radio" value="academy"> {{ $t('เต็มเฟรม ไม่ตัดซีน', 'Academy / Full frame') }}</label></div>
                                <div class="row mx-2"><label class="calculator-block-choice"><input name="type" type="radio" value="8mm"> {{ $t('8มม ธรรมดา ซุปเปอร์ 35', '8mm Standard / Super 35') }}</label></div>
                                <div class="row mx-2"><label class="calculator-block-choice"><input name="type" type="radio" value="super8"> {{ $t('8มม ซุปเปอร์', 'Super 8') }}</label></div>
                                <div class="row mx-2"><label class="calculator-block-choice"><input name="type" type="radio" value="16scope"> {{ $t('16มม สโคป', '16mm Scope') }}</label></div>
                                <div class="row mx-2"><label class="calculator-block-choice"><input name="type" type="radio" value="8scope"> {{ $t('8มม สโคป', '8mm Scope') }}</label></div>
                                <div class="row mx-2"><label class="calculator-block-choice"><input name="type" type="radio" value="wxga"> {{ $t('โปรเจคเตอร์ ไวด์สกรีน', 'Projector Widescreen') }}</label></div>
                                <div class="row mx-2"><label class="calculator-block-choice"><input name="type" type="radio" value="user"> {{ $t('อื่นๆ', 'Custom') }}</label></div>
                                <div class="row"><label class="awesome">{{ $t('สัดส่วนลักษณะ', 'Aspect ratio') }}</label></div>
                                <div class="row calculator-ratio-row">
                                    <input id="param1" name="param1" type="text" value="2.40">:
                                    <input id="param2" name="param2" type="text" value="1">
                                </div>
                            </div>

                            <div class="row justify-content-center mx-1">
                                <div class="row mx-1 justify-content-center">
                                    <a class="btn btn-outline-primary" href="#" id="cal" role="button">{{ $t('คำนวณ', 'Calculate') }}</a>
                                </div>
                            </div>
                            <hr>
                            <div class="row mx-2">
                                {{ $t('ความกว้างของจอ:', 'Screen width:') }} <div id="screen_area_width"></div>,
                                {{ $t('ความสูงของจอ:', 'Screen height:') }} <div id="screen_area_height"></div>
                            </div>
                            <div class="row mx-2">
                                <div class="col mx-2 text-center">
                                    <svg class="calculator-screen-svg" viewBox="0 0 400 400" id="svg" aria-label="{{ $t('ภาพจำลองขนาดจอ', 'Screen size preview') }}">
                                        <rect id="border" x="45" y="120" width="310" height="110" class="legacy-svg-projector"></rect>
                                        <rect id="screen" x="50" y="125" width="300" height="100" class="legacy-svg-screen-fill"></rect>
                                    </svg>
                                </div>
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
