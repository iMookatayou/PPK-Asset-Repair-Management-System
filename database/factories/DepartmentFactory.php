<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    // กำหนดค่าเริ่มต้นให้กับ Model Department
    public function definition(): array
    {
        $nameTh = $this->faker->unique()->company();
        $nameEn = Str::title($nameTh);
        return [
            // สร้างรหัสแผนกแบบสุ่ม (ตัวย่อ 5 ตัว + ตัวเลข 2 หลัก)
            'code'    => strtoupper(Str::substr(Str::slug($nameTh,''),0,5)) . rand(10,99),
            'name_th' => $nameTh,
            'name_en' => $nameEn,
        ];
    }
}
