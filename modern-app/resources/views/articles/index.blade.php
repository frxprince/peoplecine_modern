@extends('layouts.app', ['title' => 'Articles'])

@section('content')
    <section class="panel panel--hero">
        <p class="eyebrow">{{ __('Knowledge Archive') }}</p>
        <h1>{{ __('PeopleCine Articles') }}</h1>
        <p class="lede">{{ __('Imported long-form content from the legacy article system, presented in a cleaner modern archive.') }}</p>
    </section>

    <section class="panel">
        <div class="stack-list">
            @forelse ($articles as $article)
                <a class="stack-card" href="{{ route('articles.show', $article) }}">
                    <div>
                        <strong>{{ $article->title }}</strong>
                        <p>{{ $article->body_preview ?: __('Imported article body is available in the archive.') }}</p>
                    </div>
                    <span>{{ optional($article->published_at)->format('d M Y') ?: __('Archive') }}</span>
                </a>
            @empty
                <p class="empty-state">{{ __('No imported articles yet.') }}</p>
            @endforelse
        </div>

        <div class="pagination-wrap">
            {{ $articles->links() }}
        </div>
    </section>
@endsection
