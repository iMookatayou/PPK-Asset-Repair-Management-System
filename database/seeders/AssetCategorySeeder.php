<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AssetCategorySeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $rows = [
            ['name' => 'เครื่องมือแพทย์',       'slug' => 'medical-equipment', 'color' => '#2563eb', 'description' => 'อุปกรณ์ตรวจ/รักษา/ช่วยพยุงชีพ'],
            ['name' => 'คอมพิวเตอร์และอุปกรณ์', 'slug' => 'computers',         'color' => '#16a34a', 'description' => 'PC, Notebook, Printer, Monitor'],
            ['name' => 'อุปกรณ์เครือข่าย',      'slug' => 'network',           'color' => '#059669', 'description' => 'Switch, Router, Access Point'],
            ['name' => 'เครื่องใช้ไฟฟ้า',       'slug' => 'electrical',        'color' => '#f97316', 'description' => 'ตู้เย็น ไมโครเวฟ กาต้มน้ำ'],
            ['name' => 'เครื่องปรับอากาศ',      'slug' => 'air-conditioning',  'color' => '#0ea5e9', 'description' => 'แอร์ผนัง/แขวน/ตั้งพื้น'],
            ['name' => 'เฟอร์นิเจอร์สำนักงาน',  'slug' => 'furniture',         'color' => '#9333ea', 'description' => 'โต๊ะ เก้าอี้ ตู้เอกสาร'],
            ['name' => 'ระบบกล้องวงจรปิด',      'slug' => 'cctv',              'color' => '#475569', 'description' => 'CCTV และเครื่องบันทึก NVR'],
            ['name' => 'อุปกรณ์แล็บ',           'slug' => 'lab-equipment',     'color' => '#e11d48', 'description' => 'Centrifuge, Analyzer, Microscope'],
            ['name' => 'อุปกรณ์ภาพวินิจฉัย',    'slug' => 'imaging',           'color' => '#111827', 'description' => 'Ultrasound, X-ray (ถ้ามี)'],
        ];

        foreach ($rows as &$r) {
            $r['is_active']  = true;
            $r['created_at'] = $now;
            $r['updated_at'] = $now;
        }

        // ป้องกันซ้ำด้วย upsert ตาม slug
        DB::table('asset_categories')->upsert(
            $rows,
            ['slug'],
            ['name','color','description','is_active','updated_at']
        );
    }
}
