@extends('layouts.app', ['title' => __('Profile Settings')])

@section('content')
    <section class="hero-panel auth-hero">
        <div>
            <p class="eyebrow">{{ __('Profile Settings') }}</p>
            <h1>{{ __('Manage how your legacy account lives in the new site.') }}</h1>
            <p class="lede">
                {{ __('You can keep signing in with your old username and password. If your legacy account did not include an email address, add one here so email-based sign-in becomes available on future visits.') }}
            </p>
        </div>

        <div class="callout">
            <strong>{{ __('Sign-in status') }}</strong>
            <p>{{ __('Username login: :username', ['username' => $user->username]) }}</p>
            <p>{{ __('Email login: :email', ['email' => $user->email ?: __('Not available yet')]) }}</p>
        </div>
    </section>

    <section class="panel">
        <form class="form-stack" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="profile-avatar-panel">
                <div>
                    <strong>{{ __('Avatar') }}</strong>
                    <p class="empty-state">{{ __('Your legacy Pic1 image is available again, and you can upload a new image here.') }}</p>
                </div>

                <div class="profile-avatar-panel__preview">
                    @if ($user->avatarUrl())
                        <img class="profile-avatar-panel__image" src="{{ $user->avatarUrl() }}" alt="{{ $user->displayName() }} avatar" loading="lazy">
                    @else
                        <div class="profile-avatar-panel__placeholder">{{ __('No avatar yet') }}</div>
                    @endif
                </div>
            </div>

            <div class="section-grid">
                <div class="form-field">
                    <label for="display_name">{{ __('Display Name') }}</label>
                    <input id="display_name" name="display_name" type="text" value="{{ old('display_name', $user->profile?->display_name) }}">
                    @error('display_name')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-field">
                    <label for="email">{{ __('Email Address') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}">
                    @error('email')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-field">
                    <label for="phone">{{ __('Phone') }}</label>
                    <input id="phone" name="phone" type="text" value="{{ old('phone', $user->profile?->phone) }}">
                    @error('phone')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-field">
                    <label for="province">{{ __('Province') }}</label>
                    <input id="province" name="province" type="text" value="{{ old('province', $user->profile?->province) }}">
                    @error('province')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-field">
                    <label for="postal_code">{{ __('Postcode') }}</label>
                    <input id="postal_code" name="postal_code" type="text" value="{{ old('postal_code', $user->profile?->postal_code) }}">
                    @error('postal_code')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-field">
                    <label for="avatar">{{ __('Avatar Image') }}</label>
                    <input id="avatar" name="avatar" type="file" accept=".jpg,.jpeg,.png,.gif,.bmp,.webp,image/*">
                    @error('avatar')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="form-field">
                <label for="address">{{ __('Address') }}</label>
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
                {{ __('Hide my address from other members. Admins can still see it.') }}
            </label>

            <div class="form-field">
                <label for="biography">{{ __('Biography') }}</label>
                <textarea id="biography" name="biography" rows="6">{{ old('biography', $user->profile?->biography) }}</textarea>
                @error('biography')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="inline-actions">
                <button class="button" type="submit">{{ __('Save profile') }}</button>
                <a class="button button--ghost" href="{{ route('dashboard') }}">{{ __('Back to dashboard') }}</a>
            </div>
        </form>
    </section>
@endsection
