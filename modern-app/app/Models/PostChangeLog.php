<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostChangeLog extends Model
{
    protected $fillable = [
        'post_id',
        'editor_user_id',
        'summary',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_user_id');
    }
}
