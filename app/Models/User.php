<?php

namespace App\Models;

use App\Models\Department;
use App\Models\MaintenanceLog;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceRating;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // บทบาทตามที่ตกลง (เก็บเป็นโค้ดสั้นสำหรับระบบ)
    public const ROLE_ADMIN             = 'admin';     // ผู้ดูแลระบบ Admin
    public const ROLE_SUPERVISOR        = 'supervisor';// หัวหน้า Supervisor
    public const ROLE_IT_SUPPORT        = 'it_support';// ไอทีซัพพอร์ต IT Support
    public const ROLE_NETWORK           = 'network';   // เน็ตเวิร์ค Network
    public const ROLE_DEVELOPER         = 'developer'; // นักพัฒนา Developer
    public const ROLE_MEMBER            = 'member';    // บุคลากรทั่วไป (ในตาราง roles)
    public const ROLE_COMPUTER_OFFICER  = self::ROLE_MEMBER; // alias เดิม (กันโค้ดเก่า)
    public const ROLE_TECHNICIAN        = 'technician';      // ช่างซ่อมบำรุง Technician
    protected $fillable = [
        'name',
        'email',
        'password',
        'department',
        'role',
        'profile_photo_path',
        'profile_photo_thumb',
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
        // 'rating_average',
        // 'rating_count',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isSupervisor(): bool
    {
        return $this->role === self::ROLE_SUPERVISOR;
    }

    // สมาชิกธรรมดา (บุคลากร Member)
    public function isMember(): bool
    {
        return in_array($this->role, [self::ROLE_MEMBER, self::ROLE_COMPUTER_OFFICER], true);
    }

    public function isTechnician(): bool
    {
        return in_array($this->role, [
            self::ROLE_IT_SUPPORT,
            self::ROLE_NETWORK,
            self::ROLE_DEVELOPER,
            self::ROLE_TECHNICIAN,
        ], true);
    }

    // Legacy compatibility method (staff removed); always false
    public function isStaff(): bool
    {
        return false;
    }

    public function isWorker(): bool
    {
        return $this->isTechnician();
    }

    // helper generic
    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;
        return in_array($this->role, $roles, true);
    }

    public static function availableRoles(): array
    {
        return Role::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('code')
            ->all();
    }

    // บทบาทที่ถือว่าเป็นทีมปฏิบัติการ (worker)
    public static function workerRoles(): array
    {
        // ถ้าอยากให้มาจาก DB ทั้งหมด ก็ไปดึงจาก Role table ได้เหมือนกัน
        // ตอนนี้ยัง fix กลุ่มที่เป็นช่างไว้ก่อน
        return [
            self::ROLE_IT_SUPPORT,
            self::ROLE_NETWORK,
            self::ROLE_DEVELOPER,
            self::ROLE_TECHNICIAN,
        ];
    }

    // บทบาทที่แสดงใน Team Drawer (หัวหน้า + ทีมปฏิบัติการ)
    public static function teamRoles(): array
    {
        return array_merge([self::ROLE_SUPERVISOR], self::workerRoles());
    }

    /**
     * แผนที่บทบาท -> ป้ายกำกับ ไทย (ดึงจาก roles.name_th)
     */
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
        return $labels[$this->role] ?? (ucfirst((string) $this->role) ?: '-');
    }

    // relation อ้างอิง role record จริง
    public function roleRef()
    {
        // users.role (local key) → roles.code (owner key)
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

    // งานซ่อมที่ user คนนี้เป็นผู้แจ้ง
    public function reportedRequests()
    {
        return $this->hasMany(MaintenanceRequest::class, 'reporter_id');
    }

    // งานซ่อมที่ user คนนี้เป็นช่างผู้รับผิดชอบ
    public function assignedRequests()
    {
        return $this->hasMany(MaintenanceRequest::class, 'technician_id');
    }

    // log ต่าง ๆ
    public function logs()
    {
        return $this->hasMany(MaintenanceLog::class, 'user_id');
    }

    // แผนกอ้างอิง (ถ้ามี table departments แยก)
    public function departmentRef()
    {
        return $this->belongsTo(Department::class, 'department', 'code');
    }

    public function getDepartmentNameAttribute(): ?string
    {
        return $this->departmentRef?->name;
    }

    public function givenRatings()
    {
        return $this->hasMany(MaintenanceRating::class, 'rater_id');
    }

    public function technicianRatings()
    {
        return $this->hasMany(MaintenanceRating::class, 'technician_id');
    }

    public function getRatingAverageAttribute(): ?float
    {
        if (!$this->technicianRatings()->exists()) {
            return null;
        }

        return round((float) $this->technicianRatings()->avg('score'), 2);
    }

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

    private function uiAvatarUrl(int $size = 256): string
    {
        $name = urlencode($this->name ?: 'User');
        $palette = ['0D8ABC','0E2B51','16A34A','7C3AED','EA580C','DB2777','374151'];
        $idx = crc32(strtolower($this->name ?? 'user')) % count($palette);
        $bg  = $palette[$idx];
        return "https://ui-avatars.com/api/?name={$name}&background={$bg}&color=fff&size={$size}&bold=true";
    }
}
