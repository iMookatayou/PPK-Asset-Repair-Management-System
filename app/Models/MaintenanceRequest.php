<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes; // เปิดถ้ามีคอลัมน์ deleted_at

class MaintenanceRequest extends Model
{
    use HasFactory;
    // use SoftDeletes;

    /**
     * Fillable fields
     */
    protected $fillable = [
        // อ้างอิง/พื้นฐาน
        'request_no',
        'asset_id',
        'department_id',
        'reporter_id',
        'title',
        'description',
        'priority',
        'status',
        'technician_id',

        // ผู้แจ้ง (กรณีคนนอก)
        'reporter_name',
        'reporter_phone',
        'reporter_email',

        // ที่ตั้ง/หน่วยงาน
        'location_text',

        // ไทม์ไลน์
        'request_date',
        'assigned_date',
        'completed_date',
        'accepted_at',
        'started_at',
        'on_hold_at',
        'resolved_at',
        'closed_at',

        // หมายเหตุ/ผลการซ่อม/ต้นทาง/ข้อมูลเสริม
        'remark',
        'resolution_note',
        'cost',
        'source',
        'extra',
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
        'extra'          => 'array',
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
     */
    public const GROUP_PENDING    = ['pending', 'accepted', null];
    public const GROUP_INPROGRESS = ['in_progress', 'started', 'on_hold'];
    public const GROUP_COMPLETED  = ['resolved', 'completed', 'closed'];

    /**
     * Relationships
     */
    public function asset()        { return $this->belongsTo(Asset::class); }
    public function department()   { return $this->belongsTo(Department::class); }
    public function reporter()     { return $this->belongsTo(User::class, 'reporter_id'); }
    public function technician()   { return $this->belongsTo(User::class, 'technician_id'); }
    public function logs()         { return $this->hasMany(MaintenanceLog::class, 'request_id'); }

    public function attachments()
    {
        return $this->morphMany(\App\Models\Attachment::class, 'attachable')->orderBy('order_column');
    }
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
     * Auto-generate request_no ถ้ายังไม่มี
     */
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->request_no)) {
                // รูปแบบ MR-YYYY-XXXXXX (จาก id จะยังไม่มีตอน creating)
                // ใช้เวลา + random ชั่วคราว แล้วค่อย normalize ใน updated event ถ้าต้อง
                $year = now()->format('Y');
                $rand = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
                $model->request_no = "MR-{$year}-{$rand}";
            }
            if (empty($model->source)) {
                $model->source = 'web';
            }
        });
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
