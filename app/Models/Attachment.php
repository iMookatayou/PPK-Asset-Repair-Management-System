<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;

/**
 * Class Attachment
 *
 * Polymorphic attachments (ใช้ได้หลายโมดูล)
 *
 * @property int $id
 * @property string $original_name
 * @property string|null $extension
 * @property string $path
 * @property string $disk
 * @property string|null $mime
 * @property int|null $size
 * @property string|null $checksum_sha256
 * @property bool $is_private
 * @property array|null $meta
 * @property string|null $caption
 * @property string|null $alt_text
 * @property int $order_column
 * @property string|null $expires_at
 *
 * @property-read string|null $url
 * @property-read string $filename
 */
class Attachment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        // polymorphic
        'attachable_type',
        'attachable_id',

        // file identity
        'original_name',
        'extension',
        'path',
        'disk',
        'mime',
        'size',
        'checksum_sha256',

        // presentation
        'caption',
        'alt_text',
        'order_column',

        // privacy & lineage
        'is_private',
        'variant_of_id',

        // audit/meta
        'uploaded_by',
        'source',
        'meta',

        // retention
        'expires_at',
    ];

    protected $casts = [
        'size'        => 'integer',
        'is_private'  => 'boolean',
        'meta'        => 'array',
        'expires_at'  => 'datetime',
        'deleted_at'  => 'datetime',
    ];

    protected $appends = ['url','filename'];

    /* ===================== Relationships ===================== */

    public function attachable()
    {
        return $this->morphTo();
    }

    public function parentVariant()
    {
        return $this->belongsTo(self::class, 'variant_of_id');
    }

    public function variants()
    {
        return $this->hasMany(self::class, 'variant_of_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /* ======================== Scopes ========================= */

    public function scopeForAttachable($q, Model $model)
    {
        return $q->where('attachable_type', get_class($model))
                 ->where('attachable_id', $model->getKey());
    }

    public function scopeImages($q)
    {
        return $q->where('mime', 'like', 'image/%');
    }

    public function scopeOfMime($q, string $pattern)
    {
        return $q->where('mime', 'like', $pattern);
    }

    /* ======================= Accessors ======================= */

    /** ชื่อไฟล์สั้น (ไม่รวม path) */
    public function getFilenameAttribute(): string
    {
        return $this->original_name ?: basename((string)$this->path);
    }

    /**
     * URL สำหรับเปิดดูไฟล์
     * - public: ใช้ FilesystemAdapter::url() ถ้ามี, fallback เป็น Storage::url()
     * - private: คืนผ่าน route ตรวจสิทธิ์ของระบบ (attachments.show)
     */
    public function getUrlAttribute(): ?string
    {
        if (!$this->path || !$this->disk) {
            return null;
        }

        if (!$this->is_private) {
            /** @var FilesystemAdapter $fs */
            $fs = Storage::disk($this->disk);

            // บาง disk อาจไม่มีเมธอด url()
            if (method_exists($fs, 'url')) {
                return $fs->url($this->path);
            }

            // fallback ใช้ default disk จาก config/filesystems
            return Storage::url($this->path);
        }

        // Private: ส่งผ่าน controller เพื่อ authorize
        return route('attachments.show', ['attachment' => $this->getKey()]);
    }

    /** true ถ้าเป็นไฟล์รูปภาพ */
    public function getIsImageAttribute(): bool
    {
        return is_string($this->mime) && str_starts_with($this->mime, 'image/');
    }

    /* ======================== Helpers ======================== */

    /** path เต็มใน storage (internal) */
    public function absolutePath(): ?string
    {
        if (!$this->path || !$this->disk) return null;

        try {
            return Storage::disk($this->disk)->path($this->path);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** ลบ record + ไฟล์จริงใน storage (Single Source of Truth) */
    public function deleteWithFile(): bool
    {
        try {
            if ($this->path && $this->disk && Storage::disk($this->disk)->exists($this->path)) {
                Storage::disk($this->disk)->delete($this->path);
            }
        } catch (\Throwable $e) {
            // swallow error: อย่าให้การลบเรคอร์ด fail เพราะลบไฟล์จริงไม่สำเร็จ
        }

        return (bool) $this->delete();
    }

    /* ===================== Model Events ====================== */

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            $m->disk         = $m->disk ?: 'public';
            $m->source       = $m->source ?: 'web';
            $m->order_column = $m->order_column ?? 0;
            $m->is_private   = $m->is_private ?? false;

            if (empty($m->extension)) {
                $name = $m->original_name ?: $m->path;
                $m->extension = pathinfo((string)$name, PATHINFO_EXTENSION) ?: null;
            }
        });
    }
}
