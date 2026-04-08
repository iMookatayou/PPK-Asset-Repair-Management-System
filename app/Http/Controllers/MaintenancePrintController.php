<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaintenanceRequest as MR;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;

class MaintenancePrintController extends Controller
{
    public function printWorkOrder(Request $request, MR $req)
    {
        Gate::authorize('view', $req);

        $req->loadMissing([
            'asset',
            'reporter:id,name,email',
            'technician:id,name',
            'attachments' => fn($qq) => $qq->with('file'),
            'logs.user:id,name',
            'rating',
            'rating.rater:id,name',
        ]);

        $hospital = [
            'name_th'  => 'โรงพยาบาลพระปกเกล้า',
            'name_en'  => 'PHRAPOKKLAO HOSPITAL',
            'subtitle' => 'Maintenance Work Order',
            'logo'     => public_path('images/logoppk1.png'),
        ];

        $fileName = sprintf('maintenance-work-order-%s.pdf', $req->request_no ?? $req->id);

        $paperData = [
            'req'      => $req,
            'hospital' => $hospital,
            'print_at' => now(),
            'user'     => $request->user(),
        ];

        if ($request->has('html')) {
            return view('maintenance.pdf.work_order', $paperData);
        }

        $pdf = Pdf::loadView('maintenance.pdf.work_order', $paperData)
            ->setPaper('A4', 'portrait')
            ->setWarnings(false)
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
                'defaultFont'          => 'THSarabunNew',
                'chroot'               => public_path(),
            ]);

        return $pdf->stream($fileName);
    }
}
