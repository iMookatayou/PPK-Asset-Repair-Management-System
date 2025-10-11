@extends('layouts.app')
@section('title','Repair #'.$req->id)
@section('content')
<style>
  :root{--bg:#0b0b0b;--card:#131313;--line:#27272a;--muted:#a1a1aa;--text:#e5e7eb;--field:#0f0f10;--primary:#1f2937;--primary-line:#3f3f46;--good:#16a34a;--warn:#eab308;--bad:#ef4444}
  .card{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:16px;margin:16px 0}
  .meta{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
  @media (max-width:900px){.meta{grid-template-columns:repeat(2,1fr)}}
  .chip{display:inline-flex;align-items:center;gap:6px;padding:4px 8px;border-radius:999px;border:1px solid var(--line);background:#0f0f10;font-size:12px}
  input,select,textarea{width:100%;padding:10px 12px;background:var(--field);color:#e5e7eb;border:1px solid #30363d;border-radius:10px}
  .btn{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border-radius:10px;border:1px solid var(--primary-line);background:var(--primary);color:#fff}
  .row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
  .timeline{position:relative;padding-left:22px}
  .timeline:before{content:"";position:absolute;left:8px;top:6px;bottom:6px;width:2px;background:#242424;border-radius:2px}
  .t-item{position:relative;margin:12px 0;padding-left:12px}
  .dot{position:absolute;left:-1px;top:4px;width:18px;height:18px;border-radius:50%;background:#0f0f10;border:2px solid #3f3f46;display:flex;align-items:center;justify-content:center}
  .dot.good{border-color:var(--good)} .dot.warn{border-color:var(--warn)} .dot.bad{border-color:var(--bad)}
  .thumbs{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px}
  .thumb{border:1px solid var(--line);border-radius:12px;overflow:hidden;background:#0f0f10}
  .thumb img{display:block;width:100%;height:120px;object-fit:cover}
  .thumb .cap{padding:6px 8px;font-size:12px;color:var(--muted);display:flex;align-items:center;justify-content:space-between}
</style>

<div class="container">
  <h1 style="display:flex;align-items:center;gap:8px"><i data-lucide="wrench"></i> Repair Detail #{{ $req->id }}</h1>

  @if(session('ok'))
    <div class="chip" style="margin-top:8px;border-color:#16a34a"><i data-lucide="check-circle-2"></i>{{ session('ok') }}</div>
  @endif
  @if($errors->any())
    <div class="chip" style="margin-top:8px;border-color:#ef4444;color:#ef4444"><i data-lucide="alert-triangle"></i> ดำเนินการไม่สำเร็จ</div>
  @endif

  <div class="card">
    <div class="meta">
      <div><span class="chip"><i data-lucide="hash"></i> {{ $req->id }}</span></div>
      <div><span class="chip"><i data-lucide="package"></i> {{ $req->asset->name ?? $req->asset_id }}</span></div>
      <div><span class="chip"><i data-lucide="workflow"></i> {{ $req->status }}</span></div>
      <div><span class="chip"><i data-lucide="flag-triangle-right"></i> {{ $req->priority }}</span></div>
    </div>
    <div style="margin-top:10px;display:grid;grid-template-columns:2fr 1fr;gap:12px">
      <div>
        <div style="color:#a1a1aa;display:flex;align-items:center;gap:6px"><i data-lucide="type"></i> Title</div>
        <div style="font-weight:600">{{ $req->title }}</div>
      </div>
      <div>
        <div style="color:#a1a1aa;display:flex;align-items:center;gap:6px"><i data-lucide="calendar-clock"></i> Dates</div>
        <div class="chip">Requested: {{ $req->request_date ?? $req->created_at }}</div>
        @if($req->assigned_date)<div class="chip" style="margin-top:6px">Assigned: {{ $req->assigned_date }}</div>@endif
        @if($req->completed_date)<div class="chip" style="margin-top:6px">Done: {{ $req->completed_date }}</div>@endif
      </div>
    </div>
    <div style="margin-top:10px">
      <div style="color:#a1a1aa;display:flex;align-items:center;gap:6px"><i data-lucide="file-text"></i> Description</div>
      <div>{{ $req->description ?: '-' }}</div>
    </div>
  </div>

  <div class="card">
    <h3 style="display:flex;align-items:center;gap:8px"><i data-lucide="settings-2"></i> ดำเนินการ</h3>
    <form method="post" action="{{ route('maintenance.transitionFromBlade',$req) }}" class="row" style="margin-top:8px">
      @csrf
      <div>
        <label><i data-lucide="workflow"></i> Action</label>
        <select name="action" required>
          <option value="assign">assign</option>
          <option value="start">start</option>
          <option value="complete">complete</option>
          <option value="cancel">cancel</option>
        </select>
      </div>
      <div>
        <label><i data-lucide="user-round"></i> Technician ID (เฉพาะ assign)</label>
        <input type="number" name="technician_id" placeholder="เช่น 5">
      </div>
      <div style="grid-column:1/-1">
        <label><i data-lucide="message-square"></i> Remark</label>
        <input type="text" name="remark" placeholder="บันทึกเพิ่มเติม (optional)">
      </div>
      <div style="grid-column:1/-1;display:flex;gap:8px">
        <button class="btn"><i data-lucide="save"></i> บันทึก</button>
        <a class="btn" href="{{ route('maintenance.indexPage') }}"><i data-lucide="arrow-left"></i> กลับรายการ</a>
      </div>
    </form>
  </div>

  <div class="card">
    <h3 style="display:flex;align-items:center;gap:8px"><i data-lucide="image-up"></i> อัปโหลดไฟล์</h3>
    <form method="post" enctype="multipart/form-data" action="{{ route('maintenance.uploadAttachmentFromBlade',$req) }}" class="row" style="margin-top:8px">
      @csrf
      <div>
        <label><i data-lucide="badge-info"></i> ประเภท</label>
        <select name="type">
          <option value="before">before</option>
          <option value="after">after</option>
          <option value="other" selected>other</option>
        </select>
      </div>
      <div>
        <label><i data-lucide="paperclip"></i> ไฟล์</label>
        <input type="file" name="file" required>
      </div>
      <div style="grid-column:1/-1">
        <button class="btn"><i data-lucide="upload-cloud"></i> อัปโหลด</button>
      </div>
    </form>

    @if($req->attachments->count())
      <div class="thumbs" style="margin-top:10px">
        @foreach($req->attachments as $att)
          @php
            $name = $att->original_name ?? $att->path;
            $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $isImg = in_array($ext,['jpg','jpeg','png','gif','webp','bmp']);
            $url = asset('storage/'.$att->path);
          @endphp
          <div class="thumb">
            @if($isImg)
              <a href="{{ $url }}" target="_blank" rel="noopener"><img src="{{ $url }}" alt=""></a>
            @else
              <div style="height:120px;display:flex;align-items:center;justify-content:center;color:#a1a1aa">
                <i data-lucide="file"></i> {{ strtoupper($ext ?: 'FILE') }}
              </div>
            @endif
            <div class="cap">
              <span class="chip"><i data-lucide="tag"></i>{{ $att->type ?? 'other' }}</span>
              <a class="chip" href="{{ $url }}" target="_blank" rel="noopener"><i data-lucide="download"></i> เปิด</a>
            </div>
          </div>
        @endforeach
      </div>
    @else
      <div class="muted">ไม่มีไฟล์แนบ</div>
    @endif
  </div>

  <div class="card">
    <h3 style="display:flex;align-items:center;gap:8px"><i data-lucide="timeline"></i> ประวัติ (Timeline)</h3>
    <div class="timeline" style="margin-top:6px">
      @forelse($req->logs as $log)
        @php
          $cls = match($log->action) {
            'complete_request' => 'good',
            'cancel_request'   => 'bad',
            'assign_technician','start_request' => 'warn',
            default => ''
          };
        @endphp
        <div class="t-item">
          <div class="dot {{ $cls }}"><i data-lucide="dot"></i></div>
          <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
            <strong>#{{ $log->id }}</strong>
            <span class="chip"><i data-lucide="workflow"></i>{{ $log->action }}</span>
            <span class="chip"><i data-lucide="calendar-clock"></i>{{ $log->created_at->format('Y-m-d H:i') }}</span>
            @if($log->user_id)<span class="chip"><i data-lucide="user"></i>{{ $log->user_id }}</span>@endif
          </div>
          @if($log->note)
            <div style="margin-top:6px">{{ $log->note }}</div>
          @endif
        </div>
      @empty
        <div class="muted">ยังไม่มีบันทึก</div>
      @endforelse
    </div>
  </div>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script>lucide.createIcons({attrs:{width:18,height:18}});</script>
@endsection
