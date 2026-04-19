@extends('layouts.app')

@php
    $isEnglish = app()->getLocale() === 'en';
    $t = static fn (string $th, string $en) => $isEnglish ? $en : $th;
@endphp

@section('content')
    <section class="panel legacy-calculator">
        <div class="panel__header">
            <h2>{{ $t('เครื่องคิดเลข', 'Calculators') }}</h2>
        </div>
        <div class="panel__body">
            @include('partials.calculator-page-nav-localized')

            <div class="container-fluid" data-calculator-page="throw-selector">
                <div class="row justify-content-center mx-1">
                    <div class="card calculator-original-card">
                        <div class="card-header">{{ $t('รูปแบบการฉาย', 'Projection Setup') }}</div>
                        <div class="card-body mx-1">
                            <div class="row mx-2">
                                <div class="form-group">
                                    <label class="awesome">{{ $t('ชนิดของจอ:', 'Screen type:') }}</label>
                                    <label class="calculator-inline-choice">
                                        <input checked name="screen" type="radio" value="scope">
                                        {{ $t('สโคป', 'Scope') }}
                                    </label>
                                    <label class="calculator-inline-choice">
                                        <input name="screen" type="radio" value="flat">
                                        {{ $t('จอตัดซีน', 'Flat') }}
                                    </label>
                                </div>
                            </div>

                            <div class="row mx-2">
                                <div class="form-group">
                                    <label class="awesome">{{ $t('ชนิดของฟิล์ม:', 'Print format:') }}</label>
                                    <label class="calculator-inline-choice">
                                        <input checked name="print_format" type="radio" value="scope">
                                        {{ $t('สโคป', 'Scope') }}
                                    </label>
                                    <label class="calculator-inline-choice">
                                        <input name="print_format" type="radio" value="flat">
                                        {{ $t('ตัดซีน', 'Flat') }}
                                    </label>
                                    <label class="calculator-inline-choice">
                                        <input name="print_format" type="radio" value="both">
                                        {{ $t('สโคป + ตัดซีน', 'Scope + Flat') }}
                                    </label>
                                </div>
                            </div>

                            <div class="row mx-1 justify-content-center">
                                <button class="btn btn-outline-primary text-center" id="btn1" type="button">{{ $t('หน้าถัดไป', 'Next') }}</button>
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
