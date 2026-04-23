@extends('layouts.app', ['title' => app()->getLocale() === 'th' ? 'เปลี่ยนรหัสผ่าน' : __('Choose a New Password')])

@php($isThaiUi = app()->getLocale() === 'th')
@php($t = static fn (string $thai, string $english): string => $isThaiUi ? $thai : $english)

@section('content')
    <section class="auth-shell">
        <div class="hero-panel auth-hero">
            <div>
                <p class="eyebrow">{{ $t('ความปลอดภัยของบัญชี', 'Account Security') }}</p>
                <h1>
                    {{ $isThaiUi
                        ? 'ตั้งรหัสผ่านใหม่สำหรับ '.$user->displayName()
                        : __('Choose a fresh password for :name.', ['name' => $user->displayName()]) }}
                </h1>
                <p class="lede">
                    {{ $t('ใช้หน้านี้เพื่อเปลี่ยนรหัสผ่านปัจจุบันของคุณ หรือทำการรีเซ็ตรหัสผ่านที่ระบบร้องขอให้เสร็จสิ้น', 'Use this page to replace your current password or complete any required security reset for your account.') }}
                </p>
            </div>

            <div class="panel panel--tight">
                <form class="form-stack" method="POST" action="{{ route('password.update') }}">
                    @csrf
                    @method('PUT')

                    @unless ($user->requiresPasswordReset())
                        <div class="form-field">
                            <label for="current_password">{{ $t('รหัสผ่านปัจจุบัน', 'Current Password') }}</label>
                            <input id="current_password" name="current_password" type="password" required>
                            @error('current_password')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    @endunless

                    <div class="form-field">
                        <label for="password">{{ $t('รหัสผ่านใหม่', 'New Password') }}</label>
                        <input id="password" name="password" type="password" required>
                        @error('password')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="password_confirmation">{{ $t('ยืนยันรหัสผ่านใหม่', 'Confirm New Password') }}</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required>
                    </div>

                    <button class="button" type="submit">{{ $t('บันทึกรหัสผ่านใหม่', 'Save New Password') }}</button>
                </form>
            </div>
        </div>
    </section>
@endsection
