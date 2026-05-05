@extends('layouts.app', ['title' => app()->getLocale() === 'th' ? 'ตรวจสอบภาพ' : 'Image Inspector'])

@section('content')
    @php($t = static fn (string $thai, string $english): string => app()->getLocale() === 'th' ? $thai : $english)

    <section class="panel panel--hero">
        <p class="eyebrow">{{ $t('เครื่องมือตรวจสอบรูปภาพ', 'Image Inspector') }}</p>
        <h1>{{ $attachment->original_filename ?: $t('รูปภาพแนบโพสต์', 'Posted image') }}</h1>
        <p class="lede">
            {{ $t('เปิดรูปเต็มขนาด พร้อมดูข้อมูล EXIF และกราฟฮิสโตแกรมสี', 'Open the full-size image with EXIF metadata and color histogram.') }}
        </p>
        <div class="inline-actions">
            <a class="button button--small" href="{{ $imageUrl }}" target="_blank" rel="noopener noreferrer">
                {{ $t('เปิดไฟล์ภาพต้นฉบับ', 'Open original image') }}
            </a>
        </div>
    </section>

    <section class="panel">
        <div class="image-inspector-layout">
            <div class="image-inspector-preview">
                <img
                    id="inspector-image"
                    class="image-inspector-preview__image"
                    src="{{ $imageUrl }}"
                    alt="{{ $attachment->original_filename ?: $t('รูปภาพแนบโพสต์', 'Posted image') }}"
                    loading="eager"
                >
            </div>

            <div class="image-inspector-meta">
                <h2>{{ $t('ข้อมูลไฟล์', 'File details') }}</h2>
                <dl class="image-inspector-list">
                    <div>
                        <dt>{{ $t('ชนิดไฟล์', 'MIME Type') }}</dt>
                        <dd>{{ $mimeType }}</dd>
                    </div>
                    <div>
                        <dt>{{ $t('ขนาดภาพ', 'Dimensions') }}</dt>
                        <dd>
                            @if ($attachment->width && $attachment->height)
                                {{ number_format((int) $attachment->width) }} x {{ number_format((int) $attachment->height) }} px
                            @else
                                -
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt>{{ $t('ขนาดไฟล์', 'File size') }}</dt>
                        <dd>{{ number_format((int) $imageSizeBytes) }} bytes</dd>
                    </div>
                    <div>
                        <dt>EXIF</dt>
                        <dd>{{ $exifRows === [] ? $t('ไม่พบข้อมูล EXIF ในภาพนี้', 'No EXIF metadata found in this image.') : $t('พบข้อมูล EXIF', 'EXIF metadata available') }}</dd>
                    </div>
                </dl>

                <h2>{{ $t('ข้อมูล EXIF', 'EXIF Metadata') }}</h2>
                @if ($exifRows === [])
                    <p class="empty-state">{{ $t('ไม่พบข้อมูล EXIF หรือเซิร์ฟเวอร์ไม่รองรับฟังก์ชัน EXIF', 'No EXIF metadata found, or EXIF extension is not enabled on this server.') }}</p>
                @else
                    <table class="forum-table image-inspector-table">
                        <thead>
                            <tr>
                                <th>{{ $t('รายการ', 'Field') }}</th>
                                <th>{{ $t('ค่า', 'Value') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($exifRows as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td>{{ $row['value'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="panel__header">
            <h2>{{ $t('ฮิสโตแกรมสี', 'Image Histogram') }}</h2>
            <p>{{ $t('กราฟนี้คำนวณจากภาพต้นฉบับในเบราว์เซอร์', 'This histogram is calculated in your browser from the original image.') }}</p>
        </div>
        <canvas id="image-histogram-canvas" class="image-inspector-histogram" width="768" height="260" aria-label="{{ $t('กราฟฮิสโตแกรมสีของภาพ', 'Image color histogram') }}"></canvas>
        <div class="image-inspector-legend">
            <span class="image-inspector-legend__item image-inspector-legend__item--red">R</span>
            <span class="image-inspector-legend__item image-inspector-legend__item--green">G</span>
            <span class="image-inspector-legend__item image-inspector-legend__item--blue">B</span>
        </div>
    </section>
@endsection

@section('pageScripts')
    <script src="{{ asset('js/image-inspector.js') }}?v={{ @filemtime(public_path('js/image-inspector.js')) ?: time() }}" defer></script>
@endsection
