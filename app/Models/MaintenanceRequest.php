<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceRequest extends Model
{
    protected $fillable = [
        'asset_id','reporter_id','title','description',
        'priority','status','technician_id',
        'request_date','assigned_date','completed_date','remark'
    ];

    public function asset() { return $this->belongsTo(Asset::class); }
    public function reporter() { return $this->belongsTo(User::class, 'reporter_id'); }
    public function technician() { return $this->belongsTo(User::class, 'technician_id'); }
    public function attachments() { return $this->hasMany(Attachment::class, 'request_id'); }
    public function logs() { return $this->hasMany(MaintenanceLog::class, 'request_id'); }
}
