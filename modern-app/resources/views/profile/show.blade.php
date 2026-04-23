@extends('layouts.app', ['title' => $title ?? ($profileUser->displayName().' Profile')])

@section('content')
    @php($isThaiUi = app()->getLocale() === 'th')
    @php($t = static fn (string $thai, string $english): string => $isThaiUi ? $thai : $english)
    @php($profile = $profileUser->profile)
    @php($addressVisible = $profile?->addressVisibleTo(auth()->user()) ?? false)
    @php($viewer = auth()->user())
    @php($isSelf = $viewer && (int) $viewer->id === (int) $profileUser->id)
    @php($isBlocked = $viewer?->hasBlockedUser($profileUser) ?? false)
    @php($isMuted = $viewer?->hasMutedUser($profileUser) ?? false)
    <section class="panel panel--hero">
        <p class="eyebrow">{{ $t('โปรไฟล์สมาชิก', 'Member Profile') }}</p>
        <h1>{{ $profileUser->displayName() }}</h1>
        <p class="lede">
            {{ $t('ชื่อผู้ใช้: ', 'Username: ') }}{{ $profileUser->username }} | {{ $profileUser->memberLevelLabel() }}
        </p>
        @auth
            @if (! $isSelf && auth()->user()?->canUsePrivateMessages())
                <div class="inline-actions">
                    @if ($profileUser->canUsePrivateMessages() && ! $isBlocked && ! ($profileUser->hasBlockedUser($viewer)))
                        <a class="button button--ghost button--small" href="{{ route('messages.create', ['to' => $profileUser->id]) }}">{{ $t('ส่งข้อความส่วนตัว', 'Send private message') }}</a>
                    @endif

                    @if ($isBlocked)
                        <form method="POST" action="{{ route('members.unblock', $profileUser) }}">
                            @csrf
                            @method('DELETE')
                            <button class="button button--ghost button--small" type="submit">{{ $t('เลิกบล็อกสมาชิก', 'Unblock member') }}</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('members.block', $profileUser) }}">
                            @csrf
                            <button class="button button--ghost button--small" type="submit">{{ $t('บล็อกสมาชิก', 'Block member') }}</button>
                        </form>
                    @endif

                    @if ($isMuted)
                        <form method="POST" action="{{ route('members.unmute', $profileUser) }}">
                            @csrf
                            @method('DELETE')
                            <button class="button button--ghost button--small" type="submit">{{ $t('เปิดการแจ้งเตือนอีกครั้ง', 'Unmute alerts') }}</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('members.mute', $profileUser) }}">
                            @csrf
                            <button class="button button--ghost button--small" type="submit">{{ $t('ปิดการแจ้งเตือน', 'Mute alerts') }}</button>
                        </form>
                    @endif
                </div>
            @endif
        @endauth
    </section>

    <section class="section-grid">
        <section class="panel panel--tight">
            <div class="profile-avatar-panel">
                <div>
                    <strong>{{ $t('รูปประจำตัว', 'Avatar') }}</strong>
                    <p class="empty-state">{{ $t('รูปโปรไฟล์เดิมจากคลังข้อมูลเว็บบอร์ดต้นฉบับ', 'Legacy profile image from the original forum archive.') }}</p>
                </div>

                <div class="profile-avatar-panel__preview">
                    @if ($profileUser->avatarUrl())
                        <img class="profile-avatar-panel__image" src="{{ $profileUser->avatarUrl() }}" alt="{{ $profileUser->displayName() }} {{ $t('รูปประจำตัว', 'avatar') }}" loading="lazy">
                    @else
                        <div class="profile-avatar-panel__placeholder">{{ $t('ไม่มีรูปประจำตัว', 'No avatar') }}</div>
                    @endif
                </div>
            </div>
        </section>

        <section class="panel panel--tight">
            <div class="stack-list">
                <div class="stack-card">
                    <div>
                        <strong>{{ $t('ระดับสมาชิก', 'Forum Level') }}</strong>
                        <p>{{ $profileUser->memberLevelLabel() }}</p>
                    </div>
                    <span class="badge">{{ $t('ระดับ ', 'Level ') }}{{ $profileUser->memberLevel() }}</span>
                </div>
                <div class="stack-card">
                    <div>
                        <strong>{{ $t('จังหวัด', 'Province') }}</strong>
                        <p>{{ $profile?->province ?: $t('ไม่ได้เปิดเผย', 'Not shared') }}</p>
                    </div>
                </div>
                <div class="stack-card">
                    <div>
                        <strong>{{ $t('รหัสไปรษณีย์', 'Postcode') }}</strong>
                        <p>
                            @if ($addressVisible)
                                {{ $profile?->postal_code ?: $t('ไม่ได้เปิดเผย', 'Not shared') }}
                            @else
                                {{ $t('สมาชิกซ่อนไว้', 'Hidden by member') }}
                            @endif
                        </p>
                    </div>
                </div>
                <div class="stack-card">
                    <div>
                        <strong>{{ $t('วันที่อยู่ในคลังข้อมูล', 'Joined Archive') }}</strong>
                        <p>{{ optional($profileUser->created_at)->format('d M Y H:i') ?: $t('คลังข้อมูล', 'Archive') }}</p>
                    </div>
                </div>
            </div>
        </section>
    </section>

    <section class="stats-grid stats-grid--dashboard">
        <article class="stat-card">
            <span class="stat-card__label">{{ $t('หัวข้อ', 'Topics') }}</span>
            <span class="stat-card__value">{{ number_format($profileUser->topics_count) }}</span>
        </article>
        <article class="stat-card">
            <span class="stat-card__label">{{ $t('โพสต์', 'Posts') }}</span>
            <span class="stat-card__value">{{ number_format($profileUser->posts_count) }}</span>
        </article>
    </section>

    <section class="panel">
        <div class="panel__header">
            <h2>{{ $t('ที่อยู่', 'Address') }}</h2>
        </div>
        <p class="lede">
            @if ($addressVisible)
                {{ $profile?->address ?: $t('ไม่มีข้อมูลที่อยู่', 'No address available.') }}
            @else
                {{ $t('สมาชิกท่านนี้ซ่อนข้อมูลที่อยู่ไว้', 'This member has hidden their address.') }}
            @endif
        </p>
    </section>

    <section class="panel">
        <div class="panel__header">
            <h2>{{ $t('ประวัติส่วนตัว', 'Biography') }}</h2>
        </div>
        <p class="lede">{{ $profileUser->profile?->biography ?: $t('ยังไม่มีข้อมูลประวัติส่วนตัว', 'No biography available.') }}</p>
    </section>
@endsection
