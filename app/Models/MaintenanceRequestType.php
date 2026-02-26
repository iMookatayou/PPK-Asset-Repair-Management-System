<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceRequestType extends Model
{
    protected $fillable = [
        'name',
        'description',
        'default_department_code',
        'default_role_code',
        'default_user_id',
        'is_active',
        'sort_order',
    ];

    public function requests()
    {
        return $this->hasMany(MaintenanceRequest::class, 'type_id');
    }

    public function defaultUser()
    {
        return $this->belongsTo(User::class, 'default_user_id');
    }
}
