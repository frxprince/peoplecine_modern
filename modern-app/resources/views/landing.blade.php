@extends('layouts.app', ['title' => __('PeopleCine Modern')])

@php($isThaiUi = app()->getLocale() === 'th')
@php($t = static fn (string $thai, string $english): string => $isThaiUi ? $thai : $english)

@section('content')
    <section class="landing-gallery" aria-label="PeopleCine landing banners">
        @foreach (($landingBanners ?? []) as $index => $banner)
            <figure class="landing-gallery__tile">
                <img
                    src="{{ $banner['url'] }}"
                    alt="{{ $banner['alt'] }}"
                    loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                >
            </figure>
        @endforeach
    </section>

    <section class="legacy-panel landing-visitors">
        <div class="legacy-panel__header">
            <h2>{{ $t('ผู้เยี่ยมชมล่าสุด', 'Latest visitors') }}</h2>
            <p>{{ $t('แสดง 20 ผู้เยี่ยมชมล่าสุด โดยผู้ที่ยังไม่เข้าสู่ระบบจะแสดงเป็นเลขไอพี', 'Showing the 20 most recent visitors. Guests are shown by IP address.') }}</p>
        </div>

        @if (($recentVisitors ?? collect())->isNotEmpty())
            <p class="landing-visitors__inline">
                @foreach (($recentVisitors ?? collect()) as $visitor)
                    {{ $visitor['label'] }}@if (! $loop->last), @endif
                @endforeach
            </p>
        @else
            <p class="empty-state">{{ $t('ยังไม่มีข้อมูลผู้เยี่ยมชมล่าสุด', 'No recent visitor activity yet.') }}</p>
        @endif
    </section>
@endsection
