<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceRating;

class DemoRatingSeeder extends Seeder
{
    public function run(): void
    {
        // เลือกเฉพาะงานที่ปิดแล้ว และมีทั้ง reporter & technician
        $closedJobs = MaintenanceRequest::query()
            ->where('status', 'closed')
            ->whereNotNull('reporter_id')
            ->whereNotNull('technician_id')
            ->inRandomOrder()
            ->limit(120)
            ->get();

        foreach ($closedJobs as $job) {
            // กันกรณีมี rating อยู่แล้ว (unique: maintenance_request_id + rater_id)
            MaintenanceRating::updateOrCreate(
                [
                    'maintenance_request_id' => $job->id,
                    'rater_id'               => $job->reporter_id,
                ],
                [
                    'technician_id' => $job->technician_id,
                    'score'         => rand(3, 5),
                    'comment'       => null,
                ]
            );
        }
    }
}
