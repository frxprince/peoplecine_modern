@extends('layouts.app', ['title' => __('Register')])

@section('content')
    <section class="auth-shell">
        <div class="hero-panel auth-hero">
            <div>
                <p class="eyebrow">{{ __('New Member') }}</p>
                <h1>{{ __('Create a new PeopleCine account.') }}</h1>
                <p class="lede">
                    {{ __('New registrations start as level 0 members. You can sign in right away, read the forum, and complete your profile after registration.') }}
                </p>
                <div class="callout">
                    <strong>{{ __('Built-in bot protection') }}</strong>
                    <p>{{ __('This form uses a hidden trap field, a quick human-check question, and registration throttling to reduce spam signups.') }}</p>
                </div>
                <div class="callout">
                    <strong>{{ __('Already have an old PeopleCine account?') }}</strong>
                    <p>{{ __('Use the sign-in page with your legacy username or email instead of creating a duplicate account.') }}</p>
                </div>
            </div>

            <div class="panel panel--tight">
                <form class="form-stack" method="POST" action="{{ route('register.store') }}">
                    @csrf

                    <div class="form-field">
                        <label for="username">{{ __('Username') }}</label>
                        <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus>
                        @error('username')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="display_name">{{ __('Display Name') }}</label>
                        <input id="display_name" name="display_name" type="text" value="{{ old('display_name') }}" required>
                        @error('display_name')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="email">{{ __('Email') }}</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required>
                        @error('email')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="password">{{ __('Password') }}</label>
                        <input id="password" name="password" type="password" required>
                        @error('password')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="password_confirmation">{{ __('Confirm Password') }}</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required>
                    </div>

                    <div class="form-field">
                        <label for="phone">{{ __('Phone') }}</label>
                        <input id="phone" name="phone" type="text" value="{{ old('phone') }}">
                        @error('phone')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="province">{{ __('Province') }}</label>
                        <input id="province" name="province" type="text" value="{{ old('province') }}">
                        @error('province')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="postcode">{{ __('Postcode') }}</label>
                        <input id="postcode" name="postcode" type="text" value="{{ old('postcode') }}">
                        @error('postcode')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="address">{{ __('Address') }}</label>
                        <textarea id="address" name="address" rows="3">{{ old('address') }}</textarea>
                        @error('address')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field honeypot-field" aria-hidden="true">
                        <label for="website">{{ __('Website') }}</label>
                        <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                        @error('website')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="human_check">{{ __('Human Check: :left + :right = ?', ['left' => $challengeLeft, 'right' => $challengeRight]) }}</label>
                        <input id="human_check" name="human_check" type="number" value="{{ old('human_check') }}" required>
                        @error('human_check')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="inline-actions">
                        <button class="button" type="submit">{{ __('Create Account') }}</button>
                        <a class="button button--ghost" href="{{ route('login') }}">{{ __('Already a member') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
