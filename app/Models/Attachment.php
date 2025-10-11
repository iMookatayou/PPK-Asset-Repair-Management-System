<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'request_id',     // FK -> maintenance_requests.id
        'file_path',      // path เก็บไฟล์ใน storage
        'file_type',      // MIME type เช่น 'image/png'
        'uploaded_at',    // datetime
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    protected $appends = ['file_url', 'file_name'];

    // ===== Relationships =====
    public function request()
    {
        return $this->belongsTo(MaintenanceRequest::class, 'request_id');
    }

    // ===== Scopes =====
    public function scopeForRequest($query, int $requestId)
    {
        return $query->where('request_id', $requestId);
    }

    // ===== Accessors =====

    /** คืน URL เต็มสำหรับเปิดไฟล์บนเว็บ */
    public function getFileUrlAttribute(): ?string
    {
        $path = $this->file_path;
        if (!$path) return null;

        // ถ้าเป็น URL (S3, External)
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        // ถ้ามี symbolic link storage:link แล้ว (public/storage)
        if (Storage::disk('public')->exists($path)) {
            return Storage::url($path); // ออกมาเป็น /storage/xxx.jpg
        }

        // fallback: return เป็น absolute URL เฉยๆ
        return url('storage/'.$path);
    }

    /** คืนชื่อไฟล์ (ตัด path ออกให้เหลือชื่ออย่างเดียว) */
    public function getFileNameAttribute(): string
    {
        return basename($this->file_path ?? '');
    }
}
