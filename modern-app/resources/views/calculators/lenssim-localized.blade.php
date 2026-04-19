@extends('layouts.app')

@php
    $isEnglish = app()->getLocale() === 'en';
    $t = static fn (string $th, string $en) => $isEnglish ? $en : $th;
@endphp

@section('content')
    <section class="panel legacy-calculator">
        <div class="panel__header">
            <h2>{{ $t('จำลองขนาดภาพจากคู่เลนส์', 'Lens Pair Image Simulator') }}</h2>
        </div>
        <div class="panel__body">
            @include('partials.calculator-page-nav-localized')

            <div class="container-fluid" data-calculator-page="lenssim">
                <div class="row justify-content-center mx-1">
                    <div class="card calculator-original-card">
                        <div class="card-header">{{ $t('จำลองขนาดภาพจากคู่เลนส์', 'Lens Pair Image Simulator') }}</div>
                        <div class="card-body mx-1">
                            <div class="row mx-1">
                                <label class="awesome">{{ $t('ความยาวโฟกัสของเลนส์ที่ใช้ในการฉายสโคป', 'Focal length for scope projection') }}</label>
                                <input class="form-control" id="focal_scope" name="focal_scope" type="text" value="2.40">
                                <div class="form-group">
                                    <label class="calculator-inline-choice"><input checked name="focal_scope_unit" type="radio" value="1"> {{ $t('นิ้ว', 'Inches') }}</label>
                                    <label class="calculator-inline-choice"><input name="focal_scope_unit" type="radio" value="0.03937008"> {{ $t('มิลลิเมตร', 'Millimeters') }}</label>
                                </div>
                            </div>

                            <div class="row mx-1">
                                <label class="awesome">{{ $t('ความยาวโฟกัสของเลนส์ที่ใช้ในการฉายตัดซีน', 'Focal length for flat projection') }}</label>
                                <input class="form-control" id="focal_flat" name="focal_flat" type="text" value="2.40">
                                <div class="form-group">
                                    <label class="calculator-inline-choice"><input checked name="focal_flat_unit" type="radio" value="1"> {{ $t('นิ้ว', 'Inches') }}</label>
                                    <label class="calculator-inline-choice"><input name="focal_flat_unit" type="radio" value="0.03937008"> {{ $t('มิลลิเมตร', 'Millimeters') }}</label>
                                </div>
                            </div>

                            <div class="row justify-content-center mx-1">
                                <div class="row mx-1 justify-content-center">
                                    <a class="btn btn-outline-primary" href="#" id="cal" role="button">{{ $t('คำนวณ', 'Calculate') }}</a>
                                </div>
                            </div>

                            <hr>

                            <div class="row mx-2">
                                <div class="col mx-2 text-center">
                                    <svg class="calculator-lens-svg" viewBox="0 0 400 400" id="svg" aria-label="{{ $t('ภาพจำลองขนาดภาพจากคู่เลนส์', 'Lens pair image preview') }}">
                                        <rect id="scope" x="50" y="56" width="300" height="128" class="legacy-svg-frame"></rect>
                                        <text id="scopetxt" x="66" y="82" class="legacy-svg-text">{{ $t('สโคป (scope)', 'Scope') }}</text>
                                        <rect id="academy" x="125" y="56" width="150" height="128" class="legacy-svg-academy"></rect>
                                        <rect id="flat" x="76" y="120" width="200" height="108" class="legacy-svg-secondary"></rect>
                                        <text id="flattxt" x="86" y="212" class="legacy-svg-text legacy-svg-text--secondary">{{ $t('ตัดซีน (flat)', 'Flat') }}</text>
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
