@extends('layouts.app', ['title' => __('PeopleCine Modern')])

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
@endsection
