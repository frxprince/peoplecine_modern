@extends('layouts.app', ['title' => $title ?? 'Member Dashboard'])

@section('content')
    @php($isThaiUi = app()->getLocale() === 'th')
    @php($t = static fn (string $thai, string $english): string => $isThaiUi ? $thai : $english)

    <section class="hero-panel">
        <div>
            <p class="eyebrow">{{ $t('แดชบอร์ดสมาชิก', 'Member Dashboard') }}</p>
            <h1>{{ $t('ยินดีต้อนรับกลับ, ', 'Welcome back, ') }}{{ $user->displayName() }}.</h1>
            <p class="lede">
                {{ $t('หน้านี้สรุปสถานะบัญชีและข้อมูลชุมชนที่ถูกย้ายมาจากระบบเดิม ให้คุณตรวจสอบภาพรวมของการใช้งานบนเว็บไซต์ใหม่ได้อย่างรวดเร็ว', 'This dashboard gives you a clean view of your active account and imported community footprint on the new site.') }}
            </p>
        </div>

        <div class="callout">
            <strong>{{ $t('สรุปบัญชี', 'Account Summary') }}</strong>
            <p>{{ $user->email ?? $t('ยังไม่มีอีเมลที่ยืนยันไว้', 'No verified email stored yet.') }}</p>
            <p>{{ $t('บทบาท', 'Role') }}: {{ $isThaiUi ? ($user->role === 'admin' ? 'ผู้ดูแลระบบ' : 'ผู้ใช้') : ucfirst($user->role) }}</p>
            <p>{{ $t('สถานะ', 'Status') }}: {{ $stats['profile_status'] }}</p>
            <p>{{ $t('ระดับสมาชิก', 'Member Level') }}: {{ $stats['member_level'] }}</p>
            <p>{{ $t('การตอบกระทู้', 'Reply Posting') }}: {{ $stats['can_reply'] ? $t('อนุญาต', 'Allowed') : $t('อ่านอย่างเดียว', 'Read only') }}</p>
            <p>{{ $t('การตั้งหัวข้อใหม่', 'New Topics') }}: {{ $stats['can_create_topic'] ? $t('อนุญาต', 'Allowed') : $t('ไม่อนุญาต', 'Not allowed') }}</p>
            <p>{{ $t('การอัปโหลดรูปภาพ', 'Image Uploads') }}: {{ $stats['can_upload_images'] ? $t('อนุญาต', 'Allowed') : $t('ไม่อนุญาต', 'Not allowed') }}</p>
            <p>{{ $t('สิทธิ์เข้าห้อง VIP', 'VIP Room Access') }}: {{ $stats['can_access_vip'] ? $t('ใช้งานได้', 'Available') : $t('ยังใช้งานไม่ได้', 'Not available') }}</p>
            <div class="inline-actions">
                <a class="button button--ghost button--small" href="{{ route('messages.index') }}">
                    {{ $t('ข้อความส่วนตัว', 'Private Messages') }}
                    @if (($unreadMessageCount ?? 0) > 0)
                        ({{ $unreadMessageCount }} {{ $t('ข้อความใหม่', 'new') }})
                    @endif
                </a>
                <a class="button button--ghost button--small" href="{{ route('password.edit') }}">{{ $t('เปลี่ยนรหัสผ่าน', 'Change Password') }}</a>
                <a class="button button--ghost button--small" href="{{ route('profile.edit') }}">{{ $t('แก้ไขโปรไฟล์', 'Edit Profile') }}</a>
                @if ($user->canAccessAdminPanel())
                    <a class="button button--ghost button--small" href="{{ route('admin.users.index') }}">{{ $t('จัดการผู้ใช้', 'User Management') }}</a>
                @endif
            </div>
        </div>
    </section>

    <section class="stats-grid stats-grid--dashboard">
        <article class="stat-card">
            <span class="stat-card__label">{{ $t('หัวข้อที่ตั้ง', 'Topics Started') }}</span>
            <strong class="stat-card__value">{{ number_format($stats['topics_started']) }}</strong>
        </article>
        <article class="stat-card">
            <span class="stat-card__label">{{ $t('โพสต์ที่เขียน', 'Posts Written') }}</span>
            <strong class="stat-card__value">{{ number_format($stats['posts_written']) }}</strong>
        </article>
        <article class="stat-card">
            <span class="stat-card__label">{{ $t('หัวข้อที่บันทึกไว้', 'Bookmarks') }}</span>
            <strong class="stat-card__value">{{ number_format($stats['bookmarks']) }}</strong>
        </article>
        <article class="stat-card">
            <span class="stat-card__label">{{ $t('ข้อความส่วนตัว', 'Private Messages') }}</span>
            <strong class="stat-card__value">{{ number_format($stats['private_messages']) }}</strong>
        </article>
        <article class="stat-card">
            <span class="stat-card__label">{{ $t('บทความ', 'Articles') }}</span>
            <strong class="stat-card__value">{{ number_format($stats['articles']) }}</strong>
        </article>
        <article class="stat-card">
            <span class="stat-card__label">{{ $t('ห้องที่ดูแล', 'Moderated Rooms') }}</span>
            <strong class="stat-card__value">{{ number_format($stats['moderated_rooms']) }}</strong>
        </article>
    </section>

    <section class="section-grid">
        <div class="panel">
            <div class="panel__header">
                <h2>{{ $t('ข้อความล่าสุด', 'Recent Messages') }}</h2>
                <p>{{ $t('บทสนทนาส่วนตัวล่าสุดของคุณ', 'Your latest private conversations.') }}</p>
            </div>

            <div class="stack-list">
                @forelse ($recentConversations as $conversation)
                    <a class="stack-card" href="{{ route('messages.show', $conversation) }}">
                        <div>
                            <strong>
                                {{ $conversation->subjectLineFor($user) }}
                                @if ($conversation->is_unread_for_viewer)
                                    <span class="badge">{{ $t('ใหม่', 'New') }}</span>
                                @endif
                            </strong>
                            <p>
                                {{ \Illuminate\Support\Str::limit(strip_tags((string) $conversation->latestMessage?->body_html), 120) ?: $t('ไม่มีข้อความ', 'No message text') }}
                            </p>
                        </div>
                        <span>{{ optional($conversation->latest_message_at)->format('d M Y') ?: $t('คลังข้อมูล', 'Archive') }}</span>
                    </a>
                @empty
                    <p class="empty-state">{{ $t('ยังไม่มีข้อความส่วนตัวที่เชื่อมกับบัญชีนี้', 'No private messages linked to this account yet.') }}</p>
                @endforelse
            </div>
        </div>

        <div class="panel">
            <div class="panel__header">
                <h2>{{ $t('หัวข้อล่าสุดของคุณ', 'Your Latest Topics') }}</h2>
                <p>{{ $t('กระทู้ที่คุณเป็นผู้ตั้งจากข้อมูลที่นำเข้าไว้', 'Imported discussions you originally started.') }}</p>
            </div>

            <div class="stack-list">
                @forelse ($recentTopics as $topic)
                    <a class="stack-card" href="{{ route('topics.show', $topic) }}">
                        <div>
                            <strong>{{ $topic->title }}</strong>
                            <p>{{ $topic->room?->localizedName() ?? $t('ห้องที่ถูกเก็บเข้าคลัง', 'Archived room') }}</p>
                        </div>
                        <span>{{ optional($topic->created_at)->format('d M Y') }}</span>
                    </a>
                @empty
                    <p class="empty-state">{{ $t('ยังไม่มีหัวข้อที่นำเข้าและเชื่อมกับบัญชีนี้', 'No imported topics are linked to this account yet.') }}</p>
                @endforelse
            </div>
        </div>

        <div class="panel">
            <div class="panel__header">
                <h2>{{ $t('ข้อความตอบล่าสุดของคุณ', 'Your Recent Replies') }}</h2>
                <p>{{ $t('กิจกรรมการตอบกระทู้ล่าสุดจากข้อมูลที่นำเข้าไว้', 'Latest imported posting activity tied to this account.') }}</p>
            </div>

            <div class="stack-list">
                @forelse ($recentPosts as $post)
                    <a class="stack-card" href="{{ route('topics.show', $post->topic) }}">
                        <div>
                            <strong>{{ $post->topic?->title ?? $t('หัวข้อที่ถูกเก็บเข้าคลัง', 'Archived topic') }}</strong>
                            <p>{{ \Illuminate\Support\Str::limit(strip_tags((string) $post->body_html), 120) }}</p>
                        </div>
                        <span>{{ optional($post->created_at)->format('d M Y') }}</span>
                    </a>
                @empty
                    <p class="empty-state">{{ $t('ยังไม่มีข้อความตอบที่นำเข้าและเชื่อมกับบัญชีนี้', 'No imported replies are linked to this account yet.') }}</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="panel__header">
            <h2>{{ $t('หัวข้อที่บันทึกไว้', 'Saved Topics') }}</h2>
            <p>{{ $t('รายการคั่นหน้าจากระบบเดิมที่นำเข้ามาในเว็บไซต์ใหม่', 'Legacy bookmarks imported into the new platform.') }}</p>
        </div>

        <div class="forum-table-wrap">
            <table class="forum-table forum-topic-table">
                <thead>
                    <tr>
                        <th width="40%">{{ $t('หัวข้อ', 'Topic') }}</th>
                        <th width="20%">{{ $t('ห้อง', 'Room') }}</th>
                        <th width="18%">{{ $t('ผู้เขียน', 'Author') }}</th>
                        <th width="16%">{{ $t('ล่าสุด', 'Last') }}</th>
                        <th width="6%">{{ $t('จัดการ', 'Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookmarkedTopics as $topic)
                        <tr>
                            <td>
                                <a class="forum-topic-link" href="{{ route('topics.show', $topic) }}">
                                    @if ($topic->hasPostedImage())
                                        @include('partials.camera-indicator')
                                    @endif
                                    {{ $topic->title }}
                                    @if ($topic->isNewlyPosted())
                                        @include('partials.new-indicator')
                                    @endif
                                </a>
                            </td>
                            <td>
                                @if ($topic->room)
                                    <a class="forum-room-link" href="{{ route('rooms.show', $topic->room) }}">
                                        {!! $topic->room->coloredLocalizedNameHtml() !!}
                                        @if ($topic->room->hasRecentActivity())
                                            @include('partials.new-indicator')
                                        @endif
                                    </a>
                                @else
                                    <span class="empty-state">{{ $t('ห้องที่ถูกเก็บเข้าคลัง', 'Archived room') }}</span>
                                @endif
                            </td>
                            <td>
                                @include('partials.author-badge', [
                                    'user' => $topic->author,
                                    'fallback' => $t('สมาชิกที่ถูกเก็บเข้าคลัง', 'Archived member'),
                                ])
                            </td>
                            <td>
                                <div class="forum-last-meta">
                                    {{ optional($topic->last_posted_at)->format('d M Y H:i') ?: $t('คลังข้อมูล', 'Archive') }}
                                </div>
                            </td>
                            <td class="forum-table__action">
                                <form method="POST" action="{{ route('topics.bookmarks.destroy', $topic) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="icon-button icon-button--danger" type="submit" title="{{ $t('ลบออกจากหัวข้อที่บันทึกไว้', 'Remove from Saved Topics') }}" aria-label="{{ $t('ลบออกจากหัวข้อที่บันทึกไว้', 'Remove from Saved Topics') }}">
                                        <span class="icon-button__trash" aria-hidden="true"></span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="forum-table__empty">{{ $t('ยังไม่มีหัวข้อที่บันทึกไว้สำหรับสมาชิกคนนี้', 'No bookmarks imported for this member.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
