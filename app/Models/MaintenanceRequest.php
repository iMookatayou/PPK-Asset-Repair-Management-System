<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class MaintenanceRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'maintenance_requests';

    protected $fillable = [
        // ===== อ้างอิง / พื้นฐาน =====
        'request_no',
        'asset_id',
        'department_id',
        'type_id',
        'reporter_id',
        'title',
        'description',
        'priority',
        'status',

        'status_updated_at',
        'status_updated_by',

        'technician_id',

        // ===== ผู้แจ้ง =====
        'reporter_name',
        'reporter_phone',
        'reporter_email',
        'reporter_position',

        'legacy_payload',

        // ===== สถานที่ =====
        'location_text',

        // ===== timeline =====
        'request_date',
        'assigned_date',
        'completed_date', // legacy
        'acknowledged_at',
        'accepted_at',
        'started_at',
        'on_hold_at',
        'resolved_at',
        'closed_at',
        
        'sla_due_date',
        'paused_duration_minutes',

        // ===== อื่น ๆ =====
        'remark',
        'resolution_note',
        'cost',
        'source',
        'extra',
    ];

    protected $casts = [
        'request_date'        => 'datetime',
        'assigned_date'       => 'datetime',
        'completed_date'      => 'datetime',
        'acknowledged_at'     => 'datetime',
        'accepted_at'         => 'datetime',
        'started_at'          => 'datetime',
        'on_hold_at'          => 'datetime',
        'resolved_at'         => 'datetime',
        'closed_at'           => 'datetime',
        'sla_due_date'        => 'datetime',
        'paused_duration_minutes' => 'integer',

        'status_updated_at'   => 'datetime',

        'cost'                => 'decimal:2',

        // หมายเหตุ: ใน seeder อาจเป็น json_encode string ได้ แต่ cast array จะ decode ให้
        'legacy_payload'      => 'array',
        'extra'               => 'array',

        'deleted_at'          => 'datetime',
    ];

    /* ================= STATUS ================= */

    public const STATUS_PENDING      = 'pending';
    public const STATUS_ACKNOWLEDGED = 'acknowledged';
    public const STATUS_ACCEPTED     = 'accepted';
    public const STATUS_IN_PROGRESS  = 'in_progress';
    public const STATUS_ON_HOLD      = 'on_hold';
    public const STATUS_RESOLVED     = 'resolved';
    public const STATUS_CLOSED       = 'closed';
    public const STATUS_CANCELLED    = 'cancelled';
    public const STATUS_REJECTED     = 'rejected';

    // legacy
    public const STATUS_COMPLETED    = 'completed';

    public const PRIORITY_LOW    = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH   = 'high';
    public const PRIORITY_URGENT = 'urgent';

    /**
     * Transition map: สถานะปัจจุบัน => สถานะที่อนุญาตให้เปลี่ยนไปได้
     */
    public const ALLOWED_TRANSITIONS = [
        self::STATUS_PENDING      => [self::STATUS_ACKNOWLEDGED, self::STATUS_CANCELLED, self::STATUS_REJECTED],
        self::STATUS_ACKNOWLEDGED => [self::STATUS_ACCEPTED, self::STATUS_CANCELLED, self::STATUS_REJECTED],
        self::STATUS_ACCEPTED     => [self::STATUS_IN_PROGRESS, self::STATUS_ON_HOLD, self::STATUS_CANCELLED],
        self::STATUS_IN_PROGRESS  => [self::STATUS_RESOLVED, self::STATUS_CANCELLED, self::STATUS_ON_HOLD],
        self::STATUS_ON_HOLD      => [self::STATUS_IN_PROGRESS, self::STATUS_CANCELLED],
        self::STATUS_RESOLVED     => [self::STATUS_CLOSED],
        self::STATUS_CLOSED       => [],
        self::STATUS_CANCELLED    => [],
        self::STATUS_REJECTED     => [],
    ];

    public static function statusLabels(): array
    {
        return [
            self::STATUS_PENDING      => 'รอรับทราบ',
            self::STATUS_ACKNOWLEDGED => 'รับทราบแล้ว',
            self::STATUS_ACCEPTED     => 'รับเรื่องแล้ว',
            self::STATUS_IN_PROGRESS  => 'กำลังดำเนินการ',
            self::STATUS_ON_HOLD      => 'พักชั่วคราว',
            self::STATUS_RESOLVED     => 'ซ่อมบำรุงเสร็จสิ้น',
            self::STATUS_CLOSED       => 'อนุมัติ',
            self::STATUS_CANCELLED    => 'ยกเลิกซ่อม',
            self::STATUS_REJECTED     => 'ไม่รับเรื่อง',
            self::STATUS_COMPLETED    => 'เสร็จสิ้น (legacy)',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? (string) $this->status;
    }

    /* ================= RELATION ================= */

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function statusUpdatedBy()
    {
        return $this->belongsTo(User::class, 'status_updated_by');
    }

    // ตารางนี้บังคับ 1 ใบงาน -> 1 รายงานปฏิบัติงาน
    public function operationLog()
    {
        return $this->hasOne(MaintenanceOperationLog::class, 'maintenance_request_id');
    }

    public function assignments()
    {
        return $this->hasMany(MaintenanceAssignment::class, 'maintenance_request_id');
    }

    public function workers()
    {
        return $this->belongsToMany(User::class, 'maintenance_assignments')
            ->withPivot(['role', 'is_lead', 'assigned_at', 'status'])
            ->withTimestamps();
    }

    public function logs()
    {
        return $this->hasMany(MaintenanceLog::class, 'request_id');
    }

    public function attachments()
    {
        return $this->morphMany(\App\Models\Attachment::class, 'attachable')->ordered();
    }

    public function imageAttachments()
    {
        return $this->attachments()
            ->whereHas('file', fn ($q) => $q->where('mime', 'like', 'image/%'));
    }

    public function latestAttachment()
    {
        return $this->morphOne(\App\Models\Attachment::class, 'attachable')->latestOfMany('id');
    }

    public function ratings()
    {
        return $this->hasMany(MaintenanceRating::class, 'maintenance_request_id');
    }

    public function rating()
    {
        $user = Auth::user();

        return $this->hasOne(MaintenanceRating::class, 'maintenance_request_id')
            ->when($user, fn($q) => $q->where('rater_id', $user->getKey()));
    }

    public function ratingBy(int $userId)
    {
        return $this->hasOne(MaintenanceRating::class, 'maintenance_request_id')
            ->where('rater_id', $userId);
    }

    /* ================= ACCESSOR ================= */

    public function getNormalizedStatusAttribute(): string
    {
        if ($this->status === self::STATUS_COMPLETED && $this->resolved_at) {
            return self::STATUS_RESOLVED;
        }
        return (string) $this->status;
    }

    /* ================= REQUEST NO ================= */

    public static function generateLegacyRequestNo(): string
    {
        $thaiYear = now()->year + 543;
        $yy = substr((string) $thaiYear, -2);

        $type = '10'; // legacy fixed type

        // ใช้ MAX(request_no) แทน count() เพื่อป้องกัน race condition
        $lastNo = static::query()
            ->whereYear('created_at', now()->year)
            ->lockForUpdate()
            ->max('request_no');

        if ($lastNo && strlen($lastNo) >= 7) {
            $lastRun = (int) substr($lastNo, -5);
            $nextRun = $lastRun + 1;
        } else {
            $nextRun = 1;
        }

        $run = str_pad((string) $nextRun, 5, '0', STR_PAD_LEFT);

        return $yy . $type . $run;
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->request_no)) {
                $model->request_no = static::generateLegacyRequestNo();
            }
            if (empty($model->source)) {
                $model->source = 'web';
            }

            if (empty($model->status_updated_at)) {
                $model->status_updated_at = now();
            }
        });

        static::created(function (self $model) {
            $model->syncAssetStatus();
        });

        static::updated(function (self $model) {
            if ($model->isDirty(['status', 'asset_id'])) {
                $model->syncAssetStatus();
            }
        });

        static::deleted(function (self $model) {
            $model->syncAssetStatus();
        });
    }

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

    public function scopeSearch($q, ?string $term)
    {
        $term = trim((string) $term);
        if ($term === '') return $q;

        $isNumeric = ctype_digit($term);
        $len = strlen($term);

        if ($isNumeric && $len <= 5) {
            $hash = '#'.$term;

            return $q->where(function ($w) use ($term, $hash) {
                    $w->where('maintenance_requests.id', (int) $term)
                      ->orWhere('maintenance_requests.title', 'like', "%{$hash}%")
                      ->orWhere('maintenance_requests.title', 'like', "%{$term}%");
                })
                ->orderByRaw(
                    "CASE
                        WHEN maintenance_requests.id = ? THEN 0
                        WHEN maintenance_requests.title LIKE ? THEN 1
                        WHEN maintenance_requests.title LIKE ? THEN 2
                        ELSE 9
                    END ASC",
                    [(int)$term, "%{$hash}%", "%{$term}%"]
                )
                ->orderByDesc('maintenance_requests.id');
        }

        return $q->where(function ($w) use ($term) {
                $w->where('maintenance_requests.id', $term)
                  ->orWhere('maintenance_requests.title', 'like', "%{$term}%")
                  ->orWhere('maintenance_requests.description', 'like', "%{$term}%")
                  ->orWhere('maintenance_requests.request_no', 'like', "%{$term}%")
                  ->orWhere('maintenance_requests.reporter_name', 'like', "%{$term}%")
                  ->orWhere('maintenance_requests.reporter_phone', 'like', "%{$term}%")
                  ->orWhere('maintenance_requests.reporter_email', 'like', "%{$term}%")
                  ->orWhereHas('reporter', fn ($qr) =>
                        $qr->where('name', 'like', "%{$term}%")
                           ->orWhere('email', 'like', "%{$term}%")
                  )
                  ->orWhereHas('asset', fn ($qa) =>
                        $qa->where('name', 'like', "%{$term}%")
                           ->orWhere('asset_code', 'like', "%{$term}%")
                  );
            })
            ->orderByDesc('maintenance_requests.id');
    }

    /* ================= STATE HELPERS (สำคัญ) ================= */

    public function hasStatus(string $status): bool
    {
        return (string) $this->status === $status;
    }

    public function canStart(): bool
    {
        // รับเรื่องแล้ว -> เริ่มงานได้
        return $this->hasStatus(self::STATUS_ACCEPTED);
    }

    public function canHold(): bool
    {
        // รับเรื่องแล้ว/กำลังทำ -> พักได้
        return in_array((string) $this->status, [
            self::STATUS_ACCEPTED,
            self::STATUS_IN_PROGRESS,
        ], true);
    }

    public function canResume(): bool
    {
        // พักอยู่ -> กลับมาทำต่อได้
        return $this->hasStatus(self::STATUS_ON_HOLD);
    }

    public function canResolve(): bool
    {
        // ทำอยู่ -> ทำเสร็จได้ (ตัด พักอยู่ ออกตาม requirement ใหม่)
        return $this->hasStatus(self::STATUS_IN_PROGRESS);
    }

    public function canClose(): bool
    {
        // เสร็จแล้ว -> ผู้แจ้ง/แอดมินปิดงาน
        return $this->hasStatus(self::STATUS_RESOLVED);
    }

    public function transitionTo(string $toStatus, ?int $actorUserId = null, ?string $note = null): bool
    {
        $from = (string) $this->status;
        if ($from === $toStatus) {
            return true;
        }

        // ตรวจสอบลำดับสถานะที่อนุญาต
        $allowed = self::ALLOWED_TRANSITIONS[$from] ?? [];
        if (!in_array($toStatus, $allowed, true)) {
            throw new \InvalidArgumentException(
                "ไม่สามารถเปลี่ยนสถานะจาก [{$from}] ไปเป็น [{$toStatus}] ได้"
            );
        }

        $this->status = $toStatus;

        $now = now();
        switch ($toStatus) {
            case self::STATUS_ACKNOWLEDGED:
                $this->acknowledged_at ??= $now;
                break;

            case self::STATUS_ACCEPTED:
                if (!$this->accepted_at) {
                    $this->accepted_at = $now;
                    
                    $slaTarget = \App\Models\SlaConfig::where('priority_level', $this->priority ?? \App\Models\SlaConfig::PRIORITY_DEFAULT)
                        ->where('is_active', true)->first() 
                        ?? \App\Models\SlaConfig::where('priority_level', \App\Models\SlaConfig::PRIORITY_DEFAULT)->first();
                    
                    if ($slaTarget) {
                        $this->sla_due_date = $now->copy()->addMinutes($slaTarget->resolution_time_minutes);
                    }
                }
                break;

            case self::STATUS_IN_PROGRESS:
                $this->started_at ??= $now;
                // Extend SLA if resuming from ON_HOLD
                if ($from === self::STATUS_ON_HOLD && $this->on_hold_at) {
                    $onHoldAt = \Carbon\Carbon::parse($this->on_hold_at);
                    $pausedMins = (int) ceil($onHoldAt->diffInMinutes($now));
                    $this->paused_duration_minutes = (int) $this->paused_duration_minutes + $pausedMins;
                    
                    if ($this->sla_due_date) {
                        $this->sla_due_date = \Carbon\Carbon::parse($this->sla_due_date)->addMinutes($pausedMins);
                    }
                }
                break;

            case self::STATUS_ON_HOLD:
                $this->on_hold_at = $now;
                break;

            case self::STATUS_RESOLVED:
                $this->resolved_at ??= $now;
                break;

            case self::STATUS_CLOSED:
                $this->closed_at ??= $now;
                break;
        }

        // audit status
        $this->status_updated_at = $now;
        $this->status_updated_by = $actorUserId;

        $saved = $this->save();

        try {
            $text = trim(implode(' ', array_filter([
                "[{$from} -> {$toStatus}]",
                $note,
            ])));

            MaintenanceLog::create([
                'request_id' => $this->id,
                'user_id'    => $actorUserId,
                'action'     => MaintenanceLog::ACTION_TRANSITION,
                'note'       => $text !== '' ? $text : null,
            ]);
        } catch (\Throwable $e) {
            // ignore
        }

        return $saved;
    }

    public function type()
    {
        return $this->belongsTo(\App\Models\MaintenanceRequestType::class, 'type_id');
    }

    /**
     * ซิงค์สถานะของ Asset ตามสถานะของใบแจ้งซ่อม
     */
    public function syncAssetStatus(): void
    {
        if (empty($this->asset_id)) {
            return;
        }

        $asset = $this->asset;
        if (!$asset) {
            return;
        }

        // สถานะที่ถือว่า "กำลังซ่อม/รอซ่อม" (ทำให้ Asset เป็น in_repair)
        $activeStatuses = [
            self::STATUS_PENDING,
            self::STATUS_ACKNOWLEDGED,
            self::STATUS_ACCEPTED,
            self::STATUS_IN_PROGRESS,
            self::STATUS_ON_HOLD,
        ];

        // สถานะที่ถือว่า "จบงาน" (ทำให้ Asset กลับเป็น active ถ้าไม่มีใบงานอื่นค้าง)
        $resolvedStatuses = [
            self::STATUS_RESOLVED,
            self::STATUS_CLOSED,
            self::STATUS_CANCELLED,
            self::STATUS_REJECTED,
        ];

        if (in_array((string) $this->status, $activeStatuses, true)) {
            // ถ้าใบงานนี้กำลังดำเนินการอยู่ ให้ Asset เป็น in_repair
            if ($asset->status !== 'in_repair' && $asset->status !== 'disposed') {
                $asset->update(['status' => 'in_repair']);
                \Illuminate\Support\Facades\Log::info('[MaintenanceRequest::syncAssetStatus] Asset set to in_repair', [
                    'asset_id' => $asset->id,
                    'request_id' => $this->id,
                    'status' => $this->status,
                ]);
            }
        } elseif (in_array((string) $this->status, $resolvedStatuses, true)) {
            // ถ้าใบงานนี้จบแล้ว ให้เช็คว่ามีใบงานอื่นที่ยังค้างอยู่ไหมสำหรับ Asset นี้
            $hasOtherActive = static::query()
                ->where('asset_id', $this->asset_id)
                ->where('id', '!=', $this->id)
                ->whereIn('status', $activeStatuses)
                ->exists();

            if (!$hasOtherActive) {
                if ($asset->status === 'in_repair') {
                    $asset->update(['status' => 'active']);
                    \Illuminate\Support\Facades\Log::info('[MaintenanceRequest::syncAssetStatus] Asset restored to active', [
                        'asset_id' => $asset->id,
                        'request_id' => $this->id,
                        'status' => $this->status,
                    ]);
                }
            } else {
                \Illuminate\Support\Facades\Log::info('[MaintenanceRequest::syncAssetStatus] Asset kept in_repair (other MR still active)', [
                    'asset_id' => $asset->id,
                    'request_id' => $this->id,
                ]);
            }
        }
    }

}
