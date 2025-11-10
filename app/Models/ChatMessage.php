<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_thread_id',
        'user_id',
        'body',
    ];

    protected $touches = ['thread'];

    protected $casts = [
        'chat_thread_id' => 'integer',
        'user_id'        => 'integer',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ChatThread::class, 'chat_thread_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeInThread(Builder $q, int $threadId): Builder
    {
        return $q->where('chat_thread_id', $threadId);
    }

    public function scopeLatestFirst(Builder $q): Builder
    {
        return $q->orderByDesc('created_at');
    }

    public function scopeAfterId(Builder $q, int $afterId): Builder
    {
        return $q->where('id', '>', $afterId);
    }
}
