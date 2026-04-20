@extends('layouts.app', ['title' => __('PeopleCine Modern')])

@section('content')
    @php
        $banners = [
            'landingpagebanner-01.png',
            'landingpagebanner-02.jpg',
            'landingpagebanner-03.jpg',
            'landingpagebanner-04.jpg',
            'landingpagebanner-05.jpg',
            'landingpagebanner-06.jpg',
            'landingpagebanner-07.jpg',
            'landingpagebanner-08.jpg',
            'landingpagebanner-09.jpg',
        ];
    @endphp

    <section class="landing-gallery" aria-label="PeopleCine landing banners">
        @foreach ($banners as $index => $banner)
            <figure class="landing-gallery__tile">
                <img
                    src="{{ asset('images/landingpagebanner/' . $banner) }}"
                    alt="Landing banner {{ $index + 1 }}"
                    loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                >
            </figure>
        @endforeach
    </section>
@endsection
