<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    public $timestamps = false;
    protected $fillable = ['request_id','file_path','file_type','uploaded_at'];
    protected $casts = ['uploaded_at' => 'datetime'];

    public function request() {
        return $this->belongsTo(MaintenanceRequest::class, 'request_id');
    }
}
