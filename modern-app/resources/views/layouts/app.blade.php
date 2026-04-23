<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @php($versionedAsset = static function (string $path): string {
        $assetUrl = asset($path);
        $fullPath = public_path($path);

        if (! is_file($fullPath)) {
            return $assetUrl;
        }

        return $assetUrl.'?v='.filemtime($fullPath);
    })
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? __('PeopleCine Modern') }}</title>
    <link rel="icon" type="image/png" href="{{ $versionedAsset('images/peoplecine-logo.png') }}">
    <link rel="shortcut icon" href="{{ $versionedAsset('images/peoplecine-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ $versionedAsset('images/peoplecine-logo.png') }}">
    <link rel="stylesheet" href="{{ $versionedAsset('css/peoplecine.css') }}">
</head>
<body>
    @php($cookieConsent = request()->cookie('peoplecine_cookie_consent'))
    @php($currentLocale = app()->getLocale())
    @php($changePasswordLabel = $currentLocale === 'th' ? 'เปลี่ยนรหัสผ่าน' : 'Change Password')
    @php($projectorManualLabel = $currentLocale === 'th' ? 'คู่มือโปรเจคเตอร์' : 'Projector manual')
    @php($bannerAdminLabel = $currentLocale === 'th' ? 'จัดการแบนเนอร์' : 'Banner Admin')
    @php($goBackLabel = $currentLocale === 'th' ? 'ย้อนกลับ' : 'Go Back')
    @php($memberCountLabel = $currentLocale === 'th' ? 'สมาชิก' : 'members')
    @php($topicCountLabel = $currentLocale === 'th' ? 'หัวข้อ' : 'topics')
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
                        <p class="legacy-header__subtitle">{{ __('Open-air cinema community') }}</p>
                    </div>
                </div>
                <div class="legacy-header__stats">
                    <strong>{{ number_format($headerStats['users'] ?? 0) }}</strong>
                    <span>{{ $memberCountLabel }}</span>
                    <strong>{{ number_format($headerStats['topics'] ?? 0) }}</strong>
                    <span>{{ $topicCountLabel }}</span>
                </div>
            </div>

            <nav class="legacy-topnav">
                <a href="{{ route('home') }}">{{ __('Main Forum') }}</a>
                <a href="{{ route('projector-manual.index') }}">{{ $projectorManualLabel }}</a>
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
                    <a href="{{ route('password.edit') }}">{{ $changePasswordLabel }}</a>
                    @if (auth()->user()?->isAdmin())
                        <details class="legacy-topnav__group">
                            <summary>{{ __('Admin') }}</summary>
                            <div class="legacy-topnav__submenu">
                                <a href="{{ route('admin.users.index') }}">{{ __('User Admin') }}</a>
                                <a href="{{ route('admin.rooms.index') }}">{{ __('Room Admin') }}</a>
                                <a href="{{ route('admin.banners.index') }}">{{ $bannerAdminLabel }}</a>
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
                    <a href="{{ route('projector-manual.index') }}">{{ $projectorManualLabel }}</a>
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
                                    <a href="{{ route('admin.banners.index') }}">{{ $bannerAdminLabel }}</a>
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
                    @foreach (($sidebarBanners ?? []) as $banner)
                        <div class="legacy-side-banner">
                            <img src="{{ $banner['url'] }}" alt="{{ $banner['alt'] }}" loading="lazy">
                        </div>
                    @endforeach
                </section>
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
                    {{ $goBackLabel }}
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
    @include('partials.image-viewer-modal')
    <script>
        window.peoplecineTinyMceBase = @json('/vendor/tinymce');
    </script>
    <script src="{{ $versionedAsset('js/cookie-consent.js') }}" defer></script>
    <script src="{{ $versionedAsset('js/legacy-image-viewer.js') }}" defer></script>
    <script src="{{ $versionedAsset('js/legacy-composer.js') }}" defer></script>
    <script src="{{ $versionedAsset('js/staged-composer.js') }}" defer></script>
    @yield('pageScripts')
    <script src="/vendor/tinymce/tinymce.min.js" defer></script>
    <script src="{{ $versionedAsset('js/legacy-tinymce.js') }}" defer></script>
</body>
</html>
