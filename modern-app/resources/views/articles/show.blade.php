@extends('layouts.app', ['title' => $article->title])

@section('content')
    <section class="panel panel--hero">
        <p class="eyebrow">{{ $article->category?->name ?? __('Article') }}</p>
        <h1>{{ $article->title }}</h1>
        <p class="lede">
            {{ $article->source_name ? __('Original source: :source', ['source' => $article->source_name]).' | ' : '' }}
            {{ optional($article->published_at)->format('d M Y') ?: __('Archive') }}
        </p>
    </section>

    <section class="article-stack">
        @forelse ($article->blocks as $block)
            <article class="panel article-block">
                <div class="article-block__body">
                    {!! $block->renderedBodyHtml() !!}
                </div>

                @if ($block->attachments->isNotEmpty())
                    <div class="attachment-grid">
                        @foreach ($block->attachments as $attachment)
                            @if ($attachment->isImage() && $attachment->legacyUrl())
                                <a
                                    class="attachment-card attachment-card--image js-lightbox-item"
                                    href="{{ $attachment->legacyUrl() }}"
                                    data-lightbox-group="article-block-{{ $block->id }}"
                                    data-lightbox-caption="{{ $attachment->original_filename ?: __('Article image') }}"
                                >
                                    <img
                                        class="attachment-card__image"
                                        src="{{ $attachment->legacyUrl() }}"
                                        alt="{{ __('Article image') }}"
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
                <p class="empty-state">{{ __('This article has no imported content blocks yet.') }}</p>
            </div>
        @endforelse
    </section>
@endsection
