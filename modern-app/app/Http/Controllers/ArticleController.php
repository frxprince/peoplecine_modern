<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Contracts\View\View;

class ArticleController extends Controller
{
    public function index(): View
    {
        return view('articles.index', [
            'articles' => Article::query()
                ->with(['category', 'author.profile'])
                ->orderByDesc('published_at')
                ->paginate(20),
        ]);
    }

    public function show(Article $article): View
    {
        $article->load(['category', 'author.profile', 'blocks.attachments']);

        return view('articles.show', [
            'article' => $article,
        ]);
    }
}
