@extends('layouts.app', ['title' => $title ?? 'Register'])

@section('content')
    @php($isThaiUi = app()->getLocale() === 'th')
    @php($t = static fn (string $thai, string $english): string => $isThaiUi ? $thai : $english)
    <section class="auth-shell">
        <div class="hero-panel auth-hero">
            <div>
                <p class="eyebrow">{{ $t('สมาชิกใหม่', 'New Member') }}</p>
                <h1>{{ $t('สร้างบัญชี PeopleCine ใหม่', 'Create a new PeopleCine account.') }}</h1>
                <p class="lede">
                    {{ $t('สมาชิกที่สมัครใหม่จะเริ่มต้นที่ระดับ 0 คุณสามารถเข้าสู่ระบบได้ทันที อ่านเว็บบอร์ด และค่อยกลับมาเติมข้อมูลโปรไฟล์ภายหลังได้', 'New registrations start as level 0 members. You can sign in right away, read the forum, and complete your profile after registration.') }}
                </p>
                <div class="callout">
                    <strong>{{ $t('มีบัญชี PeopleCine เดิมอยู่แล้ว?', 'Already have an old PeopleCine account?') }}</strong>
                    <p>{{ $t('ให้ใช้หน้าลงชื่อเข้าใช้ด้วยชื่อผู้ใช้เดิมหรืออีเมลเดิมแทนการสร้างบัญชีซ้ำ', 'Use the sign-in page with your legacy username or email instead of creating a duplicate account.') }}</p>
                </div>
            </div>

            <div class="panel panel--tight">
                <form class="form-stack" method="POST" action="{{ route('register.store') }}">
                    @csrf

                    <div class="form-field">
                        <label for="username">{{ $t('ชื่อผู้ใช้', 'Username') }}</label>
                        <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus>
                        @error('username')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="display_name">{{ $t('ชื่อที่แสดง', 'Display Name') }}</label>
                        <input id="display_name" name="display_name" type="text" value="{{ old('display_name') }}" required>
                        @error('display_name')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="email">{{ $t('อีเมล', 'Email') }}</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required>
                        @error('email')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="password">{{ $t('รหัสผ่าน', 'Password') }}</label>
                        <input id="password" name="password" type="password" required>
                        @error('password')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="password_confirmation">{{ $t('ยืนยันรหัสผ่าน', 'Confirm Password') }}</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required>
                    </div>

                    <div class="form-field">
                        <label for="phone">{{ $t('โทรศัพท์', 'Phone') }}</label>
                        <input id="phone" name="phone" type="text" value="{{ old('phone') }}">
                        @error('phone')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="province">{{ $t('จังหวัด', 'Province') }}</label>
                        <input id="province" name="province" type="text" value="{{ old('province') }}">
                        @error('province')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="postcode">{{ $t('รหัสไปรษณีย์', 'Postcode') }}</label>
                        <input id="postcode" name="postcode" type="text" value="{{ old('postcode') }}">
                        @error('postcode')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="address">{{ $t('ที่อยู่', 'Address') }}</label>
                        <textarea id="address" name="address" rows="3">{{ old('address') }}</textarea>
                        @error('address')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field honeypot-field" aria-hidden="true">
                        <label for="website">{{ $t('เว็บไซต์', 'Website') }}</label>
                        <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                        @error('website')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="captcha">{{ $t('ตรวจสอบความปลอดภัย', 'Security Check') }}</label>
                        <div class="captcha-field">
                            <img
                                class="captcha-field__image"
                                src="{{ route('register.captcha', ['token' => $captchaToken]) }}"
                                alt="{{ $t('ภาพ CAPTCHA', 'CAPTCHA image') }}"
                                width="220"
                                height="74"
                            >
                            <a class="button button--ghost button--small captcha-field__refresh" href="{{ route('register') }}">{{ $t('โหลดรหัสใหม่', 'Load a new code') }}</a>
                        </div>
                        <p class="forum-last-meta">{{ $isThaiUi ? 'กรอกตัวอักษร '.$captchaLength.' ตัวที่เห็นในภาพ' : 'Enter the '.$captchaLength.' characters shown in the image.' }}</p>
                        <input id="captcha" name="captcha" type="text" value="{{ old('captcha') }}" maxlength="6" inputmode="text" autocomplete="off" autocapitalize="characters" spellcheck="false" required>
                        @error('captcha')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="inline-actions">
                        <button class="button" type="submit">{{ $t('สร้างบัญชี', 'Create Account') }}</button>
                        <a class="button button--ghost" href="{{ route('login') }}">{{ $t('เป็นสมาชิกอยู่แล้ว', 'Already a member') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
