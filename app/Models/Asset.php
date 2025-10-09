<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $fillable = [
        'asset_code','name','category','brand','model',
        'serial_number','location','purchase_date','warranty_expire','status'
    ];

    public function requests() {
        return $this->hasMany(MaintenanceRequest::class);
    }
}

