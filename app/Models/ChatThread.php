<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatThread extends Model {
  protected $fillable = ['title','author_id','is_locked'];
  public function author(): BelongsTo { return $this->belongsTo(User::class,'author_id'); }
  public function messages(): HasMany { return $this->hasMany(ChatMessage::class)->latest(); }
}
