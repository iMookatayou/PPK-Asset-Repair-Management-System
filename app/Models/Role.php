<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    // ถ้า roles ไม่มี created_at / updated_at ให้ปิด timestamps
    public $timestamps = false;

    protected $fillable = [
        'code',
        'name_th',
        'name_en',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * users.role เก็บ code (เชื่อมตาม code)
     */
    public function users()
    {
        return $this->hasMany(User::class, 'role', 'code');
    }

    /**
     * scope เอาเฉพาะ role ที่ active
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * ใช้ใน validation → Rule::in(Role::availableCodes())
     */
    public static function availableCodes(): array
    {
        return static::active()
            ->orderBy('sort_order')
            ->pluck('code')
            ->all();
    }

    public static function options(): array
    {
        return static::active()
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(function (self $r) {
                $label = $r->name_th ?: ($r->name_en ?: $r->code);
                return [$r->code => $label];
            })
            ->toArray();
    }

    /**
     * accessor สำหรับแสดงชื่อ role รวม ๆ
     * $role->display_name
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name_th ?: ($this->name_en ?: $this->code);
    }
}
