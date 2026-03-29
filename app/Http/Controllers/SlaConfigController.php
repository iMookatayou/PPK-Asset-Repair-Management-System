<?php

namespace App\Http\Controllers;

use App\Models\SlaConfig;
use App\Models\MaintenanceRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class SlaConfigController extends Controller
{
    public function index(Request $request)
    {
        $data = $this->getSlaDashboardData($request);
        return view('settings.sla.index', $data);
    }

    public function report(Request $request)
    {
        $data = $this->getSlaDashboardData($request);
        
        $signatureData = $request->input('signature');
        if ($signatureData) {
            // signature data is usually: data:image/png;base64,iVBOR...
            $data['signature'] = $signatureData;
        }

        $hospital = [
            'name_th'  => 'โรงพยาบาลพระปกเกล้า',
            'name_en'  => 'PHRAPOKKLAO HOSPITAL',
            'subtitle' => 'SLA Performance Summary Report',
            'logo'     => public_path('images/logoppk1.png'),
        ];
        
        $data['hospital'] = $hospital;
        $data['reportDate'] = Carbon::now()->translatedFormat('d F Y');

        $pdf = Pdf::loadView('settings.sla.report', $data)
            ->setPaper('A4', 'portrait');

        return $pdf->stream('sla-report-' . Carbon::now()->format('Y-m-d') . '.pdf');
    }

    private function getSlaDashboardData(Request $request)
    {
        $configs = SlaConfig::orderBy('id')->get();
        
        // Calculate Dashboard Metrics
        // Default to Start of Current Year if no filter is provided
        $start = $request->query('from') 
            ? Carbon::parse($request->query('from'))->startOfDay() 
            : Carbon::now()->startOfYear();

        $end = $request->query('to') 
            ? Carbon::parse($request->query('to'))->endOfDay() 
            : Carbon::now()->endOfMonth();

        $requests = MaintenanceRequest::with(['department:id,name_th,name_en'])
            ->whereBetween('request_date', [$start, $end])
            ->get();

        $responseTimeSum = 0; $responseCount = 0;
        $acceptanceTimeSum = 0; $acceptanceCount = 0;
        $resolutionTimeSum = 0; $resolutionCount = 0;
        $complianceCount = 0; $resolvedTotal = 0;

        foreach ($requests as $req) {
            // Response Time (request_date -> acknowledged_at)
            if ($req->acknowledged_at && $req->request_date) {
                $responseTimeSum += $req->request_date->diffInMinutes($req->acknowledged_at);
                $responseCount++;
            }
            
            // Acceptance Time (acknowledged_at -> accepted_at)
            if ($req->accepted_at && $req->acknowledged_at) {
                $acceptanceTimeSum += $req->acknowledged_at->diffInMinutes($req->accepted_at);
                $acceptanceCount++;
            }

            // Resolution Time Net (accepted_at -> resolved_at)
            if ($req->resolved_at && $req->accepted_at) {
                $gross = $req->accepted_at->diffInMinutes($req->resolved_at);
                $net = max(0, $gross - ($req->paused_duration_minutes ?? 0));
                $resolutionTimeSum += $net;
                $resolutionCount++;
                $resolvedTotal++;
                
                // Compare with SLA due date if exists, else generic 48h
                if ($req->sla_due_date) {
                    if ($req->resolved_at <= $req->sla_due_date) {
                        $complianceCount++;
                    }
                } else {
                    if ($net <= (48 * 60)) {
                        $complianceCount++;
                    }
                }
            }
        }

        $nowDatetime = Carbon::now();
        $warningThreshold = $nowDatetime->copy()->addHours(4); // 4 hours before breach

        $activeTickets = MaintenanceRequest::with(['reporter:id,name', 'technician:id,name', 'department:id,name_th,name_en'])
            ->whereNotIn('status', [MaintenanceRequest::STATUS_RESOLVED, MaintenanceRequest::STATUS_CLOSED, MaintenanceRequest::STATUS_CANCELLED])
            ->whereNotNull('sla_due_date')
            ->get();

        $breachedTickets = [];
        $atRiskTickets = [];

        foreach ($activeTickets as $ticket) {
            if ($nowDatetime->greaterThan($ticket->sla_due_date)) {
                $breachedTickets[] = $ticket;
            } elseif ($warningThreshold->greaterThan($ticket->sla_due_date)) {
                $atRiskTickets[] = $ticket;
            }
        }

        // Chart 2: Status Distribution (Current Month)
        $statusDist = [
            'ทำตาม SLA' => $complianceCount, // Handled correctly resolved ones
            'เกินเวลา'  => 0,
            'มีความเสี่ยง'   => 0,
            'ตามกำหนด'  => 0,
        ];
        
        $monthBreached = [];

        foreach ($requests as $req) {
            if ($req->resolved_at && $req->accepted_at) {
                // Resolved tickets logic
                $gross = $req->accepted_at->diffInMinutes($req->resolved_at);
                $net = max(0, $gross - ($req->paused_duration_minutes ?? 0));
                
                $isCompliant = $req->sla_due_date 
                    ? ($req->resolved_at <= $req->sla_due_date) 
                    : ($net <= (48 * 60));
                    
                if (!$isCompliant) {
                    $statusDist['เกินเวลา']++;
                    $monthBreached[] = $req;
                }
            } else {
                // Unresolved/Active tickets logic
                if (!in_array($req->status, [MaintenanceRequest::STATUS_CLOSED, MaintenanceRequest::STATUS_CANCELLED])) {
                    if ($req->sla_due_date) {
                        if ($nowDatetime->greaterThan($req->sla_due_date)) {
                            $statusDist['เกินเวลา']++;
                            $monthBreached[] = $req;
                        } elseif ($warningThreshold->greaterThan($req->sla_due_date)) {
                            $statusDist['มีความเสี่ยง']++;
                        } else {
                            $statusDist['ตามกำหนด']++;
                        }
                    } else {
                        $statusDist['ตามกำหนด']++;
                    }
                }
            }
        }
        
        // Chart 3: Breaches by Department
        $breachesByDept = [];
        foreach ($monthBreached as $req) {
            $deptName = $req->department ? $req->department->name : 'ไม่ได้ระบุ';
            if (!isset($breachesByDept[$deptName])) {
                $breachesByDept[$deptName] = 0;
            }
            $breachesByDept[$deptName]++;
        }
        arsort($breachesByDept);

        $dashboard = [
            'avg_response_hours' => $responseCount > 0 ? round(($responseTimeSum / $responseCount) / 60, 1) : 0,
            'avg_acceptance_hours' => $acceptanceCount > 0 ? round(($acceptanceTimeSum / $acceptanceCount) / 60, 1) : 0,
            'avg_resolution_hours' => $resolutionCount > 0 ? round(($resolutionTimeSum / $resolutionCount) / 60, 1) : 0,
            'compliance_rate' => $resolvedTotal > 0 ? round(($complianceCount / $resolvedTotal) * 100, 1) : 0,
            'breached_count' => count($breachedTickets),
            'at_risk_count' => count($atRiskTickets),
        ];

        // Chart data: Last 6 months trend
        $chartLabels = [];
        $chartResolution = [];
        $chartCompliance = [];

        for ($i = 11; $i >= 0; $i--) {
            $mStartObj = Carbon::now()->subMonths($i)->startOfMonth();
            $mEndObj = Carbon::now()->subMonths($i)->endOfMonth();
            $label = $mStartObj->translatedFormat('M Y');
            
            $mRequests = MaintenanceRequest::whereBetween('request_date', [$mStartObj, $mEndObj])->get();
            
            $mResSum = 0; $mResCount = 0;
            $mCompCount = 0; $mTotalRes = 0;

            foreach ($mRequests as $req) {
                if ($req->resolved_at && $req->accepted_at) {
                    $mTotalRes++;
                    $gross = $req->accepted_at->diffInMinutes($req->resolved_at);
                    $net = max(0, $gross - ($req->paused_duration_minutes ?? 0));
                    $mResSum += $net;
                    $mResCount++;

                    if ($req->sla_due_date) {
                        if ($req->resolved_at <= $req->sla_due_date) $mCompCount++;
                    } else {
                        if ($net <= (48 * 60)) $mCompCount++;
                    }
                }
            }

            $chartLabels[] = $label;
            $chartResolution[] = $mResCount > 0 ? round(($mResSum / $mResCount) / 60, 1) : 0;
            $chartCompliance[] = $mTotalRes > 0 ? round(($mCompCount / $mTotalRes) * 100, 1) : 0;
        }

        $chartData = [
            'trend' => [
                'labels' => $chartLabels,
                'resolution' => $chartResolution,
                'compliance' => $chartCompliance,
            ],
            'distribution' => [
                'labels' => array_keys($statusDist),
                'data' => array_values($statusDist),
            ],
            'department' => [
                'labels' => array_keys($breachesByDept),
                'data' => array_values($breachesByDept),
            ]
        ];

        return compact('configs', 'dashboard', 'breachedTickets', 'atRiskTickets', 'chartData');
    }

    public function update(Request $request, SlaConfig $slaConfig)
    {
        $request->validate([
            'response_time_minutes' => 'required|integer|min:0',
            'resolution_time_minutes' => 'required|integer|min:0',
        ]);

        $slaConfig->update([
            'response_time_minutes' => $request->response_time_minutes,
            'resolution_time_minutes' => $request->resolution_time_minutes,
            'is_active' => $request->input('is_active', true),
        ]);

        return back()->with('toast', \App\Support\Toast::success('อัปเดตค่า SLA เรียบร้อยแล้ว'));
    }
}
