<nav class="calculator-page-nav" aria-label="Projector calculator navigation">
    <a
        class="calculator-page-nav__link {{ request()->routeIs('calculator.throw*') ? 'calculator-page-nav__link--active' : '' }}"
        href="{{ route('calculator.throw') }}"
    >
        {{ __('คำนวณระยะฉาย') }}
    </a>
    <a
        class="calculator-page-nav__link {{ request()->routeIs('calculator.lenssim') ? 'calculator-page-nav__link--active' : '' }}"
        href="{{ route('calculator.lenssim') }}"
    >
        {{ __('จำลองขนาดภาพจากคู่เลนส์') }}
    </a>
    <a
        class="calculator-page-nav__link {{ request()->routeIs('calculator.screendesign') ? 'calculator-page-nav__link--active' : '' }}"
        href="{{ route('calculator.screendesign') }}"
    >
        {{ __('คำนวณขนาดจอ') }}
    </a>
</nav>
