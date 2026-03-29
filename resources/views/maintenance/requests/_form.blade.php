@php
  /** @var \App\Models\MaintenanceRequest|null $req */
  $req = $req ?? null;

  $assets = is_iterable($assets ?? null) ? collect($assets) : collect();
  $depts  = is_iterable($depts ?? null) ? collect($depts) : collect();
  $types  = is_iterable($types ?? null) ? collect($types) : collect();

  $user   = auth()->user();
  $isEdit = (bool) optional($req)->exists;

  $line = "border-slate-200";

  $input = "mt-2 w-full h-11 rounded-md border $line bg-white px-3 py-2 text-sm
            focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100";

  $textarea = "mt-2 w-full rounded-md border $line bg-white px-3 py-2 text-sm
              focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100";

  $headCls   = "flex items-start gap-3 pb-3 min-h-[56px]";
  $noCls     = "w-8 h-8 shrink-0 rounded-full border border-emerald-600 bg-emerald-600
                flex items-center justify-center text-sm font-bold text-white leading-none";
  $titleCls  = "text-base font-semibold text-slate-900 leading-tight";
  $subCls    = "text-sm text-slate-500 leading-snug";
  $accentWrap= "min-w-0 relative pl-3 pt-[1px]";
  $accentBar = "absolute left-0 top-[2px] w-[3px] h-9 rounded-full bg-emerald-600/90";

  $v = function ($key, $default = '') use ($req) {
        $old = old($key);
        if (!is_null($old)) return $old;

        $modelVal = data_get($req, $key, null);
        if (!is_null($modelVal)) return $modelVal;

        $queryVal = request()->query($key, null);
        if (!is_null($queryVal)) return $queryVal;

        return $default;
    };
@endphp

<div class="mx-auto max-w-screen-2xl px-3 sm:px-6 lg:px-8">
  <div class="space-y-10">

    <div class="relative grid grid-cols-1 lg:grid-cols-2 gap-10">
      <div class="hidden lg:block absolute inset-y-0 left-1/2 w-px bg-slate-200"></div>

      <section>
        <div class="{{ $headCls }}">
          <div class="{{ $noCls }}">1</div>
          <div class="{{ $accentWrap }}">
            <span class="{{ $accentBar }}"></span>
            <div class="{{ $titleCls }}">ข้อมูลหลัก</div>
            <div class="{{ $subCls }}">ทรัพย์สิน / หน่วยงาน / สถานที่</div>
          </div>
        </div>

        <label class="block text-sm font-medium text-slate-700">
          ทรัพย์สิน <span class="text-rose-500 font-bold">*</span>
          <span class="ml-1 text-[11px] font-normal text-slate-500 italic">(ไม่จำเป็นต้องระบุหากไม่ได้ซ่อมครุภัณฑ์ในระบบ)</span>
        </label>
        <select name="asset_id" class="ts-basic mt-2 w-full" data-placeholder="— เลือกทรัพย์สิน —">
          <option value="">— ไม่ระบุ —</option>
          @foreach($assets as $a)
            @php $label = trim(($a->asset_code ? $a->asset_code.' - ' : '').($a->name ?? '')); @endphp
            <option value="{{ $a->id }}" @selected((string)$v('asset_id') === (string)$a->id)>
              {{ $label ?: '—' }}
            </option>
          @endforeach
        </select>

        <label class="block text-sm font-medium text-slate-700 mt-4">หน่วยงาน</label>
        <select name="department_id" class="ts-basic mt-2 w-full" data-placeholder="— เลือกหน่วยงาน —">
          <option value="">— ไม่ระบุ —</option>
          @foreach($depts as $d)
            @php
              $deptName = $d->name_th ?: ($d->name_en ?? '');
              $label = trim(($d->code ? $d->code.' - ' : '').$deptName);
            @endphp
            <option value="{{ $d->id }}" @selected((string)$v('department_id') === (string)$d->id)>
              {{ $label ?: '—' }}
            </option>
          @endforeach
        </select>

        <label class="block text-sm font-medium text-slate-700 mt-4">สถานที่ / ตำแหน่งงาน</label>
        <input type="text" name="location_text" value="{{ $v('location_text') }}" autocomplete="off" class="{{ $input }}">
      </section>

      <section>
        <div class="{{ $headCls }}">
          <div class="{{ $noCls }}">2</div>
          <div class="{{ $accentWrap }}">
            <span class="{{ $accentBar }}"></span>
            <div class="{{ $titleCls }}">รายละเอียดปัญหา</div>
            <div class="{{ $subCls }}">หัวข้อและอาการเสีย</div>
          </div>
        </div>

        <label class="block text-sm font-medium text-slate-700">
          ประเภทงาน (Report Type)
        </label>
        <select name="type_id" class="ts-basic mt-2 w-full" data-placeholder="— เลือกประเภทงาน —">
          <option value="">— ไม่ระบุ —</option>
          @foreach($types as $t)
            <option value="{{ $t->id }}" @selected((string)$v('type_id') === (string)$t->id)>
              {{ $t->name }}
            </option>
          @endforeach
        </select>

        <label class="block text-sm font-medium text-slate-700 mt-4">
          หัวข้อ <span class="text-rose-600">*</span>
        </label>
        <input type="text" name="title" value="{{ $v('title') }}" autocomplete="off" class="{{ $input }}" required>

        <label class="block text-sm font-medium text-slate-700 mt-4">รายละเอียด / อาการเสีย</label>
        <textarea name="description" rows="6" class="{{ $textarea }}">{{ $v('description') }}</textarea>
      </section>
    </div>

    <div class="border-t {{ $line }}"></div>

    <div class="relative grid grid-cols-1 lg:grid-cols-2 gap-10">
      <div class="hidden lg:block absolute inset-y-0 left-1/2 w-px bg-slate-200"></div>

      <section>
        <div class="{{ $headCls }}">
          <div class="{{ $noCls }}">3</div>
          <div class="{{ $accentWrap }}">
            <span class="{{ $accentBar }}"></span>
            <div class="{{ $titleCls }}">ผู้แจ้ง &amp; ความสำคัญ</div>
            <div class="{{ $subCls }}">ข้อมูลผู้แจ้ง + ระดับความสำคัญ</div>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-4">
          @if(!$isEdit && $user)
            <div>
              <label class="block text-sm font-medium text-slate-700">ผู้แจ้ง</label>
              <div class="mt-2 h-11 rounded-md border {{ $line }} bg-slate-50 px-3 flex items-center text-sm text-slate-700">
                {{ $user->name }}
              </div>
              <input type="hidden" name="reporter_name" value="{{ $user->name }}">
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700">เบอร์โทร (ถ้ามี)</label>
              <input type="text" name="reporter_phone" value="{{ $v('reporter_phone') }}" class="{{ $input }}">
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700">อีเมล (ถ้ามี)</label>
              <input type="email" name="reporter_email" value="{{ $v('reporter_email', $user->email) }}" class="{{ $input }}">
            </div>
          @else
            <div>
              <label class="block text-sm font-medium text-slate-700">ชื่อผู้แจ้ง</label>
              <input type="text" name="reporter_name" value="{{ $v('reporter_name') }}" class="{{ $input }}">
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700">เบอร์โทร</label>
              <input type="text" name="reporter_phone" value="{{ $v('reporter_phone') }}" class="{{ $input }}">
            </div>

            <div>
              <label class="block text-sm font-medium text-slate-700">อีเมล</label>
              <input type="email" name="reporter_email" value="{{ $v('reporter_email') }}" class="{{ $input }}">
            </div>
          @endif
        </div>

        <label class="block text-sm font-medium text-slate-700 mt-4">
          ระดับความสำคัญ <span class="text-rose-600">*</span>
        </label>
        @php $priority = $v('priority', 'medium'); @endphp
        <select name="priority" class="{{ $input }}" required>
          <option value="low"    @selected($priority === 'low')>ต่ำ</option>
          <option value="medium" @selected($priority === 'medium')>ปานกลาง</option>
          <option value="high"   @selected($priority === 'high')>สูง</option>
          <option value="urgent" @selected($priority === 'urgent')>เร่งด่วน</option>
        </select>
      </section>

      <section>
        <div class="{{ $headCls }}">
          <div class="{{ $noCls }}">4</div>
          <div class="{{ $accentWrap }}">
            <span class="{{ $accentBar }}"></span>
            <div class="{{ $titleCls }}">ไฟล์แนบ</div>
            <div class="{{ $subCls }}">แนบไฟล์ / ถ่ายรูปจากมือถือ</div>
          </div>
        </div>

        @php $attachments = is_iterable($attachments ?? null) ? $attachments : []; @endphp

        @if($isEdit && !empty($attachments) && count($attachments))
          <div class="mb-4">
            <div class="text-xs font-medium text-slate-600">ไฟล์ที่มีอยู่แล้ว</div>
            <div class="mt-2 divide-y divide-slate-200 rounded-md border {{ $line }}">
              @foreach($attachments as $att)
                <label class="flex items-center justify-between gap-3 px-3 py-2 text-sm">
                  <span class="truncate text-slate-700">{{ $att->original_name }}</span>
                  <span class="inline-flex items-center gap-2 text-rose-600">
                    <input type="checkbox" name="remove_attachments[]" value="{{ $att->id }}">
                    ลบ
                  </span>
                </label>
              @endforeach
            </div>
          </div>
        @endif

        <input id="mr_files_any"
               type="file"
               name="files[]"
               multiple
               accept="image/*,application/pdf"
               class="hidden">

        <input id="mr_files_camera"
               type="file"
               name="files[]"
               accept="image/*"
               capture="environment"
               class="hidden">

        <div class="flex items-center gap-2">
          {{-- ปุ่มแนบไฟล์ --}}
          <button type="button"
                  id="mr_files_any_btn"
                  class="inline-flex items-center justify-center h-11 px-4 rounded-md border {{ $line }} bg-white
                         text-sm font-medium text-slate-700 hover:bg-slate-50
                         focus:outline-none focus:ring-2 focus:ring-emerald-100">
            <svg class="h-4 w-4 mr-2 text-slate-600" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M21 11.5l-8.5 8.5a6 6 0 0 1-8.5-8.5l9.2-9.2a4 4 0 0 1 5.7 5.7l-9.2 9.2a2 2 0 0 1-2.8-2.8l8.7-8.7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            แนบไฟล์
          </button>

          {{-- ปุ่มกล้อง --}}
          <button type="button"
                  id="mr_files_camera_btn"
                  class="inline-flex items-center justify-center h-11 w-11 rounded-md border {{ $line }} bg-white
                         hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-100"
                  aria-label="ถ่ายรูป">
            <svg class="h-5 w-5 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
              <circle cx="12" cy="13" r="4"/>
            </svg>
          </button>

          <div class="min-w-0">
            <div class="text-xs text-slate-600">รองรับรูปภาพ และ PDF (แนบไฟล์) / ถ่ายรูป (กล้อง)</div>
          </div>
        </div>

        {{-- Preview list --}}
        <div id="mr_files_preview" class="mt-3 hidden">
          <div class="text-xs font-medium text-slate-600">ไฟล์ที่เลือก</div>
          <div id="mr_files_list" class="mt-2 divide-y divide-slate-200 rounded-md border {{ $line }} bg-white"></div>
        </div>

        <div class="mt-2 p-3 rounded-md bg-amber-50 border border-amber-200">
          <div class="flex gap-2">
            <svg class="h-5 w-5 text-amber-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <div class="text-xs text-amber-800 space-y-1">
              <p class="font-bold">เงื่อนไขการแนบไฟล์และแก้ไขข้อมูล:</p>
              <ul class="list-disc ml-4 space-y-0.5">
                <li>จำกัดจำนวนไฟล์แนบสูงสุด 3 ไฟล์ต่อใบงาน</li>
                <li>ผู้แจ้งซ่อมสามารถแก้ไขข้อมูลและไฟล์แนบได้เฉพาะก่อนช่างรับทราบงาน</li>
                <li>เมื่อใบงานถูกปิดหรือช่างเริ่มดำเนินการแล้ว จะไม่สามารถแก้ไขข้อมูลพื้นฐานได้</li>
              </ul>
            </div>
          </div>
        </div>


        <script>
          (function () {
            const anyInput = document.getElementById('mr_files_any');
            const camInput = document.getElementById('mr_files_camera');
            const anyBtn = document.getElementById('mr_files_any_btn');
            const camBtn = document.getElementById('mr_files_camera_btn');
            const preview = document.getElementById('mr_files_preview');
            const list = document.getElementById('mr_files_list');

            if (!anyInput || !camInput || !anyBtn || !camBtn || !preview || !list) return;

            let filesBag = [];

            const keyOf = (f) => `${f.name}|${f.size}|${f.type}|${f.lastModified || 0}`;

            const syncToInputs = () => {
              // สร้าง FileList ใหม่ให้ anyInput เพื่อ submit ทีเดียว (รวมทั้งจากกล้องด้วย)
              const dt = new DataTransfer();
              filesBag.forEach(f => dt.items.add(f));
              anyInput.files = dt.files;
            };

            const render = () => {
              list.innerHTML = '';
              if (!filesBag.length) {
                preview.classList.add('hidden');
                return;
              }
              preview.classList.remove('hidden');

              filesBag.forEach((f, idx) => {
                const card = document.createElement('div');
                card.className = 'p-3 rounded-lg border border-slate-200 bg-slate-50 space-y-2';

                const top = document.createElement('div');
                top.className = 'flex items-center justify-between gap-3';

                const left = document.createElement('div');
                left.className = 'min-w-0';

                const name = document.createElement('div');
                name.className = 'truncate text-[13px] font-medium text-slate-700';
                name.textContent = f.name;

                const meta = document.createElement('div');
                meta.className = 'text-[11px] text-slate-500';
                meta.textContent = (f.type || 'file') + ' • ' + Math.round(f.size / 1024) + ' KB';

                left.appendChild(name);
                left.appendChild(meta);

                const del = document.createElement('button');
                del.type = 'button';
                del.className = 'shrink-0 text-rose-600 hover:text-rose-700 text-xs font-medium';
                del.textContent = 'ลบ';
                del.addEventListener('click', () => {
                  filesBag.splice(idx, 1);
                  syncToInputs();
                  render();
                });

                top.appendChild(left);
                top.appendChild(del);
                card.appendChild(top);

                // Caption Input
                const capInput = document.createElement('input');
                capInput.type = 'text';
                capInput.name = 'captions[]';
                capInput.placeholder = 'เพิ่มคำอธิบายภาพ...';
                capInput.className = 'w-full h-8 px-2 py-1 text-[12px] rounded border border-slate-300 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-100 outline-none transition';
                card.appendChild(capInput);

                list.appendChild(card);
              });
            };

            const addFiles = (files) => {
              const arr = Array.from(files || []);
              if (!arr.length) return;

              const map = new Map(filesBag.map(f => [keyOf(f), f]));
              arr.forEach(f => map.set(keyOf(f), f));

              const existingCount = {{ isset($attachments) && is_iterable($attachments) ? count($attachments) : 0 }};
              const maxAllowed = Math.max(0, 3 - existingCount);

              if (map.size > maxAllowed) {
                window.dispatchEvent(new CustomEvent('app:toast', {
                  detail: { type: 'warning', message: 'สามารถแนบไฟล์เพิ่มได้อีก ' + maxAllowed + ' ไฟล์ (สูงสุด 3 ไฟล์ต่อใบงาน)' }
                }));
              }
              filesBag = Array.from(map.values()).slice(0, maxAllowed); // กันเกินโควต้า
              syncToInputs();
              render();
            };

            anyBtn.addEventListener('click', () => {
              anyInput.click();
            });

            camBtn.addEventListener('click', () => {
              camInput.click();
            });

            anyInput.addEventListener('change', () => {
              addFiles(anyInput.files);
              // reset ค่า เพื่อเลือกไฟล์เดิมซ้ำได้
              anyInput.value = '';
            });

            camInput.addEventListener('change', () => {
              addFiles(camInput.files);
              camInput.value = '';
            });

            // ถ้ามีไฟล์จาก old() (กรณี validation fail) เราไม่สามารถ restore file ได้ด้วยเบราว์เซอร์
          })();
        </script>
      </section>
    </div>

  </div>
</div>

<div class="flex justify-end gap-2 pt-4 mt-6 border-t {{ $line }}">
  <a href="{{ route('maintenance.requests.index') }}"
     class="inline-flex items-center justify-center h-10 px-4 rounded-lg border {{ $line }} bg-white
            text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
    ยกเลิก
  </a>
  <button type="submit"
          class="inline-flex items-center justify-center h-10 px-4 rounded-lg bg-emerald-600
                 text-sm font-medium text-white hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-200 transition-all">
    {{ $isEdit ? 'บันทึกการแก้ไข' : 'บันทึก' }}
  </button>
</div>
