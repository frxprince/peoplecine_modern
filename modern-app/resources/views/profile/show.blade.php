@extends('layouts.app', ['title' => $title ?? ($profileUser->displayName().' Profile')])

@section('content')
    @php($isThaiUi = app()->getLocale() === 'th')
    @php($t = static fn (string $thai, string $english): string => $isThaiUi ? $thai : $english)
    @php($profile = $profileUser->profile)
    @php($viewer = auth()->user())
    @php($isSelf = $viewer && (int) $viewer->id === (int) $profileUser->id)
    @php($isBlocked = $viewer?->hasBlockedUser($profileUser) ?? false)
    @php($isMuted = $viewer?->hasMutedUser($profileUser) ?? false)
    @php($showAdminDetails = $showAdminDetails ?? false)
    @php($effectiveLastVisit = $profileUser->effective_last_visited_at ?? $profileUser->last_visited_at)

    <section class="panel panel--hero">
        <p class="eyebrow">{{ $t('โปรไฟล์สมาชิก', 'Member Profile') }}</p>
        <h1>{{ $profileUser->displayName() }}</h1>
        <p class="lede">
            {{ $t('ชื่อผู้ใช้: ', 'Username: ') }}{{ $profileUser->username }} | {{ $profileUser->memberLevelLabel() }} | {{ $t('เข้าใช้ล่าสุด: ', 'Last visit: ') }}{{ $effectiveLastVisit ? \Illuminate\Support\Carbon::parse((string) $effectiveLastVisit)->format('d M Y H:i') : $t('ยังไม่มีข้อมูล', 'No visits yet') }}
        </p>

        @if ($showAdminDetails)
            <div class="callout">
                <strong>{{ $t('มุมมองผู้ดูแลระบบ', 'Administrator view') }}</strong>
                <p>{{ $t('หน้านี้เปิดจาก User Admin จึงแสดงข้อมูลติดต่อเต็มรูปแบบของสมาชิกท่านนี้', 'This profile was opened from User Admin, so full contact details are visible to administrators.') }}</p>
            </div>
        @endif

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
                        <strong>{{ $t('ข้อมูลติดต่อ', 'Contact details') }}</strong>
                        @if ($showAdminDetails)
                            <p>{{ $t('ผู้ดูแลระบบสามารถเห็นอีเมล โทรศัพท์ รหัสไปรษณีย์ และที่อยู่เต็มรูปแบบได้จากมุมมองนี้', 'Administrators can see email, phone, postcode, and full address in this view.') }}</p>
                        @else
                            <p>{{ $t('อีเมลและที่อยู่จะไม่แสดงบนโปรไฟล์สาธารณะ', 'Email and address are hidden from public profiles.') }}</p>
                        @endif
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

    @if ($showAdminDetails)
        <section class="panel">
            <div class="panel__header">
                <h2>{{ $t('ข้อมูลติดต่อสำหรับผู้ดูแลระบบ', 'Administrator-only contact details') }}</h2>
            </div>

            <div class="stack-list">
                <div class="stack-card">
                    <div>
                        <strong>{{ $t('อีเมล', 'Email') }}</strong>
                        <p>{{ $profileUser->email ?: $t('ไม่มีอีเมล', 'No email') }}</p>
                    </div>
                </div>
                <div class="stack-card">
                    <div>
                        <strong>{{ $t('โทรศัพท์', 'Phone') }}</strong>
                        <p>{{ $profile?->phone ?: $t('ไม่มีข้อมูล', 'Not available') }}</p>
                    </div>
                </div>
                <div class="stack-card">
                    <div>
                        <strong>{{ $t('รหัสไปรษณีย์', 'Postcode') }}</strong>
                        <p>{{ $profile?->postal_code ?: $t('ไม่มีข้อมูล', 'Not available') }}</p>
                    </div>
                </div>
                <div class="stack-card">
                    <div>
                        <strong>{{ $t('ที่อยู่', 'Address') }}</strong>
                        <p>{{ $profile?->address ?: $t('ไม่มีข้อมูลที่อยู่', 'No address available.') }}</p>
                    </div>
                </div>
            </div>
        </section>
    @endif

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
            <h2>{{ $t('ประวัติส่วนตัว', 'Biography') }}</h2>
        </div>
        <p class="lede">{{ $profileUser->profile?->biography ?: $t('ยังไม่มีข้อมูลประวัติส่วนตัว', 'No biography available.') }}</p>
    </section>
@endsection
