@extends('layouts.app', ['title' => $title ?? 'Profile Settings'])

@section('content')
    @php($isThaiUi = app()->getLocale() === 'th')
    @php($t = static fn (string $thai, string $english): string => $isThaiUi ? $thai : $english)
    <section class="hero-panel auth-hero">
        <div>
            <p class="eyebrow">{{ $t('ตั้งค่าโปรไฟล์', 'Profile Settings') }}</p>
            <h1>{{ $t('จัดการข้อมูลบัญชีเดิมของคุณบนเว็บไซต์ใหม่', 'Manage how your legacy account lives in the new site.') }}</h1>
            <p class="lede">
                {{ $t('คุณยังสามารถเข้าสู่ระบบด้วยชื่อผู้ใช้และรหัสผ่านเดิมได้เหมือนเดิม หากบัญชีเก่าของคุณยังไม่มีอีเมล กรุณาเพิ่มไว้ที่นี่เพื่อให้รองรับการเข้าสู่ระบบด้วยอีเมลในครั้งถัดไป', 'You can keep signing in with your old username and password. If your legacy account did not include an email address, add one here so email-based sign-in becomes available on future visits.') }}
            </p>
        </div>

        <div class="callout">
            <strong>{{ $t('สถานะการเข้าสู่ระบบ', 'Sign-in status') }}</strong>
            <p>{{ $t('เข้าสู่ระบบด้วยชื่อผู้ใช้: ', 'Username login: ') }}{{ $user->username }}</p>
            <p>{{ $t('เข้าสู่ระบบด้วยอีเมล: ', 'Email login: ') }}{{ $user->email ?: $t('ยังไม่มี', 'Not available yet') }}</p>
        </div>
    </section>

    <section class="panel">
        <form class="form-stack" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="profile-avatar-panel">
                <div>
                    <strong>{{ $t('รูปประจำตัว', 'Avatar') }}</strong>
                    <p class="empty-state">{{ $t('รูป Pic1 เดิมจากระบบเก่ากลับมาใช้งานได้แล้ว และคุณสามารถอัปโหลดรูปใหม่ได้ที่นี่', 'Your legacy Pic1 image is available again, and you can upload a new image here.') }}</p>
                </div>

                <div class="profile-avatar-panel__preview">
                    @if ($user->avatarUrl())
                        <img class="profile-avatar-panel__image" src="{{ $user->avatarUrl() }}" alt="{{ $user->displayName() }} {{ $t('รูปประจำตัว', 'avatar') }}" loading="lazy">
                    @else
                        <div class="profile-avatar-panel__placeholder">{{ $t('ยังไม่มีรูปประจำตัว', 'No avatar yet') }}</div>
                    @endif
                </div>
            </div>

            <div class="section-grid">
                <div class="form-field">
                    <label for="display_name">{{ $t('ชื่อที่แสดง', 'Display Name') }}</label>
                    <input id="display_name" name="display_name" type="text" value="{{ old('display_name', $user->profile?->display_name) }}">
                    @error('display_name')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-field">
                    <label for="email">{{ $t('อีเมล', 'Email Address') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}">
                    @error('email')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-field">
                    <label for="phone">{{ $t('โทรศัพท์', 'Phone') }}</label>
                    <input id="phone" name="phone" type="text" value="{{ old('phone', $user->profile?->phone) }}">
                    @error('phone')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-field">
                    <label for="province">{{ $t('จังหวัด', 'Province') }}</label>
                    <input id="province" name="province" type="text" value="{{ old('province', $user->profile?->province) }}">
                    @error('province')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-field">
                    <label for="postal_code">{{ $t('รหัสไปรษณีย์', 'Postcode') }}</label>
                    <input id="postal_code" name="postal_code" type="text" value="{{ old('postal_code', $user->profile?->postal_code) }}">
                    @error('postal_code')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-field">
                    <label for="avatar">{{ $t('รูปประจำตัว', 'Avatar Image') }}</label>
                    <input id="avatar" name="avatar" type="file" accept=".jpg,.jpeg,.png,.gif,.bmp,.webp,image/*">
                    @error('avatar')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="form-field">
                <label for="address">{{ $t('ที่อยู่', 'Address') }}</label>
                <textarea id="address" name="address" rows="4">{{ old('address', $user->profile?->address) }}</textarea>
                @error('address')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <label class="checkbox-field" for="hide_address">
                <input
                    id="hide_address"
                    name="hide_address"
                    type="checkbox"
                    value="1"
                    @checked(old('hide_address', $user->profile?->hide_address))
                >
                {{ $t('ซ่อนที่อยู่ของฉันจากสมาชิกคนอื่น แต่ผู้ดูแลระบบยังมองเห็นได้', 'Hide my address from other members. Admins can still see it.') }}
            </label>

            <div class="form-field">
                <label for="biography">{{ $t('ประวัติส่วนตัว', 'Biography') }}</label>
                <textarea id="biography" name="biography" rows="6">{{ old('biography', $user->profile?->biography) }}</textarea>
                @error('biography')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="inline-actions">
                <button class="button" type="submit">{{ $t('บันทึกโปรไฟล์', 'Save profile') }}</button>
                <a class="button button--ghost" href="{{ route('dashboard') }}">{{ $t('กลับไปหน้าแดชบอร์ด', 'Back to dashboard') }}</a>
            </div>
        </form>
    </section>
@endsection
