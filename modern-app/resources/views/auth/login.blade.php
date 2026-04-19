@extends('layouts.app', ['title' => __('Sign In')])

@section('content')
    <section class="auth-shell">
        <div class="hero-panel auth-hero">
            <div>
                <p class="eyebrow">{{ __('Member Access') }}</p>
                <h1>{{ __('Sign in to the rebuilt PeopleCine experience.') }}</h1>
                <p class="lede">
                    {{ __('Your old PeopleCine password now works in the new Laravel site. Sign in with your legacy username, or use your email address if that account already has one on file.') }}
                </p>
                <div class="callout">
                    <strong>{{ __('No email on your old account?') }}</strong>
                    <p>{{ __('Sign in with your username first. After that, you can add an email address from your profile settings.') }}</p>
                </div>
                <div class="callout">
                    <strong>{{ __('Legacy access rules are active') }}</strong>
                    <p>{{ __('Level 0 members can read and use private messages. Levels 1-2 can reply. Level 3 can start topics and upload images. Level 4 members can access VIP rooms.') }}</p>
                </div>
                <div class="inline-actions">
                    <a class="button button--ghost button--small" href="{{ route('register') }}">{{ __('Create new account') }}</a>
                    <a class="button button--ghost button--small" href="{{ route('password.request') }}">{{ __('Forgot password?') }}</a>
                </div>
            </div>

            <div class="panel panel--tight">
                <form class="form-stack" method="POST" action="{{ route('login.store') }}">
                    @csrf

                    <div class="form-field">
                        <label for="login">{{ __('Username or Email') }}</label>
                        <input id="login" name="login" type="text" value="{{ old('login') }}" required autofocus>
                        @error('login')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="password">{{ __('Password') }}</label>
                        <input id="password" name="password" type="password">
                        @error('password')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="checkbox-field">
                        <input name="remember" type="checkbox" value="1" @checked(old('remember'))>
                        <span>{{ __('Keep me signed in on this device') }}</span>
                    </label>

                    <button class="button" type="submit">{{ __('Sign in') }}</button>

                    <p class="form-helper">
                        {{ __('Lost your password?') }}
                        <a href="{{ route('password.request') }}">{{ __('Send yourself a recovery link') }}</a>.
                    </p>
                </form>
            </div>
        </div>
    </section>
@endsection
