<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? __('PeopleCine Modern') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/peoplecine-logo.png') }}">
    <link rel="shortcut icon" href="{{ asset('images/peoplecine-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/peoplecine-logo.png') }}">
    <link rel="stylesheet" href="{{ asset('css/peoplecine.css') }}">
</head>
<body>
    @php($cookieConsent = request()->cookie('peoplecine_cookie_consent'))
    @php($currentLocale = app()->getLocale())
    <div class="legacy-shell">
        <header class="legacy-header">
            <div class="legacy-header__banner">
                <div class="legacy-header__brand">
                    <a class="legacy-header__logo-link" href="{{ route('landing') }}">
                        <img
                            class="legacy-header__logo"
                            src="{{ asset('images/peoplecine-logo.png') }}"
                            alt="PeopleCine"
                            loading="eager"
                        >
                    </a>
                    <div>
                        <p class="legacy-header__tag">peoplecine.com</p>
                        <h1>{{ __('PeopleCine Main Forum') }}</h1>
                    <p class="legacy-header__subtitle">{{ __('Movie lovers community, rebuilt on Laravel with the original forum spirit kept intact.') }}</p>
                    </div>
                </div>
                <div class="legacy-header__stats">
<strong>{{ number_format($stats['users'] ?? 0) }}</strong>
                    <span>{{ __('members') }}</span>
                    <strong>{{ number_format($stats['topics'] ?? 0) }}</strong>
                    <span>{{ __('topics') }}</span>
                </div>
            </div>

            <nav class="legacy-topnav">
                <a href="{{ route('home') }}">{{ __('Main Forum') }}</a>
                <a href="{{ route('articles.index') }}">{{ __('Articles') }}</a>
                <a href="{{ route('search.index') }}">{{ __('Search') }}</a>
                <details class="legacy-topnav__group">
                    <summary>{{ __('Calculators') }}</summary>
                    <div class="legacy-topnav__submenu">
                        @include('partials.calculator-submenu-localized')
                    </div>
                </details>
                @auth
                    <a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
                    <a class="legacy-topnav__message-link" href="{{ route('messages.index') }}">
                        <span class="message-nav-icon" aria-hidden="true"></span>
                        <span>{{ __('Messages') }}</span>
                        @if (($unreadMessageCount ?? 0) > 0)
                            <span class="message-nav-badge">{{ $unreadMessageCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('profile.edit') }}">{{ __('Profile') }}</a>
                    <a href="{{ route('password.edit') }}">{{ __('Change Password') }}</a>
                    @if (auth()->user()?->isAdmin())
                        <details class="legacy-topnav__group">
                            <summary>{{ __('Admin') }}</summary>
                            <div class="legacy-topnav__submenu">
                                <a href="{{ route('admin.users.index') }}">{{ __('User Admin') }}</a>
                                <a href="{{ route('admin.rooms.index') }}">{{ __('Room Admin') }}</a>
                            </div>
                        </details>
                    @endif
                @else
                    <a href="{{ route('login') }}">{{ __('Member Login') }}</a>
                    <a href="{{ route('register') }}">{{ __('Register') }}</a>
                @endauth
                <div class="legacy-topnav__locale-group" aria-label="{{ __('Language switcher') }}">
                    <a
                        class="legacy-topnav__locale {{ $currentLocale === 'th' ? 'is-active' : '' }}"
                        href="{{ route('locale.switch', 'th') }}"
                        hreflang="th"
                        lang="th"
                        title="{{ __('Thai') }}"
                        aria-label="{{ __('Switch interface to Thai') }}"
                    >
                        <span class="legacy-topnav__locale-flag legacy-topnav__locale-flag--th" aria-hidden="true"></span>
                        <span class="legacy-topnav__locale-text">TH</span>
                    </a>
                    <a
                        class="legacy-topnav__locale {{ $currentLocale === 'en' ? 'is-active' : '' }}"
                        href="{{ route('locale.switch', 'en') }}"
                        hreflang="en"
                        lang="en"
                        title="{{ __('English') }}"
                        aria-label="{{ __('Switch interface to English') }}"
                    >
                        <span class="legacy-topnav__locale-flag legacy-topnav__locale-flag--en" aria-hidden="true"></span>
                        <span class="legacy-topnav__locale-text">EN</span>
                    </a>
                </div>
            </nav>
        </header>

        <div class="legacy-body">
            <aside class="legacy-sidebar">
                @auth
                    <section class="legacy-menu">
                        <h2>{{ __('Current Member') }}</h2>
                        <div class="legacy-member-card">
                            @if (auth()->user()->avatarUrl())
                                <img
                                    class="legacy-member-card__avatar"
                                    src="{{ auth()->user()->avatarUrl() }}"
                                    alt="{{ auth()->user()->displayName() }} avatar"
                                    loading="lazy"
                                >
                            @else
                                <div class="legacy-member-card__avatar legacy-member-card__avatar--empty">{{ __('No avatar') }}</div>
                            @endif

                            <div class="legacy-member-card__body">
                                <strong>{{ auth()->user()->username }}</strong>
                                <span class="legacy-member-card__meta">
                                    {{ __('Level') }}: {{ auth()->user()->memberLevel() }}
                                </span>
                                <span class="legacy-member-card__meta">
                                    {{ auth()->user()->memberLevelLabel() }}
                                </span>
                            </div>
                        </div>
                    </section>
                @endauth

                <section class="legacy-menu">
                    <h2>PEOPLECINE</h2>
                    <a href="{{ route('home') }}">{{ __('Main Forum') }}</a>
                    <a href="{{ route('articles.index') }}">{{ __('Article Archive') }}</a>
                    <a href="{{ route('search.index') }}">{{ __('Forum Search') }}</a>
                    <details class="legacy-menu__group">
                        <summary>{{ __('Calculators') }}</summary>
                        <div class="legacy-menu__submenu">
                            @include('partials.calculator-submenu-localized')
                        </div>
                    </details>
                    @auth
                        <a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
                        <a href="{{ route('messages.index') }}">
                            {{ __('Private Messages') }}
                            @if (($unreadMessageCount ?? 0) > 0)
                                <span class="sidebar-notify-badge">{{ $unreadMessageCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('profile.edit') }}">{{ __('Personal Info') }}</a>
                        @if (auth()->user()?->isAdmin())
                            <details class="legacy-menu__group">
                                <summary>{{ __('Admin') }}</summary>
                                <div class="legacy-menu__submenu">
                                    <a href="{{ route('admin.users.index') }}">{{ __('User Admin') }}</a>
                                    <a href="{{ route('admin.rooms.index') }}">{{ __('Room Admin') }}</a>
                                </div>
                            </details>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="sidebar-button" type="submit">{{ __('Log off') }}</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}">{{ __('Member Login') }}</a>
                        <a href="{{ route('register') }}">{{ __('Register') }}</a>
                    @endauth
                </section>

                <section class="legacy-menu">
                    <h2>{{ __('Forum Search') }}</h2>
                    <form class="legacy-search-form" method="GET" action="{{ route('search.index') }}">
                        <label class="sr-only" for="sidebar-search-input">{{ __('Search Topics and Replies') }}</label>
                        <input
                            id="sidebar-search-input"
                            class="legacy-search-form__input"
                            name="q"
                            type="search"
                            value="{{ request()->query('q') }}"
                            maxlength="120"
                            placeholder="{{ __('Search Topics and Replies') }}"
                        >
                        <button class="legacy-search-form__button" type="submit">{{ __('Search') }}</button>
                    </form>
                </section>

                <section class="legacy-menu">
                    <h2>{{ __('Forum Rooms') }}</h2>
                    @foreach (($sidebarRooms ?? collect()) as $sidebarRoom)
                        <a href="{{ route('rooms.show', $sidebarRoom) }}">{!! $sidebarRoom->coloredLocalizedNameHtml() !!}</a>
                    @endforeach
                </section>

                <section class="legacy-side-banners" aria-label="PeopleCine banners">
                    <a class="legacy-side-banner" href="{{ route('home') }}">
                        <img src="{{ asset('images/legacy/add_newbanner_small.png') }}" alt="PeopleCine banner" loading="lazy">
                    </a>
                    <a class="legacy-side-banner" href="{{ route('home') }}">
                        <img src="{{ asset('images/legacy/add_side_banner_mid.jpg') }}" alt="PeopleCine side banner" loading="lazy">
                    </a>
                    <a class="legacy-side-banner" href="{{ route('home') }}">
                        <img src="{{ asset('images/legacy/bigfilm_leftbanner.png') }}" alt="Big film banner" loading="lazy">
                    </a>
                </section>

                @auth
                    <section class="legacy-menu">
                        <h2>{{ __('Personal Menu') }}</h2>
                        <a href="{{ route('messages.index') }}">
                            {{ __('Inbox') }}
                            @if (($unreadMessageCount ?? 0) > 0)
                                <span class="sidebar-notify-badge">{{ $unreadMessageCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('messages.create') }}">{{ __('Write Message') }}</a>
                        <a href="{{ route('profile.edit') }}">{{ __('Edit Profile') }}</a>
                        <a href="{{ route('password.edit') }}">{{ __('Change Password') }}</a>
                        @if (auth()->user()?->isAdmin())
                            <details class="legacy-menu__group">
                                <summary>{{ __('Admin') }}</summary>
                                <div class="legacy-menu__submenu">
                                    <a href="{{ route('admin.users.index') }}">{{ __('User Admin') }}</a>
                                    <a href="{{ route('admin.rooms.index') }}">{{ __('Room Admin') }}</a>
                                </div>
                            </details>
                        @endif
                    </section>
                @endauth
            </aside>

            <main class="legacy-content">
            @if (session('status'))
                <div class="flash-banner" role="status">
                    {{ session('status') }}
                </div>
            @endif

            @yield('content')
            <div class="page-footer-nav">
                <button
                    class="page-footer-nav__button"
                    type="button"
                    onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.href = @js(route('home')); }"
                >
                    {{ app()->getLocale() === 'th' ? 'ย้อนกลับ' : __('Go Back') }}
                </button>
            </div>
            </main>
        </div>
    </div>
    @if ($cookieConsent === null)
        <section class="cookie-consent-banner" data-cookie-consent-banner>
            <div class="cookie-consent-banner__body">
                <strong>{{ __('Cookie Notice') }}</strong>
                <p>
                    {{ __('PeopleCine uses cookies for sign-in, saved preferences, and core site security. Choose how you want to continue.') }}
                </p>
            </div>
            <div class="cookie-consent-banner__actions">
                <button class="button button--small" type="button" data-cookie-consent="accepted">{{ __('Accept') }}</button>
                <button class="button button--ghost button--small" type="button" data-cookie-consent="essential">{{ __('Only Necessary') }}</button>
            </div>
        </section>
    @endif
    <script>
        window.peoplecineTinyMceBase = @json(asset('vendor/tinymce'));
    </script>
    <script src="{{ asset('js/cookie-consent.js') }}" defer></script>
    <script src="{{ asset('js/legacy-composer.js') }}" defer></script>
    <script src="{{ asset('js/staged-composer.js') }}" defer></script>
    @yield('pageScripts')
    <script src="{{ asset('vendor/tinymce/tinymce.min.js') }}" defer></script>
    <script src="{{ asset('js/legacy-tinymce.js') }}" defer></script>
</body>
</html>
