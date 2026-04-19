@extends('layouts.app')

@section('content')
    <section class="panel legacy-calculator">
        <div class="panel__header">
            <h2>คำนวณขนาดจอ</h2>
        </div>
        <div class="panel__body">
            @include('partials.calculator-page-nav-localized')

            <div class="container-fluid" data-calculator-page="screendesign">
                <div class="row justify-content-center mx-1">
                    <div class="card calculator-original-card">
                        <div class="card-header">คำนวณขนาดจอ</div>
                        <div class="card-body mx-1">
                            <div class="row mx-2">
                                <label class="awesome">ความกว้างของจอ:</label>
                            </div>
                            <div class="row">
                                <input class="form-control" id="screen_width" name="screen_width" type="text" value="8">
                            </div>

                            <div class="row mx-2">
                                <label class="awesome">ชนิดของฟิล์ม:</label>
                            </div>

                            <div class="form-group">
                                <div class="row mx-2"><label class="calculator-block-choice"><input checked name="type" type="radio" value="35scope"> สโคป</label></div>
                                <div class="row mx-2"><label class="calculator-block-choice"><input name="type" type="radio" value="35flat"> ตัดซีน</label></div>
                                <div class="row mx-2"><label class="calculator-block-choice"><input name="type" type="radio" value="academy"> เต็มเฟรม ไม่ตัดซีน</label></div>
                                <div class="row mx-2"><label class="calculator-block-choice"><input name="type" type="radio" value="8mm"> 8มม ธรรมดา ซุปเปอร์ 35</label></div>
                                <div class="row mx-2"><label class="calculator-block-choice"><input name="type" type="radio" value="super8"> 8มม ซุปเปอร์</label></div>
                                <div class="row mx-2"><label class="calculator-block-choice"><input name="type" type="radio" value="16scope"> 16มม สโคป</label></div>
                                <div class="row mx-2"><label class="calculator-block-choice"><input name="type" type="radio" value="8scope"> 8มม สโคป</label></div>
                                <div class="row mx-2"><label class="calculator-block-choice"><input name="type" type="radio" value="wxga"> โปรเจคเตอร์ ไวด์สกรีน</label></div>
                                <div class="row mx-2"><label class="calculator-block-choice"><input name="type" type="radio" value="user"> อื่นๆ</label></div>
                                <div class="row"><label class="awesome">สัดส่วนลักษณะ</label></div>
                                <div class="row calculator-ratio-row">
                                    <input id="param1" name="param1" type="text" value="2.40">:
                                    <input id="param2" name="param2" type="text" value="1">
                                </div>
                            </div>

                            <div class="row justify-content-center mx-1">
                                <div class="row mx-1 justify-content-center">
                                    <a class="btn btn-outline-primary" href="#" id="cal" role="button">คำนวณ</a>
                                </div>
                            </div>
                            <hr>
                            <div class="row mx-2">
                                ความกว้างของจอ: <div id="screen_area_width"></div>,
                                ความสูงของจอ: <div id="screen_area_height"></div>
                            </div>
                            <div class="row mx-2">
                                <div class="col mx-2 text-center">
                                    <svg class="calculator-screen-svg" viewBox="0 0 400 400" id="svg">
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
    <script src="{{ asset('js/legacy-projector-calculator.js') }}" defer></script>
@endsection
