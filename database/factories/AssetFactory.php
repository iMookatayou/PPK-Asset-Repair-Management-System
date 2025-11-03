<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Asset;
use App\Models\Department;
use App\Models\AssetCategory; 

class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        // หมวดหมู่ย่อย (string เดิม)
        $cats   = ['คอมพิวเตอร์', 'เครื่องพิมพ์', 'เครือข่าย', 'ยานพาหนะ', 'เครื่องมือ', 'เฟอร์นิเจอร์'];
        $brands = ['HP','Dell','Lenovo','Acer','Canon','Brother','Cisco','MikroTik','Yamaha','3M'];
        $locs   = ['สำนักงานใหญ่', 'อาคาร A', 'อาคาร B', 'คลังสินค้า', 'สาขาเชียงใหม่', 'สาขาภูเก็ต'];

        // map category → type คร่าว ๆ
        $typeMap = [
            'คอมพิวเตอร์'  => 'IT',
            'เครื่องพิมพ์' => 'IT',
            'เครือข่าย'    => 'IT',
            'ยานพาหนะ'     => 'Vehicle',
            'เครื่องมือ'    => 'Tool',
            'เฟอร์นิเจอร์'  => 'Office',
        ];

        $categoryName = fake()->randomElement($cats);
        $type         = $typeMap[$categoryName] ?? fake()->randomElement(['IT','Electrical','Office','Tool','Vehicle']);

        // เลือก category_id จากตาราง asset_categories ถ้ามี (ถ้าไม่มีให้เป็น null ไปก่อน)
        $categoryId = AssetCategory::where('name', $categoryName)->inRandomOrder()->value('id')
            ?? AssetCategory::inRandomOrder()->value('id');

        return [
            'asset_code'      => 'ASSET-'.fake()->unique()->numerify('#####'),
            'name'            => fake()->words(2, true),
            'type'            => $type,                 // ← เพิ่ม type
            'category'        => $categoryName,         // string เดิม (เพื่อ compatibility)
            'category_id'     => $categoryId,           // ← FK ใหม่ (อาจเป็น null หากยังไม่มี seed)
            'brand'           => fake()->randomElement($brands),
            'model'           => strtoupper(fake()->bothify('??-###')),
            'serial_number'   => strtoupper(fake()->unique()->bothify('SN########')),
            'location'        => fake()->randomElement($locs),
            'purchase_date'   => fake()->dateTimeBetween('-5 years', '-6 months')->format('Y-m-d'),
            'warranty_expire' => fake()->dateTimeBetween('-1 years', '+2 years')->format('Y-m-d'),
            'status'          => fake()->randomElement(['active','in_repair','disposed']),
            'department_id'   => Department::inRandomOrder()->value('id') ?? null,
        ];
    }
}
