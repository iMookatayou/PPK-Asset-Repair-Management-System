<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Asset extends Model
{
    use HasFactory;

    /**
     * ถ้าตาราง attachments / maintenance_logs อ้างใบงานด้วยคีย์อื่น
     * เช่น 'maintenance_request_id' ให้เปลี่ยนตรงนี้จุดเดียว
     */
    private const REQ_FK = 'request_id';

    protected $fillable = [
        'asset_code',
        'name',
        'type',
        'category',        // legacy string
        'brand',
        'model',
        'serial_number',
        'location',
        'purchase_date',
        'warranty_expire',
        'status',
        'department_id',
        'category_id',
    ];

    protected $casts = [
        'purchase_date'   => 'date',
        'warranty_expire' => 'date',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    protected $attributes = [
        'status' => 'active',
    ];

    // =========================
    // Relationships
    // =========================

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function categoryRef()
    {
        return $this->belongsTo(AssetCategory::class, 'category_id');
    }

    /**
     * ใบงานซ่อมของทรัพย์สินนี้ (FK: maintenance_requests.asset_id)
     */
    public function maintenanceRequests()
    {
        return $this->hasMany(MaintenanceRequest::class, 'asset_id');
    }

    /**
     * ไฟล์แนบของทรัพย์สิน “ผ่าน” ใบงานซ่อม
     * attachments.{request_id|maintenance_request_id} -> maintenance_requests.id
     */
    public function requestAttachments()
    {
        return $this->hasManyThrough(
            Attachment::class,          // ปลายทาง
            MaintenanceRequest::class,  // ผ่าน
            'asset_id',                 // FK บน maintenance_requests -> assets.id
            self::REQ_FK,               // FK บน attachments -> maintenance_requests.id
            'id',                       // local key บน assets
            'id'                        // local key บน maintenance_requests
        );
    }

    /**
     * บันทึกเหตุการณ์/Log ของการซ่อม “ผ่าน” ใบงานซ่อม
     * maintenance_logs.{request_id|maintenance_request_id} -> maintenance_requests.id
     */
    public function requestLogs()
    {
        return $this->hasManyThrough(
            MaintenanceLog::class,
            MaintenanceRequest::class,
            'asset_id',                 // maintenance_requests.asset_id -> assets.id
            self::REQ_FK,               // maintenance_logs.FK -> maintenance_requests.id
            'id',
            'id'
        );
    }

    // =========================
    // Query Scopes (ช่วยให้ controller/view สะอาด)
    // =========================

    /**
     * ค้นหาแบบรวมหลายฟิลด์ (code/name/serial)
     */
    public function scopeSearch($q, ?string $term)
    {
        $term = trim((string) $term);
        if ($term === '') return $q;

        return $q->where(function ($w) use ($term) {
            $w->where('asset_code', 'like', "%{$term}%")
              ->orWhere('name', 'like', "%{$term}%")
              ->orWhere('serial_number', 'like', "%{$term}%");
        });
    }

    public function scopeDepartmentId($q, ?int $departmentId)
    {
        return filled($departmentId) ? $q->where('department_id', $departmentId) : $q;
    }

    public function scopeCategory($q, ?string $category)
    {
        return $category ? $q->where('category', $category) : $q;
    }

    public function scopeLocation($q, ?string $location)
    {
        return $location ? $q->where('location', $location) : $q;
    }

    public function scopeType($q, ?string $type)
    {
        return $type ? $q->where('type', $type) : $q;
    }

    public function scopeSortBySafe($q, ?string $by, string $dir = 'desc')
    {
        $map = [
            'id'              => 'id',
            'asset_code'      => 'asset_code',
            'name'            => 'name',
            'category'        => 'category',
            'status'          => 'status',
            'purchase_date'   => 'purchase_date',
            'warranty_expire' => 'warranty_expire',
            'created_at'      => 'created_at',
        ];
        $col = $map[$by ?? 'id'] ?? 'id';
        $dir = strtolower($dir) === 'asc' ? 'asc' : 'desc';
        return $q->orderBy($col, $dir);
    }
}
