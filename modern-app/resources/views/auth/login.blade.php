@php($isThaiUi = app()->getLocale() === 'th')
@extends('layouts.app', ['title' => $isThaiUi ? 'เข้าสู่ระบบ' : __('Sign In')])

@section('content')
    <section class="auth-shell">
        <div class="hero-panel auth-hero">
            <div>
                <p class="eyebrow">{{ $isThaiUi ? 'ทางเข้าสำหรับสมาชิก' : __('Member Access') }}</p>
                <h1>{{ $isThaiUi ? 'เข้าสู่ระบบ PeopleCine เวอร์ชันใหม่' : __('Sign in to the rebuilt PeopleCine experience.') }}</h1>
                <p class="lede">
                    {{ $isThaiUi ? 'รหัสผ่าน PeopleCine เดิมของคุณยังใช้ได้บนเว็บไซต์ Laravel ใหม่ เข้าสู่ระบบด้วยชื่อผู้ใช้เดิม หรือใช้อีเมลได้ถ้าบัญชีนั้นมีอีเมลอยู่แล้ว' : __('Your old PeopleCine password now works in the new Laravel site. Sign in with your legacy username, or use your email address if that account already has one on file.') }}
                </p>
                <div class="callout">
                    <strong>{{ $isThaiUi ? 'บัญชีเดิมของคุณยังไม่มีอีเมลหรือไม่?' : __('No email on your old account?') }}</strong>
                    <p>{{ $isThaiUi ? 'ให้เข้าสู่ระบบด้วยชื่อผู้ใช้ก่อน แล้วจึงเพิ่มอีเมลได้จากหน้าตั้งค่าโปรไฟล์' : __('Sign in with your username first. After that, you can add an email address from your profile settings.') }}</p>
                </div>
                <div class="callout">
                    <strong>{{ $isThaiUi ? 'กฎสิทธิ์การใช้งานแบบเดิมยังถูกใช้อยู่' : __('Legacy access rules are active') }}</strong>
                    <p>{{ $isThaiUi ? 'สมาชิกระดับ 0 อ่านได้และใช้ข้อความส่วนตัวได้ ระดับ 1-2 ตอบกระทู้ได้ ระดับ 3 ตั้งกระทู้และอัปโหลดรูปได้ และระดับ 4 เข้าใช้ห้อง VIP ได้' : __('Level 0 members can read and use private messages. Levels 1-2 can reply. Level 3 can start topics and upload images. Level 4 members can access VIP rooms.') }}</p>
                </div>
                <div class="inline-actions">
                    <a class="button button--ghost button--small" href="{{ route('register') }}">{{ $isThaiUi ? 'สร้างบัญชีใหม่' : __('Create new account') }}</a>
                    <a class="button button--ghost button--small" href="{{ route('password.request') }}">{{ $isThaiUi ? 'ลืมรหัสผ่าน?' : __('Forgot password?') }}</a>
                </div>
            </div>

            <div class="panel panel--tight">
                <form class="form-stack" method="POST" action="{{ route('login.store') }}">
                    @csrf

                    <div class="form-field">
                        <label for="login">{{ $isThaiUi ? 'ชื่อผู้ใช้หรืออีเมล' : __('Username or Email') }}</label>
                        <input id="login" name="login" type="text" value="{{ old('login') }}" required autofocus>
                        @error('login')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="password">{{ $isThaiUi ? 'รหัสผ่าน' : __('Password') }}</label>
                        <input id="password" name="password" type="password">
                        @error('password')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="checkbox-field">
                        <input name="remember" type="checkbox" value="1" @checked(old('remember'))>
                        <span>{{ $isThaiUi ? 'ให้ฉันเข้าสู่ระบบค้างไว้บนอุปกรณ์นี้' : __('Keep me signed in on this device') }}</span>
                    </label>

                    <button class="button" type="submit">{{ $isThaiUi ? 'เข้าสู่ระบบ' : __('Sign in') }}</button>

                    <p class="form-helper">
                        {{ $isThaiUi ? 'ลืมรหัสผ่านใช่ไหม?' : __('Lost your password?') }}
                        <a href="{{ route('password.request') }}">{{ $isThaiUi ? 'ส่งลิงก์กู้คืนให้ตัวเอง' : __('Send yourself a recovery link') }}</a>.
                    </p>
                </form>
            </div>
        </div>
    </section>
@endsection
