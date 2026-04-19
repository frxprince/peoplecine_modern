@extends('layouts.app')

@section('content')
    <section class="panel legacy-calculator">
        <div class="panel__header">
            <h2>เครื่องคิดเลข</h2>
        </div>
        <div class="panel__body">
            @include('partials.calculator-page-nav-localized')

            <div class="container-fluid" data-calculator-page="throw-selector">
                <div class="row justify-content-center mx-1">
                    <div class="card calculator-original-card">
                        <div class="card-header">รูปแบบการฉาย</div>
                        <div class="card-body mx-1">
                            <div class="row mx-2">
                                <div class="form-group">
                                    <label class="awesome">ชนิดของจอ:</label>
                                    <label class="calculator-inline-choice">
                                        <input checked name="screen" type="radio" value="scope">
                                        สโคป
                                    </label>
                                    <label class="calculator-inline-choice">
                                        <input name="screen" type="radio" value="flat">
                                        จอตัดซีน
                                    </label>
                                </div>
                            </div>

                            <div class="row mx-2">
                                <div class="form-group">
                                    <label class="awesome">ชนิดของฟิล์ม:</label>
                                    <label class="calculator-inline-choice">
                                        <input checked name="print_format" type="radio" value="scope">
                                        สโคป
                                    </label>
                                    <label class="calculator-inline-choice">
                                        <input name="print_format" type="radio" value="flat">
                                        ตัดซีน
                                    </label>
                                    <label class="calculator-inline-choice">
                                        <input name="print_format" type="radio" value="both">
                                        สโคป + ตัดซีน
                                    </label>
                                </div>
                            </div>

                            <div class="row mx-1 justify-content-center">
                                <button class="btn btn-outline-primary text-center" id="btn1" type="button">หน้าถัดไป</button>
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
