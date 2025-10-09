<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['request_id','user_id','action','note','created_at'];
    protected $casts = ['created_at' => 'datetime'];

    public function request() { return $this->belongsTo(MaintenanceRequest::class, 'request_id'); }
    public function user() { return $this->belongsTo(User::class); }
}
