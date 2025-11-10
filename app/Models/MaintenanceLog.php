<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'request_id','user_id','action','note','created_at','from_status','to_status'
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public const ACTION_CREATE   = 'create_request';
    public const ACTION_UPDATE   = 'update_request';
    public const ACTION_ASSIGN   = 'assign_technician';
    public const ACTION_START    = 'start_request';
    public const ACTION_COMPLETE = 'complete_request';
    public const ACTION_CANCEL   = 'cancel_request';
    public const ACTION_TRANSITION = 'transition';

    /**
     * แปลง status code -> Thai label สำหรับแสดงผลเร็ว ๆ ใน view (ไม่ persisted)
     */
    public static function statusLabel(string $status): string
    {
        return [
            'pending'     => 'รอคิว',
            'accepted'    => 'รับงานแล้ว',
            'in_progress' => 'ระหว่างดำเนินการ',
            'on_hold'     => 'พักไว้',
            'resolved'    => 'แก้ไขแล้ว',
            'closed'      => 'ปิดงาน',
            'cancelled'   => 'ยกเลิก',
        ][$status] ?? $status;
    }

    public function getFromStatusLabelAttribute(): ?string
    {
        return $this->from_status ? self::statusLabel($this->from_status) : null;
    }

    public function getToStatusLabelAttribute(): ?string
    {
        return $this->to_status ? self::statusLabel($this->to_status) : null;
    }

    protected static function booted()
    {
        static::creating(function (self $log) {
            if (empty($log->created_at)) {
                $log->created_at = now();
            }
        });
    }

    public function request()
    {
        return $this->belongsTo(MaintenanceRequest::class, 'request_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForRequest($query, int $requestId)
    {
        return $query->where('request_id', $requestId);
    }

    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeRecent($query)
    {
        return $query->orderByDesc('created_at');
    }
}
