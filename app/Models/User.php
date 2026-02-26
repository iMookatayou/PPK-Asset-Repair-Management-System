<?php

namespace App\Models;

use App\Models\Department;
use App\Models\MaintenanceLog;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceRating;
use App\Models\MaintenanceAssignment;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // การกำหนดค่า Role พื้นฐาน
    public const ROLE_ADMIN       = 'admin';
    public const ROLE_SUPERVISOR  = 'supervisor';
    public const ROLE_IT_SUPPORT  = 'it_support';
    public const ROLE_NETWORK     = 'network';
    public const ROLE_DEVELOPER   = 'programmer';
    public const ROLE_MEMBER      = 'member';
    public const ROLE_COMPUTER_OFFICER = self::ROLE_MEMBER;
    public const ROLE_TECHNICIAN  = 'technician';

    protected $fillable = [
        'name',
        'citizen_id',
        'email',
        'password',
        'department',
        'role',
        'profile_photo_path',
        'profile_photo_thumb',
        'notification_sound',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'avatar_url',
        'avatar_thumb_url',
        'department_name',
        'role_label',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // งานที่ User คนนี้ถูกมอบหมาย (ผ่านตาราง Assignment)
    public function maintenanceAssignments()
    {
        return $this->hasMany(MaintenanceAssignment::class, 'user_id');
    }

    // ดึงงานซ่อมทั้งหมดที่คนนี้ต้องทำผ่าน Pivot
    public function assignedMaintenanceRequests()
    {
        return $this->belongsToMany(MaintenanceRequest::class, 'maintenance_assignments')
                    ->withPivot(['role', 'is_lead', 'assigned_at', 'status'])
                    ->withTimestamps();
    }

    // Alias สำหรับเรียกใช้โค้ดเดิม
    public function assignedRequests()
    {
        return $this->assignedMaintenanceRequests();
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isSupervisor(): bool
    {
        return $this->role === self::ROLE_SUPERVISOR;
    }

    public function isMember(): bool
    {
        return in_array($this->role, [
            self::ROLE_MEMBER,
            self::ROLE_COMPUTER_OFFICER,
        ], true);
    }

    // ตรวจสอบว่าเป็นกลุ่มช่างหรือทีมทำงานหรือไม่
    public function isTechnician(): bool
    {
        return in_array($this->role, self::workerRoles(), true);
    }

    public function isWorker(): bool
    {
        return $this->isTechnician();
    }

    public function hasRole(string|array $roles): bool
    {
        return in_array($this->role, (array) $roles, true);
    }

    public static function availableRoles(): array
    {
        return Role::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('code')
            ->all();
    }

    // นิยามกลุ่มที่เป็นทีมปฏิบัติการ (Workers)
    public static function workerRoles(): array
    {
        return [
            self::ROLE_IT_SUPPORT,
            self::ROLE_NETWORK,
            self::ROLE_DEVELOPER,
            self::ROLE_TECHNICIAN,
        ];
    }

    // กลุ่มทีมบริหารและทีมปฏิบัติการรวมกัน
    public static function teamRoles(): array
    {
        return array_merge([self::ROLE_ADMIN, self::ROLE_SUPERVISOR], self::workerRoles());
    }

    public static function roleLabels(): array
    {
        return Role::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('name_th', 'code')
            ->all();
    }

    public function getRoleLabelAttribute(): string
    {
        $labels = self::roleLabels();
        return $labels[$this->role] ?? ucfirst((string) $this->role);
    }

    public function roleRef()
    {
        return $this->belongsTo(Role::class, 'role', 'code');
    }

    public function scopeRole($q, string $role)
    {
        return $q->where('role', $role);
    }

    public function scopeInRoles($q, array $roles)
    {
        return $q->whereIn('role', $roles);
    }

    public function scopeDepartment($q, ?string $code)
    {
        return $code ? $q->where('department', $code) : $q;
    }

    public function scopeHasAvatar($q)
    {
        return $q->whereNotNull('profile_photo_path')
                 ->where('profile_photo_path', '!=', '');
    }

    public function scopeTechnicians($q)
    {
        return $q->whereIn('role', self::workerRoles());
    }

    // รายการใบแจ้งซ่อมที่ User คนนี้เป็นผู้แจ้ง
    public function reportedRequests()
    {
        return $this->hasMany(MaintenanceRequest::class, 'reporter_id');
    }

    // ประวัติการบันทึก Log ของ User
    public function logs()
    {
        return $this->hasMany(MaintenanceLog::class, 'user_id');
    }

    // ข้อมูลแผนก
    public function departmentRef()
    {
        return $this->belongsTo(Department::class, 'department', 'code');
    }

    public function getDepartmentNameAttribute(): ?string
    {
        return $this->departmentRef?->name_th ?? $this->departmentRef?->name;
    }

    // การให้คะแนนโดย User คนนี้
    public function givenRatings()
    {
        return $this->hasMany(MaintenanceRating::class, 'rater_id');
    }

    // คะแนนเฉลี่ยที่ช่างได้รับ
    public function getRatingAverageAttribute(): ?float
    {
        if (!$this->technicianRatings()->exists()) {
            return null;
        }
        return round((float) $this->technicianRatings()->avg('score'), 2);
    }

    // จำนวนครั้งที่ถูกให้คะแนน
    public function getRatingCountAttribute(): int
    {
        return (int) $this->technicianRatings()->count();
    }

    public function getAvatarUrlAttribute(): string
    {
        $path = $this->profile_photo_path;

        if ($path && Storage::disk('public')->exists($path)) {
            return Storage::url($path);
        }
        return $this->uiAvatarUrl(256);
    }

    public function getAvatarThumbUrlAttribute(): string
    {
        $thumb = $this->profile_photo_thumb;
        $main  = $this->profile_photo_path;

        if ($thumb && Storage::disk('public')->exists($thumb)) {
            return Storage::url($thumb);
        }
        if ($main && Storage::disk('public')->exists($main)) {
            return Storage::url($main);
        }
        return $this->uiAvatarUrl(128);
    }

    // สร้างรูปโปรไฟล์จำลองกรณีไม่มีการอัปโหลดรูป
    private function uiAvatarUrl(int $size = 256): string
    {
        $name = urlencode($this->name ?: 'User');
        $palette = ['0D8ABC','0E2B51','16A34A','7C3AED','EA580C','DB2777','374151'];
        $idx = crc32(strtolower($this->name ?? 'user')) % count($palette);
        $bg  = $palette[$idx];

        return "https://ui-avatars.com/api/?name={$name}&background={$bg}&color=fff&size={$size}&bold=true";
    }

    // คะแนนที่ User คนนี้ได้รับในฐานะช่าง
    public function technicianRatings()
    {
        return $this->hasMany(\App\Models\MaintenanceRating::class, 'technician_id');
    }
}
