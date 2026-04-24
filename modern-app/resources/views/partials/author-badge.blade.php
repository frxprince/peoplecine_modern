@props([
    'user' => null,
    'fallback' => null,
    'strong' => false,
])

@php
    $fallback = $fallback ?? (app()->getLocale() === 'th' ? 'สมาชิกที่ถูกเก็บเข้าคลัง' : 'Archived member');
    $name = $user?->displayName() ?? $fallback;
    $avatarUrl = $user?->avatarUrl();
    $profileUrl = auth()->check() && $user !== null && auth()->user()?->canViewMemberProfiles()
        ? route('members.show', $user)
        : null;
@endphp

<span class="author-badge">
    @if ($profileUrl)
        <a class="author-badge__name-link" href="{{ $profileUrl }}">
            @if ($strong)
                <strong class="author-badge__name">{{ $name }}</strong>
            @else
                <span class="author-badge__name">{{ $name }}</span>
            @endif
        </a>
    @else
        @if ($strong)
            <strong class="author-badge__name">{{ $name }}</strong>
        @else
            <span class="author-badge__name">{{ $name }}</span>
        @endif
    @endif

    @if ($avatarUrl)
        <img class="author-badge__avatar" src="{{ $avatarUrl }}" alt="{{ $name }} avatar" loading="lazy">
    @endif
</span>
