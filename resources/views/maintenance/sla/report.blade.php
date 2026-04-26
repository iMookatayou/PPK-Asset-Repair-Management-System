<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>SLA Performance Report</title>
    <style>
        @font-face {
            font-family: 'sarabun';
            font-style: normal;
            font-weight: normal;
            src: url("{{ public_path('images/fonts/Sarabun-Regular.ttf') }}") format('truetype');
        }

        @font-face {
            font-family: 'sarabun';
            font-style: normal;
            font-weight: bold;
            src: url("{{ public_path('images/fonts/Sarabun-Bold.ttf') }}") format('truetype');
        }

        body {
            font-family: 'sarabun', sans-serif;
            font-size: 14pt;
            line-height: 1.2;
            color: #333;

        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .logo {
            width: 80px;
            margin-bottom: 5px;
        }

        .hospital-name {
            font-size: 20pt;
            font-weight: bold;
            display: block;
        }

        .report-title {
            font-size: 18pt;
            font-weight: bold;
            display: block;
            margin-top: 5px;
        }

        .report-date {
            text-align: right;
            font-size: 14pt;
            margin-bottom: 10px;
        }

        .section-title {
            font-size: 18pt;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
            border-left: 5px solid #1e40af;
            padding-left: 10px;
            color: #1e40af;
        }

        .metrics-grid {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }

        .metric-card {
            width: 25%;
            padding: 10px;
            border: 1px solid #e2e8f0;
            text-align: center;
        }

        .metric-value {
            font-size: 24pt;
            font-weight: bold;
            color: #1e40af;
            display: block;
        }

        .metric-label {
            font-size: 14pt;
            color: #64748b;
            display: block;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }

        td {
            border: 1px solid #cbd5e1;
            padding: 8px;
            text-align: left;
        }

        .status-badge {
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12pt;
            font-weight: bold;
            color: #fff;
        }

        .badge-breached {
            background-color: #ef4444;
        }

        .badge-at-risk {
            background-color: #f59e0b;
        }

        .badge-compliant {
            background-color: #10b981;
        }

        .signature-section {
            width: 45%;
            margin-left: 45%;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="header">
        @if (file_exists($hospital['logo']))
            <img src="{{ $hospital['logo'] }}" class="logo">
        @endif
        <span class="hospital-name">{{ $hospital['name_th'] }}</span>
        <span class="report-title">สรุปรายงานผลการดำเนินการตาม SLA (SLA Performance Summary)</span>
    </div>

    <div class="report-date">
        วันที่ออกรายงาน: {{ $reportDate }}
    </div>

    <div class="section-title">สรุปภาพรวม (Overview)</div>
    <table class="metrics-grid">
        <tr>
            <td class="metric-card">
                <span class="metric-value">{{ $dashboard['avg_response_hours'] }}</span>
                <span class="metric-label">เวลาตอบกลับเฉลี่ย (ชม.)</span>
            </td>
            <td class="metric-card">
                <span class="metric-value">{{ $dashboard['avg_acceptance_hours'] }}</span>
                <span class="metric-label">เวลารับงานเฉลี่ย (ชม.)</span>
            </td>
            <td class="metric-card">
                <span class="metric-value">{{ $dashboard['avg_resolution_hours'] }}</span>
                <span class="metric-label">เวลาแก้ไขเฉลี่ย (ชม.)</span>
            </td>
            <td class="metric-card">
                <span class="metric-value">{{ $dashboard['compliance_rate'] }}%</span>
                <span class="metric-label">อัตราบรรลุ SLA (%)</span>
            </td>
        </tr>
    </table>

    <div class="section-title">สัดส่วนสถานะในเดือนนี้ (Status Distribution)</div>
    <table>
        <thead>
            <tr>
                <th>สถานะ (Status)</th>
                <th style="text-align: center;">จำนวนรายการ (Count)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($chartData['distribution']['labels'] as $index => $label)
                <tr>
                    <td>{{ $label }}</td>
                    <td style="text-align: center;">{{ $chartData['distribution']['data'][$index] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">สรุปงานเกินเวลาแยกตามแผนก (Breaches by Department)</div>
    @if (count($chartData['department']['labels']) > 0)
        <table>
            <thead>
                <tr>
                    <th>ชื่อแผนก (Department)</th>
                    <th style="text-align: center;">จำนวนที่เกินเวลา (Breaches)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($chartData['department']['labels'] as $index => $label)
                    <tr>
                        <td>{{ $label }}</td>
                        <td style="text-align: center;">{{ $chartData['department']['data'][$index] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="text-align: center; color: #64748b;">ไม่มีข้อมูลงานที่เกินเวลาในเดือนนี้</p>
    @endif

    @if (count($breachedTickets) > 0)
        <div class="section-title" style="color: #ef4444; border-left-color: #ef4444;">รายการงานที่เกินเวลา (Breached
            Tickets)</div>
        <table>
            <thead>
                <tr>
                    <th>เลขที่ (No.)</th>
                    <th>รายการ (Title)</th>
                    <th>แผนก (Dept)</th>
                    <th>สถานะปัจจุบัน</th>
                    <th>วันกำหนดเสร็จ (Due Date)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($breachedTickets as $ticket)
                    <tr>
                        <td>{{ $ticket->request_no }}</td>
                        <td>{{ $ticket->title }}</td>
                        <td>{{ $ticket->department?->name_th ?? ($ticket->department?->name_en ?? '-') }}</td>
                        <td><span class="status-badge" style="color: #333;">{{ ucfirst($ticket->status) }}</span></td>
                        <td>{{ $ticket->sla_due_date->translatedFormat('d/m/Y H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="signature-section">
        @if (isset($signature))
            <div style="margin-bottom: 5px;">
                <img src="{{ $signature }}" style="max-height: 100px; width: auto;">
            </div>
        @else
            <div style="height: 100px;"></div>
        @endif
        <div style="font-size: 14pt;">ลงชื่อ...........................................................ผู้ส่งรายงาน
        </div>
        <div style="margin-top: 10px; font-size: 14pt;">(...........................................................)
        </div>
    </div>

</body>

</html>
