<?php

namespace App\Models;

use App\Support\LegacyHtmlFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'legacy_membermessage_id',
        'conversation_id',
        'sender_id',
        'body_html',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function renderedBodyHtml(): string
    {
        return LegacyHtmlFormatter::linkify($this->body_html);
    }
}
