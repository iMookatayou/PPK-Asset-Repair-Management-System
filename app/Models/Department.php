<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name_th',
        'name_en',
    ];

    // ===== Relationships =====
    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    // รวมใบซ่อมทั้งหมดของแผนก (ผ่านทรัพย์สิน)
    public function maintenanceRequests()
    {
        return $this->hasManyThrough(
            MaintenanceRequest::class, // ปลายทาง
            Asset::class,              // กลาง
            'department_id',           // FK บน assets → departments.id
            'asset_id',                // FK บน maintenance_requests → assets.id
            'id',                      // PK departments
            'id'                       // PK assets
        );
    }

    // ===== Accessors (สำหรับความเข้ากันได้กับโค้ดเดิม) =====
    // ใช้ $dept->name จะได้ชื่อไทย ถ้าไม่มีไทยจะ fallback เป็นอังกฤษ
    public function getNameAttribute(): string
    {
        return $this->name_th ?: ($this->name_en ?: '');
    }

    // สำหรับ UI: แสดง "Thai (English)" ถ้ามีทั้งสองภาษา
    public function getDisplayNameAttribute(): string
    {
        $th = trim((string) $this->name_th);
        $en = trim((string) $this->name_en);
        if ($th && $en) {
            return "{$th} ({$en})";
        }
        return $th ?: $en;
    }

    // ===== Scopes =====
    public function scopeCode($q, ?string $code)
    {
        return $code ? $q->where('code', $code) : $q;
    }

    // ค้นหาชื่อแบบ like ทั้งไทยและอังกฤษ
    public function scopeNameLike($q, ?string $name)
    {
        if (!$name) return $q;

        return $q->where(function ($qq) use ($name) {
            $qq->where('name_th', 'like', "%{$name}%")
               ->orWhere('name_en', 'like', "%{$name}%");
        });
    }

    // ค้นหาแบบรวม code + ชื่อ (เหมาะกับหน้า index filter เดียว)
    public function scopeSearch($q, ?string $term)
    {
        if (!$term) return $q;

        return $q->where(function ($qq) use ($term) {
            $qq->where('code', 'like', "%{$term}%")
               ->orWhere('name_th', 'like', "%{$term}%")
               ->orWhere('name_en', 'like', "%{$term}%");
        });
    }
}
