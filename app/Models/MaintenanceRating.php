<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceRating extends Model
{
    protected $fillable = [
        'maintenance_request_id',
        'rater_id',
        'technician_id',
        'score',
        'comment',
    ];

    public function request()
    {
        return $this->belongsTo(MaintenanceRequest::class, 'maintenance_request_id');
    }

    public function rater()
    {
        return $this->belongsTo(User::class, 'rater_id');
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}
