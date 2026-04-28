@extends('layouts.app', ['title' => $topic->title])

@section('content')
    @php($maxPostImages = (int) config('peoplecine.post_image_limit', 12))
    @php($t = static fn (string $thai, string $english): string => app()->getLocale() === 'th' ? $thai : $english)

    <section class="panel panel--hero">
        <p class="eyebrow">{{ $topic->room?->localizedName() ?? $t('การสนทนา', 'Discussion') }}</p>
        <h1>
            @if ($topic->hasPostedImage())
                @include('partials.camera-indicator')
            @endif
            {{ $topic->title }}
        </h1>
        <p class="lede">
            @if (app()->getLocale() === 'th')
                {{ number_format($topic->view_count) }} ครั้งอ่าน | {{ number_format($topic->reply_count) }} ครั้งตอบ | ล่าสุด {{ optional($topic->last_posted_at)->format('d M Y H:i') ?: 'คลังข้อมูล' }}
            @else
                {{ number_format($topic->view_count) }} views | {{ number_format($topic->reply_count) }} replies | Last activity {{ optional($topic->last_posted_at)->format('d M Y H:i') ?: 'Archive' }}
            @endif
        </p>
        <div class="inline-actions">
            <a
                class="icon-button icon-button--facebook"
                href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}"
                target="_blank"
                rel="noopener noreferrer"
                title="{{ __('Share this topic on Facebook') }}"
                aria-label="{{ __('Share this topic on Facebook') }}"
            >
                <span class="icon-button__facebook" aria-hidden="true"></span>
            </a>
        @auth
            @if (isset($isBookmarked) && $isBookmarked)
                <form method="POST" action="{{ route('topics.bookmarks.destroy', $topic) }}">
                    @csrf
                    @method('DELETE')
                    <button class="icon-button icon-button--pin icon-button--active" type="submit" title="{{ __('Remove from Saved Topics') }}" aria-label="{{ __('Remove from Saved Topics') }}">
                        <span class="icon-button__disk" aria-hidden="true"></span>
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('topics.bookmarks.store', $topic) }}">
                    @csrf
                    <button class="icon-button icon-button--pin" type="submit" title="{{ __('Save to Saved Topics') }}" aria-label="{{ __('Save to Saved Topics') }}">
                        <span class="icon-button__disk" aria-hidden="true"></span>
                    </button>
                </form>
            @endif

            @if (auth()->user()->canAccessAdminPanel())
                    @if ($topic->is_pinned)
                        <form method="POST" action="{{ route('topics.unpin', $topic) }}">
                            @csrf
                            @method('DELETE')
                            <button class="icon-button icon-button--topic-pin icon-button--active" type="submit" title="{{ __('Unpin topic') }}" aria-label="{{ __('Unpin topic') }}">
                                <span class="icon-button__pin" aria-hidden="true">📌</span>
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('topics.pin', $topic) }}">
                            @csrf
                            <button class="icon-button icon-button--topic-pin" type="submit" title="{{ __('Pin topic') }}" aria-label="{{ __('Pin topic') }}">
                                <span class="icon-button__pin" aria-hidden="true">📌</span>
                            </button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('topics.destroy', $topic) }}" onsubmit="return confirm(@js(__('Delete this topic and all replies?')));">
                        @csrf
                        @method('DELETE')
                        <button class="icon-button icon-button--danger" type="submit" title="{{ __('Delete topic') }}" aria-label="{{ __('Delete topic') }}">
                            <span class="icon-button__trash" aria-hidden="true"></span>
                        </button>
                    </form>
            @endif
        @endauth
        </div>
    </section>

    <section class="topic-thread">
        @forelse ($posts as $post)
            <article class="post-card" id="post-{{ $post->id }}">
                <div class="post-card__meta">
                    @if ($post->hasPostedImage())
                        @include('partials.camera-indicator')
                    @endif
                        @include('partials.author-badge', [
                            'user' => $post->author,
                            'fallback' => $t('สมาชิกที่ถูกเก็บเข้าคลัง', 'Archived member'),
                            'strong' => true,
                            'ipAddress' => $post->ip_address,
                        ])
                    <span>#{{ $post->position_in_topic }}</span>
                    <span>{{ optional($post->created_at)->format('d M Y H:i') ?: $t('คลังข้อมูล', 'Archive') }}</span>
                    @if ($post->wasEdited())
                        @php($latestChange = $post->changeLogs->first())
                        <span class="post-card__edited-note">
                            {{ $t('แก้ไขแล้ว', 'Edited') }} {{ $post->changeLogs->count() }} {{ $t($post->changeLogs->count() === 1 ? 'ครั้ง' : 'ครั้ง', $post->changeLogs->count() === 1 ? 'time' : 'times') }}
                            @if ($latestChange)
                                {{ $t('โดย', 'by') }} {{ $latestChange->editor?->displayName() ?? $t('สมาชิกที่ไม่ทราบชื่อ', 'Unknown member') }}
                                {{ $t('เมื่อ', 'on') }} {{ optional($latestChange->created_at)->format('d M Y H:i') }}
                            @endif
                        </span>
                        <details class="post-card__change-log">
                            <summary>{{ $t('ประวัติการแก้ไข', 'Change log') }}</summary>
                            <div class="post-card__change-log-list">
                                @foreach ($post->changeLogs as $changeLog)
                                    <div class="post-card__change-log-item">
                                        <strong>{{ $changeLog->summary }}</strong>
                                        <span>
                                            {{ $changeLog->editor?->displayName() ?? $t('สมาชิกที่ไม่ทราบชื่อ', 'Unknown member') }}
                                            | {{ optional($changeLog->created_at)->format('d M Y H:i') ?: $t('คลังข้อมูล', 'Archive') }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @endif
                    @auth
                        <div class="post-card__actions">
                        @if ($post->isEditableBy(auth()->user()))
                            <details class="post-card__edit-toggle" @if ((int) old('editing_post_id') === (int) $post->id) open @endif>
                                <summary class="post-card__edit-summary" title="{{ __('Edit your post') }}">
                                    <span class="post-card__edit-icon" aria-hidden="true"></span>
                                    <span>{{ __('Edit') }}</span>
                                </summary>
                                <div class="post-card__edit-panel">
                                    <form class="form-stack" method="POST" action="{{ route('topics.posts.update', [$topic, $post]) }}" enctype="multipart/form-data" data-forum-validate data-require-title="{{ $post->isTopicStarter() ? 'true' : 'false' }}" data-require-body="{{ $post->isTopicStarter() ? 'true' : 'false' }}" data-require-body-or-image="{{ $post->isTopicStarter() ? 'false' : 'true' }}" data-error-title-required="{{ $t('กรุณาใส่ชื่อหัวข้อก่อนบันทึก', 'Please enter a topic title before saving.') }}" data-error-body-required="{{ $t('กรุณาใส่ข้อความก่อนบันทึก', 'Please enter a message before saving.') }}" data-error-body-or-image-required="{{ $t('กรุณาใส่ข้อความตอบหรือคงรูปภาพไว้อย่างน้อย 1 รูปก่อนบันทึก', 'Please enter a reply message or keep at least one image before saving.') }}" novalidate>
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="editing_post_id" value="{{ $post->id }}">

                                        @if ($post->isTopicStarter())
                                            <div class="form-field" data-forum-field="title">
                                                <label for="topic_title_{{ $post->id }}">{{ __('Topic Title') }}</label>
                                                <input
                                                    id="topic_title_{{ $post->id }}"
                                                    name="topic_title"
                                                    type="text"
                                                    maxlength="500"
                                                    value="{{ old('topic_title', $topic->title) }}"
                                                    data-forum-required="title"
                                                >
                                                @error('topic_title')
                                                    <p class="form-error">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        @endif

                                        <div class="form-field" data-forum-field="body">
                                            <label for="message_body_{{ $post->id }}">{{ __('Message') }}</label>
                                            @include('partials.tinymce-editor', [
                                                'id' => 'message_body_'.$post->id,
                                                'name' => 'message_body',
                                                'label' => __('Edit Message'),
                                                'rows' => 7,
                                                'value' => $post->body_html,
                                                'errorKey' => 'message_body',
                                                'placeholder' => __('Edit your message here...'),
                                            ])
                                        </div>

                                        @if ($post->attachments->isNotEmpty())
                                            <div class="form-field">
                                                <label>{{ __('Current Images') }}</label>
                                                <div class="attachment-grid attachment-grid--manage">
                                                    @foreach ($post->attachments as $attachment)
                                                        <label class="attachment-card attachment-card--manage">
                                                            @if ($attachment->isImage() && $attachment->legacyUrl())
                                                                <img
                                                                    class="attachment-card__image"
                                                                    src="{{ $attachment->legacyUrl() }}"
                                        alt="{{ __('Current attachment') }}"
                                                                    loading="lazy"
                                                                >
                                                            @endif
                                                            <span class="attachment-card__name">{{ $attachment->original_filename }}</span>
                                                            <span class="attachment-card__toggle">
                                                                <input
                                                                    type="checkbox"
                                                                    name="remove_attachment_ids[]"
                                                                    value="{{ $attachment->id }}"
                                                                    @checked(in_array($attachment->id, old('remove_attachment_ids', [])))
                                                                >
                                                                {{ __('Remove this image') }}
                                                            </span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        @if (auth()->user()->canUploadImages())
                                            <div class="form-field">
                                                @include('partials.image-uploader-v2', [
                                                    'inputId' => 'edit-attachments-'.$post->id,
                                                    'maxFiles' => $maxPostImages,
                                                    'hint' => __('Upload replacement or additional images for post #:number.', ['number' => $post->position_in_topic]),
                                                ])
                                                @error('attachments')
                                                    <p class="form-error">{{ $message }}</p>
                                                @enderror
                                                @error('attachments.*')
                                                    <p class="form-error">{{ $message }}</p>
                                                @enderror
                                                @error('staged_uploads')
                                                    <p class="form-error">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        @endif

                                        <div class="inline-actions">
                                            <button class="button button--small" type="submit">{{ __('Save Changes') }}</button>
                                        </div>
                                    </form>
                                </div>
                            </details>
                        @endif
                        @if (auth()->user()->canAccessAdminPanel() && ! $post->isTopicStarter())
                            <form method="POST" action="{{ route('topics.posts.destroy', [$topic, $post]) }}" onsubmit="return confirm(@js(__('Delete this reply?')));">
                                @csrf
                                @method('DELETE')
                                <button class="icon-button icon-button--danger" type="submit" title="{{ __('Delete reply') }}" aria-label="{{ __('Delete reply') }}">
                                    <span class="icon-button__trash" aria-hidden="true"></span>
                                </button>
                            </form>
                        @endif
                        </div>
                    @endauth
                </div>

                <div class="post-card__body">
                    {!! $post->renderedBodyHtml() !!}
                </div>

                @if ($post->attachments->isNotEmpty())
                    <div class="attachment-grid">
                        @foreach ($post->attachments as $attachment)
                            @if ($attachment->isImage() && $attachment->legacyUrl())
                                <a
                                    class="attachment-card attachment-card--image js-lightbox-item"
                                    href="{{ $attachment->legacyUrl() }}"
                                    data-lightbox-group="post-{{ $post->id }}"
                                    data-lightbox-caption="{{ $attachment->original_filename ?: __('Posted image') }}"
                                >
                                    <img
                                        class="attachment-card__image"
                                        src="{{ $attachment->legacyUrl() }}"
                                        alt="{{ __('Posted image') }}"
                                        loading="lazy"
                                    >
                                </a>
                            @else
                                <div class="attachment-card">
                                    @if ($attachment->legacyUrl())
                                        <a class="attachment-card__name" href="{{ $attachment->legacyUrl() }}" target="_blank" rel="noopener noreferrer">
                                            {{ $attachment->original_filename }}
                                        </a>
                                    @else
                                        <span class="attachment-card__name">{{ $attachment->original_filename }}</span>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </article>
        @empty
            <div class="panel">
                <p class="empty-state">{{ $t('ยังไม่มีโพสต์ที่นำเข้าสำหรับหัวข้อนี้', 'No posts imported for this topic yet.') }}</p>
            </div>
        @endforelse
    </section>

    <section class="panel composer-panel">
        <div class="panel__header">
            <h2>{{ $t('ตอบกระทู้', 'Post Reply') }}</h2>
            @auth
                <p>
                    @if ($topic->is_locked && ! auth()->user()->canAccessAdminPanel())
                        {{ $t('หัวข้อนี้ถูกล็อกและไม่สามารถรับข้อความตอบใหม่ได้', 'This topic is locked and cannot accept new replies.') }}
                    @elseif (auth()->user()->canReply())
                        {{ $t('เพิ่มข้อความตอบของคุณในกระทู้นี้', 'Add your reply to this discussion.') }}
                    @else
                        {{ $t('ระดับสมาชิกปัจจุบันของคุณยังไม่สามารถตอบกระทู้ได้', 'Your current member level cannot post replies yet.') }}
                    @endif
                </p>
            @else
                <p>{{ $t('เข้าสู่ระบบเพื่อตอบกระทู้นี้', 'Sign in to reply to this topic.') }}</p>
            @endauth
        </div>

        @auth
            @if ($topic->is_locked && ! auth()->user()->canAccessAdminPanel())
                <p class="empty-state">{{ $t('หัวข้อนี้ถูกล็อก', 'This topic is locked.') }}</p>
            @elseif (auth()->user()->canReply())
                <form class="form-stack" method="POST" action="{{ route('topics.replies.store', $topic) }}" enctype="multipart/form-data" data-forum-validate data-require-body-or-image="true" data-error-body-or-image-required="{{ $t('กรุณาใส่ข้อความตอบหรืออัปโหลดรูปภาพอย่างน้อย 1 รูปก่อนโพสต์', 'Please enter a reply message or upload at least one image before posting.') }}" novalidate>
                    @csrf

                    <div class="form-field" data-forum-field="body">
                        <label for="body_html">{{ $t('ข้อความตอบ', 'Reply Message') }}</label>
                        @include('partials.tinymce-editor', [
                            'id' => 'body_html',
                            'name' => 'body_html',
                            'label' => $t('ข้อความตอบ', 'Reply Message'),
                            'rows' => 7,
                            'placeholder' => $t('พิมพ์ข้อความตอบที่นี่...', 'Write your reply here...'),
                        ])
                    </div>

                    @if (auth()->user()->canUploadImages())
                        <div class="form-field">
                            @include('partials.image-uploader-v2', [
                                'inputId' => 'reply-attachments',
                                'maxFiles' => $maxPostImages,
                                'hint' => $t("ลาก วาง หรือเลือกภาพได้สูงสุด {$maxPostImages} ภาพสำหรับข้อความตอบนี้", "Drag and drop or select up to {$maxPostImages} images for this reply."),
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
                        <button class="button" type="submit">{{ $t('โพสต์คำตอบ', 'Post Reply') }}</button>
                    </div>
                </form>
            @else
                <p class="empty-state">{{ $t('สิทธิการตอบกระทู้เริ่มต้นที่ระดับสมาชิก 1', 'Reply access starts at member level 1.') }}</p>
            @endif
        @else
            <div class="inline-actions">
                <a class="button" href="{{ route('login') }}">{{ $t('เข้าสู่ระบบเพื่อตอบ', 'Sign in to reply') }}</a>
            </div>
        @endauth
    </section>
@endsection
