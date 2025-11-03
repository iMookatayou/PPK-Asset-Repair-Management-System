<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model {
  protected $fillable = ['thread_id','user_id','body'];
  public function thread(): BelongsTo { return $this->belongsTo(ChatThread::class); }
  public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
