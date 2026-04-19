<nav class="calculator-page-nav" aria-label="{{ __('Projector calculator navigation') }}">
    <a
        class="calculator-page-nav__link {{ request()->routeIs('calculator.throw*') ? 'calculator-page-nav__link--active' : '' }}"
        href="{{ route('calculator.throw') }}"
    >
        {{ __('Throw Distance Calculator') }}
    </a>
    <a
        class="calculator-page-nav__link {{ request()->routeIs('calculator.lenssim') ? 'calculator-page-nav__link--active' : '' }}"
        href="{{ route('calculator.lenssim') }}"
    >
        {{ __('Lens Pair Image Simulator') }}
    </a>
    <a
        class="calculator-page-nav__link {{ request()->routeIs('calculator.screendesign') ? 'calculator-page-nav__link--active' : '' }}"
        href="{{ route('calculator.screendesign') }}"
    >
        {{ __('Screen Size Calculator') }}
    </a>
</nav>
