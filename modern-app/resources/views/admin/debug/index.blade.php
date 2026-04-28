@extends('layouts.app', ['title' => app()->getLocale() === 'th' ? 'ดีบัก' : 'Debug'])

@section('content')
    @php($isThaiUi = app()->getLocale() === 'th')

    <section class="panel panel--hero">
        <p class="eyebrow">{{ $isThaiUi ? 'โปรแกรมเมอร์' : 'Programmer' }}</p>
        <h1>{{ $isThaiUi ? 'ดีบัก' : 'Debug' }}</h1>
        <p class="lede">
            {{ $isThaiUi
                ? 'เครื่องมือชุดนี้สงวนไว้สำหรับระดับโปรแกรมเมอร์ ใช้ตรวจสอบระบบและทดสอบการส่งอีเมลจากค่าตั้งค่า Laravel ปัจจุบัน'
                : 'These tools are reserved for programmer-level accounts. Use them to inspect the system and test the current Laravel mail configuration.' }}
        </p>
        <div class="inline-actions">
            <a class="button button--ghost button--small" href="{{ route('debug.statistics') }}">{{ $isThaiUi ? 'เปิดแดชบอร์ดสถิติ' : 'Open Statistics Dashboard' }}</a>
        </div>
    </section>

    <section class="panel">
        <div class="panel__header">
            <h2>{{ $isThaiUi ? 'ทดสอบอีเมล' : 'Mail Test' }}</h2>
            <p>{{ $isThaiUi ? 'ส่งอีเมลทดสอบขนาดเล็กจากค่าตั้งค่าเมลที่กำลังใช้งานอยู่' : 'Send a small test message using the active mail settings.' }}</p>
        </div>

        <form class="admin-search-form" method="POST" action="{{ route('debug.mail-test') }}">
            @csrf

            <label class="admin-search-form__label" for="mail-test-recipient">{{ $isThaiUi ? 'อีเมลผู้รับ' : 'Recipient email' }}</label>
            <input
                id="mail-test-recipient"
                class="admin-search-form__input"
                name="recipient_email"
                type="email"
                value="{{ old('recipient_email') }}"
                placeholder="peoplecine@drpaween.com"
                required
            >

            <label class="admin-search-form__label" for="mail-test-subject">{{ $isThaiUi ? 'หัวข้อ' : 'Subject' }}</label>
            <input
                id="mail-test-subject"
                class="admin-search-form__input"
                name="subject_line"
                type="text"
                value="{{ old('subject_line', 'PeopleCine mail test') }}"
                maxlength="160"
            >

            <label class="admin-search-form__label" for="mail-test-body">{{ $isThaiUi ? 'ข้อความ' : 'Message' }}</label>
            <textarea
                id="mail-test-body"
                class="admin-search-form__input"
                name="body_text"
                rows="4"
            >{{ old('body_text', "This is a test email from the PeopleCine debug panel.") }}</textarea>

            <button class="button button--small" type="submit">{{ $isThaiUi ? 'ส่งอีเมลทดสอบ' : 'Send Test Email' }}</button>
        </form>

        @error('mail_test')
            <p class="form-error">{{ $message }}</p>
        @enderror
        @error('recipient_email')
            <p class="form-error">{{ $message }}</p>
        @enderror
        @error('subject_line')
            <p class="form-error">{{ $message }}</p>
        @enderror
        @error('body_text')
            <p class="form-error">{{ $message }}</p>
        @enderror
    </section>
@endsection
