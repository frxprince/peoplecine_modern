@extends('layouts.app', ['title' => __('Choose a New Password')])

@section('content')
    <section class="auth-shell">
        <div class="hero-panel auth-hero">
            <div>
                <p class="eyebrow">{{ __('Account Security') }}</p>
                <h1>{{ __('Choose a fresh password for :name.', ['name' => $user->displayName()]) }}</h1>
                <p class="lede">
                    {{ __('Use this page to replace your current password or complete any required security reset for your account.') }}
                </p>
            </div>

            <div class="panel panel--tight">
                <form class="form-stack" method="POST" action="{{ route('password.update') }}">
                    @csrf
                    @method('PUT')

                    @unless ($user->requiresPasswordReset())
                        <div class="form-field">
                            <label for="current_password">{{ __('Current Password') }}</label>
                            <input id="current_password" name="current_password" type="password" required>
                            @error('current_password')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    @endunless

                    <div class="form-field">
                        <label for="password">{{ __('New Password') }}</label>
                        <input id="password" name="password" type="password" required>
                        @error('password')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="password_confirmation">{{ __('Confirm New Password') }}</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required>
                    </div>

                    <button class="button" type="submit">{{ __('Save password') }}</button>
                </form>
            </div>
        </div>
    </section>
@endsection
