<section>
    <div class="{{ $headCls }}">
        <div class="{{ $noCls }}">{{ $sectionNumber ?? 6 }}</div>
        <div class="{{ $accentWrap }}">
            <span class="{{ $accentBar }}"></span>
            <div class="{{ $titleCls }}">รายงานการปฏิบัติงานและค่าใช้จ่าย <span
                    class="text-slate-500 text-[13px] font-normal">(บันทึกภายหลังได้)</span></div>
            <div class="{{ $subCls }}">สำหรับทีมเจ้าหน้าที่: ระบุวิธีคิดค่าใช้จ่าย, รพจ.
                และรายละเอียดประกอบ</div>
        </div>
    </div>

    <form method="POST" action="{{ route('maintenance.requests.operation-log', ['maintenanceRequest' => $mr]) }}"
        class="space-y-4" novalidate id="operation-log-form">
        @csrf

        <div class="max-w-[240px]">
            <label class="block text-sm font-medium text-slate-700">รายการซ่อมสำหรับวันที่</label>
            <input type="date" name="operation_date"
                value="{{ old('operation_date', optional($opLog?->operation_date)->format('Y-m-d')) }}"
                class="{{ $input }}" onclick="this.showPicker()">
        </div>

        <div>
            <div class="block text-sm font-medium text-slate-700">วิธีการปฏิบัติ / การคิดค่าใช้จ่าย</div>
            @php $method = old('operation_method', $opLog->operation_method ?? null); @endphp
            <div class="mt-2 space-y-2 text-sm">
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="operation_method" value="requisition" @checked($method === 'requisition')
                        class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span>ตามใบเบิกครุภัณฑ์ / วัสดุ</span>
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="operation_method" value="service_fee" @checked($method === 'service_fee')
                        class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span>ค่าบริการ / ค่าแรงเจ้าหน้าที่</span>
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="operation_method" value="other" @checked($method === 'other')
                        class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span>อื่น ๆ</span>
                </label>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700">ระบุรพจ. (รหัสครุภัณฑ์)</label>
            <input type="text" name="property_code"
                value="{{ old('property_code', $opLog->property_code ?? ($mr->asset?->asset_code ?? '')) }}"
                class="{{ $input }}" placeholder="เช่น 68101068718">
        </div>

        <div>
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="require_precheck" value="1" @checked(old('require_precheck', $opLog->require_precheck ?? false))
                    class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                <span>ยืนยันว่าได้แจ้งผู้ใช้งาน / หน่วยงาน และขออนุญาตก่อนปฏิบัติงาน/ปิดเครื่อง</span>
            </label>
        </div>

        <div>
            <div class="text-sm font-medium text-slate-700">ประเภทงานที่ปฏิบัติ</div>
            <div class="mt-2 space-y-2 text-sm text-slate-700">
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="issue_software" value="1" @checked(old('issue_software', $opLog->issue_software ?? false))
                        class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span>Software</span>
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="issue_hardware" value="1" @checked(old('issue_hardware', $opLog->issue_hardware ?? false))
                        class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span>Hardware</span>
                </label>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700">หมายเหตุ / รายละเอียดประกอบ</label>
            <textarea name="remark" rows="4" class="{{ $textarea }}"
                placeholder="เช่น ตรวจเช็คแล้วพบว่า..., ผู้ใช้ทดสอบแล้วเรียบร้อย">{{ old('remark', $opLog->remark ?? '') }}</textarea>
        </div>

        <div class="pt-2">
            <button type="submit"
                class="inline-flex items-center justify-center h-10 px-4 rounded-lg bg-emerald-600
             text-sm font-medium text-white hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-200">
                บันทึกรายงานการปฏิบัติงาน
            </button>
        </div>

        @if ($opLog)
            <p class="mt-3 text-xs text-slate-500">
                บันทึกล่าสุดโดย {{ $opLog->user?->name ?? '-' }} ·
                {{ $opLog->updated_at?->format('Y-m-d H:i') ?? '-' }}
            </p>
        @endif
    </form>
</section>
