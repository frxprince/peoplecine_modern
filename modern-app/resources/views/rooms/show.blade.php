@extends('layouts.app', ['title' => $room->localizedName()])

@section('content')
    @php($maxPostImages = (int) config('peoplecine.post_image_limit', 12))
    @php($t = static fn (string $thai, string $english): string => app()->getLocale() === 'th' ? $thai : $english)

    <section class="panel panel--hero">
        <p class="eyebrow">{{ $t('ห้องเว็บบอร์ด', 'Forum Room') }}</p>
        <h1>
            {!! $room->coloredLocalizedNameHtml() !!}
            @if ($room->hasRecentActivity())
                @include('partials.new-indicator')
            @endif
        </h1>
        @if ($room->description)
            <div class="lede legacy-room-description">{!! $room->legacyDescriptionHtml() !!}</div>
        @endif
    </section>

    <section class="panel composer-panel">
        <div class="panel__header">
            <h2>{{ $t('ตั้งหัวข้อใหม่', 'Create New Topic') }}</h2>
            @auth
                <p>
                    @if (auth()->user()->canCreateTopic())
                        {{ $t('เริ่มต้นการสนทนาใหม่ในห้องนี้', 'Start a new discussion in this room.') }}
                    @else
                        {{ $t('ระดับสมาชิกปัจจุบันของคุณยังไม่สามารถตั้งหัวข้อใหม่ได้', 'Your current member level cannot create a new topic yet.') }}
                    @endif
                </p>
            @else
                <p>{{ $t('เข้าสู่ระบบเพื่อตั้งหัวข้อใหม่', 'Sign in to create a new topic.') }}</p>
            @endauth
        </div>

        @auth
            @if (auth()->user()->canCreateTopic())
                <form class="form-stack" method="POST" action="{{ route('rooms.topics.store', $room) }}" enctype="multipart/form-data" data-forum-validate data-require-title="true" data-require-body="true" data-error-title-required="{{ $t('กรุณาใส่ชื่อหัวข้อก่อนโพสต์', 'Please enter a topic title before posting.') }}" data-error-body-required="{{ $t('กรุณาใส่ข้อความหัวข้อก่อนโพสต์', 'Please enter a topic message before posting.') }}" novalidate>
                    @csrf

                    <div class="form-field" data-forum-field="title">
                        <label for="title">{{ $t('ชื่อหัวข้อ', 'Topic Title') }}</label>
                        <input id="title" name="title" type="text" value="{{ old('title') }}" maxlength="500" data-forum-required="title">
                        @error('title')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field" data-forum-field="body">
                        <label for="body_html">{{ $t('ข้อความ', 'Message') }}</label>
                        @include('partials.tinymce-editor', [
                            'id' => 'body_html',
                            'name' => 'body_html',
                            'label' => $t('ข้อความหัวข้อ', 'Topic Message'),
                            'rows' => 8,
                            'placeholder' => $t('พิมพ์ข้อความหัวข้อใหม่ที่นี่...', 'Write your new topic message here...'),
                        ])
                    </div>

                    @if (auth()->user()->canUploadImages())
                        <div class="form-field">
                            @include('partials.image-uploader-v2', [
                                'inputId' => 'topic-attachments',
                                'maxFiles' => $maxPostImages,
                                'hint' => $t("ลาก วาง หรือเลือกภาพได้สูงสุด {$maxPostImages} ภาพสำหรับหัวข้อนี้", "Drag and drop or select up to {$maxPostImages} images for this topic."),
                            ])
                            @error('attachments')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                            @error('attachments.*')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <div class="inline-actions">
                        <button class="button" type="submit">{{ $t('โพสต์หัวข้อ', 'Post Topic') }}</button>
                    </div>
                </form>
            @else
                <p class="empty-state">{{ $t('การตอบกระทู้เริ่มต้นที่ระดับ 1 และการตั้งหัวข้อใหม่เริ่มต้นที่ระดับ 3', 'Reply access starts at level 1. New topic access starts at level 3.') }}</p>
            @endif
        @else
            <div class="inline-actions">
                <a class="button" href="{{ route('login') }}">{{ $t('เข้าสู่ระบบเพื่อโพสต์', 'Sign in to post') }}</a>
            </div>
        @endauth
    </section>

    <section class="panel">
        <div class="panel__header">
            <h2>{{ $t('หัวข้อ', 'Topics') }}</h2>
            <p>{{ $t(number_format($topics->total()).' หัวข้อที่จัดเก็บไว้ในห้องนี้', number_format($topics->total()).' archived discussions in this room.') }}</p>
        </div>

        @include('partials.room-topic-table', ['topics' => $topics])

        <div class="pagination-wrap">
            {{ $topics->links() }}
        </div>
    </section>
@endsection
