@php
    $isThai = app()->getLocale() === 'th';
    $pageTitle = $isThai ? 'คู่มือโปรเจคเตอร์' : 'Projector manual';
    $eyebrow = $isThai ? 'คลังเอกสาร' : 'Document Archive';
    $lede = $isThai
        ? 'รวมไฟล์ PDF คู่มือและเอกสารเกี่ยวกับเครื่องฉายจากคลังเดิมของ PeopleCine'
        : 'A curated PDF archive of projector manuals and related legacy PeopleCine documents.';
    $emptyState = $isThai
        ? 'ยังไม่มีไฟล์คู่มือโปรเจคเตอร์ในระบบ'
        : 'No projector manual PDFs are available yet.';
    $openLabel = $isThai ? 'เปิดไฟล์ PDF' : 'Open PDF';
    $sizeLabel = $isThai ? 'ขนาดไฟล์' : 'File size';
@endphp

@extends('layouts.app', ['title' => $pageTitle])

@section('content')
    <section class="panel panel--hero">
        <p class="eyebrow">{{ $eyebrow }}</p>
        <h1>{{ $pageTitle }}</h1>
        <p class="lede">{{ $lede }}</p>
    </section>

    <section class="panel">
        <div class="stack-list">
            @forelse ($manuals as $manual)
                <a class="stack-card" href="{{ $manual['url'] }}" target="_blank" rel="noopener noreferrer">
                    <div>
                        <strong>{{ $manual['name'] }}</strong>
                        <p>{{ $sizeLabel }}: {{ number_format(($manual['size_bytes'] ?? 0) / 1024, 1) }} KB</p>
                    </div>
                    <span>{{ $openLabel }}</span>
                </a>
            @empty
                <p class="empty-state">{{ $emptyState }}</p>
            @endforelse
        </div>
    </section>
@endsection
