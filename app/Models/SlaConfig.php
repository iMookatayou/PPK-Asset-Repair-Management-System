<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlaConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'priority_level',
        'name',
        'response_time_minutes',
        'resolution_time_minutes',
        'is_active',
        'description',
    ];

    public const PRIORITY_DEFAULT = 'default';
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    protected $casts = [
        'is_active' => 'boolean',
        'response_time_minutes' => 'integer',
        'resolution_time_minutes' => 'integer',
    ];
}
