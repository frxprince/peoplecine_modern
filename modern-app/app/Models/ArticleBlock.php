<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Support\LegacyHtmlFormatter;

class ArticleBlock extends Model
{
    protected $fillable = [
        'legacy_article_body_id',
        'article_id',
        'position_in_article',
        'body_html',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'attachable_id')
            ->where('attachable_type', 'article_block')
            ->orderBy('slot_no');
    }

    public function renderedBodyHtml(): string
    {
        return LegacyHtmlFormatter::linkify($this->body_html);
    }
}
