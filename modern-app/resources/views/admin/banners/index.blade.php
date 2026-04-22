@extends('layouts.app', ['title' => app()->getLocale() === 'th' ? 'จัดการแบนเนอร์' : __('Banner Management')])

@php
    $isThaiUi = app()->getLocale() === 'th';
    $t = static fn (string $thai, string $english): string => $isThaiUi ? $thai : $english;
@endphp

@section('content')
    <section class="panel panel--hero">
        <p class="eyebrow">{{ __('Admin') }}</p>
        <h1>{{ $t('จัดการแบนเนอร์', 'Banner Management') }}</h1>
        <p class="lede">
            {{ $t('จัดการแบนเนอร์ฝั่งซ้ายและแบนเนอร์หน้าแรกได้จากหน้านี้ สามารถเพิ่มรูป ลบรูปเดิม และปรับลำดับการแสดงผลได้', 'Manage the left panel banners and the landing page banner tiles here. You can add images, delete old ones, and change their display order.') }}
        </p>
        <div class="inline-actions">
            <a class="button button--ghost button--small" href="{{ route('admin.users.index') }}">{{ $t('จัดการสมาชิก', 'User Management') }}</a>
            <a class="button button--ghost button--small" href="{{ route('admin.rooms.index') }}">{{ $t('จัดการห้องเว็บบอร์ด', 'Room Management') }}</a>
        </div>
    </section>

    <section class="panel panel--tight">
        <div class="panel__header">
            <h2>{{ $t('เพิ่มแบนเนอร์ใหม่', 'Add New Banner') }}</h2>
            <p>{{ $t('อัปโหลดรูปแบนเนอร์ไปยังฝั่งซ้ายหรือแกลเลอรีหน้าแรก', 'Upload a banner image to either the left panel or the landing page gallery.') }}</p>
        </div>

        <form class="form-stack" method="POST" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="admin-room-create-grid">
                <div class="form-field">
                    <label for="banner_section">{{ $t('ตำแหน่งแบนเนอร์', 'Banner Area') }}</label>
                    <select id="banner_section" name="section">
                        <option value="sidebar" @selected(old('section', 'sidebar') === 'sidebar')>{{ $t('แบนเนอร์ฝั่งซ้าย', 'Left Panel Banners') }}</option>
                        <option value="landing" @selected(old('section') === 'landing')>{{ $t('แบนเนอร์หน้าแรก', 'Landing Page Banners') }}</option>
                    </select>
                </div>

                <div class="form-field">
                    <label for="banner_alt">{{ $t('ข้อความกำกับรูป', 'Alt Text') }}</label>
                    <input id="banner_alt" name="alt" type="text" value="{{ old('alt') }}" maxlength="255" placeholder="{{ $t('ใส่หรือไม่ก็ได้', 'Optional descriptive label') }}">
                </div>
            </div>

            <div class="form-field">
                <label for="banner_image">{{ $t('รูปแบนเนอร์', 'Banner Image') }}</label>
                <input id="banner_image" name="image" type="file" accept="image/*" required>
            </div>

            @if ($errors->any())
                @foreach ($errors->all() as $message)
                    <p class="form-error">{{ $message }}</p>
                @endforeach
            @endif

            <div class="inline-actions">
                <button class="button" type="submit">{{ $t('อัปโหลดแบนเนอร์', 'Upload Banner') }}</button>
            </div>
        </form>
    </section>

    @foreach ($bannerSections as $sectionKey => $section)
        <section class="panel">
            <div class="panel__header">
                <h2>{{ $section['label'] }}</h2>
                <p>{{ $t('มีแบนเนอร์ในส่วนนี้ :count รูป', ':count banners in this section.') }}</p>
            </div>

            <div class="banner-admin-list">
                @forelse ($section['items'] as $banner)
                    <article class="banner-admin-card">
                        <div class="banner-admin-card__preview">
                            <img src="{{ $banner['url'] }}" alt="{{ $banner['alt'] }}" loading="lazy">
                        </div>

                        <div class="banner-admin-card__body">
                            <form class="form-stack" method="POST" action="{{ route('admin.banners.update', [$sectionKey, $banner['id']]) }}">
                                @csrf
                                @method('PUT')

                                <div class="form-field">
                                    <label for="banner_alt_{{ $banner['id'] }}">{{ $t('ข้อความกำกับรูป', 'Alt Text') }}</label>
                                    <input
                                        id="banner_alt_{{ $banner['id'] }}"
                                        name="alt"
                                        type="text"
                                        maxlength="255"
                                        value="{{ old('alt', $banner['alt']) }}"
                                    >
                                </div>

                                <div class="form-field">
                                    <label for="banner_sort_{{ $banner['id'] }}">{{ $t('ลำดับการแสดงผล', 'Sort Order') }}</label>
                                    <input
                                        id="banner_sort_{{ $banner['id'] }}"
                                        name="sort_order"
                                        type="number"
                                        min="0"
                                        max="99999"
                                        value="{{ old('sort_order', $banner['sort_order']) }}"
                                        required
                                    >
                                </div>

                                <div class="inline-actions">
                                    <button class="button button--small" type="submit">{{ $t('บันทึก', 'Save') }}</button>
                                </div>
                            </form>

                            <form method="POST" action="{{ route('admin.banners.destroy', [$sectionKey, $banner['id']]) }}" onsubmit="return confirm(@js($t('ลบแบนเนอร์นี้ใช่หรือไม่?', 'Delete this banner?')));">
                                @csrf
                                @method('DELETE')
                                <button class="button button--small button--danger" type="submit">{{ $t('ลบ', 'Delete') }}</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <p class="empty-state">{{ $t('ยังไม่มีแบนเนอร์ในส่วนนี้', 'No banners configured for this section yet.') }}</p>
                @endforelse
            </div>
        </section>
    @endforeach
@endsection
