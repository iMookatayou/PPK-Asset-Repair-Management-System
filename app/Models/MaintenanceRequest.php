<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceRequest extends Model
{
    use HasFactory;

    /**
     * Fillable fields
     */
    protected $fillable = [
        'asset_id', 'reporter_id', 'title', 'description',
        'priority', 'status', 'technician_id',
        'request_date', 'assigned_date', 'completed_date',
        'accepted_at', 'started_at', 'on_hold_at', 'resolved_at', 'closed_at',
        'remark', 'cost',
    ];

    /**
     * Attribute casts
     */
    protected $casts = [
        'request_date'   => 'datetime',
        'assigned_date'  => 'datetime',
        'completed_date' => 'datetime', // legacy/back-compat
        'accepted_at'    => 'datetime',
        'started_at'     => 'datetime',
        'on_hold_at'     => 'datetime',
        'resolved_at'    => 'datetime',
        'closed_at'      => 'datetime',
        'cost'           => 'decimal:2',
    ];

    /**
     * Base statuses
     */
    public const STATUS_PENDING     = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED   = 'completed'; // legacy → ใช้ resolved แทน
    public const STATUS_CANCELLED   = 'cancelled';

    /**
     * Extended statuses
     */
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_ON_HOLD  = 'on_hold';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED   = 'closed';

    /**
     * Priorities
     */
    public const PRIORITY_LOW    = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH   = 'high';
    public const PRIORITY_URGENT = 'urgent';

    /**
     * UI groups mapping (สำคัญสำหรับแท็บ)
     * - Pending     : งานรอคิว/เพิ่งรับ (รวม accepted และ null)
     * - In progress : กำลังทำ/พักไว้
     * - Completed   : ปิดงานแล้ว/เสร็จสิ้น
     */
    public const GROUP_PENDING    = ['pending', 'accepted', null];
    public const GROUP_INPROGRESS = ['in_progress', 'started', 'on_hold'];
    public const GROUP_COMPLETED  = ['resolved', 'completed', 'closed'];

    /**
     * Relationships
     */
    public function asset()       { return $this->belongsTo(Asset::class); }
    public function reporter()    { return $this->belongsTo(User::class, 'reporter_id'); }
    public function technician()  { return $this->belongsTo(User::class, 'technician_id'); }
    public function attachments() { return $this->hasMany(Attachment::class, 'request_id'); }
    public function logs()        { return $this->hasMany(MaintenanceLog::class, 'request_id'); }

    /**
     * Helpers
     */
    public function getNormalizedStatusAttribute(): string
    {
        if ($this->status === self::STATUS_COMPLETED && $this->resolved_at) {
            return self::STATUS_RESOLVED;
        }
        return (string) $this->status;
    }

    /**
     * Basic scopes
     */
    public function scopeStatus($q, ?string $s)
    {
        return $s ? $q->where('status', $s) : $q;
    }

    public function scopePriority($q, ?string $p)
    {
        return $p ? $q->where('priority', $p) : $q;
    }

    public function scopeRequestedBetween($q, ?string $from, ?string $to)
    {
        if ($from) $q->where('request_date', '>=', $from);
        if ($to)   $q->where('request_date', '<=', $to);
        return $q;
    }

    /**
     * Grouped scopes (ใช้กับแท็บ UI)
     */
    public function scopePendingGroup($q)
    {
        // ครอบคลุม pending, accepted และยังไม่ตั้งสถานะ (null)
        return $q->where(function ($w) {
            $w->whereIn('status', ['pending', 'accepted'])
              ->orWhereNull('status');
        });
    }

    public function scopeInProgressGroup($q)
    {
        return $q->whereIn('status', self::GROUP_INPROGRESS);
    }

    public function scopeCompletedGroup($q)
    {
        return $q->whereIn('status', self::GROUP_COMPLETED);
    }
}
