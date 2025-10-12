@extends('layouts.app')

@section('title','Repair #'.$req->id)

@push('head')
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
@endpush

@section('content')
<div class="container">
  <h1 style="display:flex;align-items:center;gap:8px"><i data-lucide="wrench"></i> Repair Detail #{{ $req->id }}</h1>

  @if(session('ok'))
    <div class="chip" role="status" aria-live="polite" style="margin-top:8px;border-color:#16a34a">
      <i data-lucide="check-circle-2"></i>{{ session('ok') }}
    </div>
  @endif
  @if($errors->any())
    <div class="chip" role="alert" style="margin-top:8px;border-color:#ef4444;color:#ef4444">
      <i data-lucide="alert-triangle"></i> ดำเนินการไม่สำเร็จ
    </div>
  @endif

  <div class="card">
    <div class="meta">
      <div><span class="chip"><i data-lucide="hash"></i> {{ $req->id }}</span></div>
      <div><span class="chip"><i data-lucide="package"></i> {{ $req->asset->name ?? $req->asset_id }}</span></div>
      <div><span class="chip"><i data-lucide="workflow"></i> {{ str_replace('_',' ', $req->status) }}</span></div>
      <div><span class="chip"><i data-lucide="flag-triangle-right"></i> {{ $req->priority }}</span></div>
    </div>

    <div style="margin-top:10px;display:grid;grid-template-columns:2fr 1fr;gap:12px">
      <div>
        <div style="color:#a1a1aa;display:flex;align-items:center;gap:6px"><i data-lucide="type"></i> Title</div>
        <div style="font-weight:600">{{ $req->title }}</div>
      </div>
      <div>
        <div style="color:#a1a1aa;display:flex;align-items:center;gap:6px"><i data-lucide="calendar-clock"></i> Dates</div>
        @php $requestedAt = optional($req->request_date ?? $req->created_at); @endphp
        <div class="chip">
          Requested:
          @if($requestedAt)
            <time datetime="{{ $requestedAt->toIso8601String() }}">{{ $requestedAt->format('Y-m-d H:i') }}</time>
          @else - @endif
        </div>
        @if($req->assigned_date)
          <div class="chip" style="margin-top:6px">
            Assigned: <time datetime="{{ $req->assigned_date->toIso8601String() }}">{{ $req->assigned_date->format('Y-m-d H:i') }}</time>
          </div>
        @endif
        @if($req->completed_date)
          <div class="chip" style="margin-top:6px">
            Done: <time datetime="{{ $req->completed_date->toIso8601String() }}">{{ $req->completed_date->format('Y-m-d H:i') }}</time>
          </div>
        @endif
      </div>
    </div>

    <div style="margin-top:10px">
      <div style="color:#a1a1aa;display:flex;align-items:center;gap:6px"><i data-lucide="file-text"></i> Description</div>
      <div class="prose prose-invert">{{ $req->description ?: '-' }}</div>
    </div>
  </div>

  {{-- Actions --}}
  <div class="card">
    <h3 style="display:flex;align-items:center;gap:8px"><i data-lucide="settings-2"></i> ดำเนินการ</h3>

    <form method="post"
          action="{{ route('maintenance.requests.transition', $req) }}"
          class="row" style="margin-top:8px" aria-label="Change request status form">
      @csrf

      <div>
        <label for="action"><i data-lucide="workflow"></i> Action</label>
        <select id="action" name="action" required aria-required="true">
          <option value="assign">assign</option>
          <option value="start">start</option>
          <option value="complete">complete</option>
          <option value="cancel">cancel</option>
        </select>
      </div>

      <div>
        <label for="technician_id"><i data-lucide="user-round"></i> Technician ID (เฉพาะ assign)</label>
        <input id="technician_id" type="number" name="technician_id" placeholder="เช่น 5" inputmode="numeric" />
      </div>

      <div style="grid-column:1/-1">
        <label for="remark"><i data-lucide="message-square"></i> Remark</label>
        <input id="remark" type="text" name="remark" placeholder="บันทึกเพิ่มเติม (optional)" />
      </div>

      <div style="grid-column:1/-1;display:flex;gap:8px">
        <button type="submit" class="btn"><i data-lucide="save"></i> บันทึก</button>
        <a class="btn" href="{{ route('maintenance.requests.index') }}"><i data-lucide="arrow-left"></i> กลับรายการ</a>
      </div>
    </form>
  </div>

  {{-- Attachments --}}
  <div class="card">
    <h3 style="display:flex;align-items:center;gap:8px"><i data-lucide="image-up"></i> อัปโหลดไฟล์</h3>

    <form method="post"
          enctype="multipart/form-data"
          action="{{ route('maintenance.requests.attachments', $req) }}"
          class="row" style="margin-top:8px" aria-label="Upload attachment form">
      @csrf

      <div>
        <label for="att_type"><i data-lucide="badge-info"></i> ประเภท</label>
        <select id="att_type" name="type">
          <option value="before">before</option>
          <option value="after">after</option>
          <option value="other" selected>other</option>
        </select>
      </div>

      <div>
        <label for="file"><i data-lucide="paperclip"></i> ไฟล์</label>
        <input id="file" type="file" name="file" required aria-required="true" />
      </div>

      <div style="grid-column:1/-1">
        <button type="submit" class="btn"><i data-lucide="upload-cloud"></i> อัปโหลด</button>
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
          <figure class="thumb">
            @if($isImg)
              <a href="{{ $url }}" target="_blank" rel="noopener">
                <img src="{{ $url }}" alt="{{ $att->original_name ?? 'attachment' }}">
              </a>
            @else
              <div style="height:120px;display:flex;align-items:center;justify-content:center;color:#a1a1aa">
                <i data-lucide="file"></i> {{ strtoupper($ext ?: 'FILE') }}
              </div>
            @endif
            <figcaption class="cap">
              <span class="chip"><i data-lucide="tag"></i>{{ $att->type ?? 'other' }}</span>
              <a class="chip" href="{{ $url }}" target="_blank" rel="noopener">
                <i data-lucide="download"></i> เปิด
              </a>
            </figcaption>
          </figure>
        @endforeach
      </div>
    @else
      <p class="muted">ไม่มีไฟล์แนบ</p>
    @endif
  </div>

  {{-- Timeline --}}
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
        <article class="t-item">
          <div class="dot {{ $cls }}"><i data-lucide="dot"></i></div>
          <header style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
            <strong>#{{ $log->id }}</strong>
            <span class="chip"><i data-lucide="workflow"></i>{{ $log->action }}</span>
            <span class="chip">
              <i data-lucide="calendar-clock"></i>
              <time datetime="{{ $log->created_at->toIso8601String() }}">{{ $log->created_at->format('Y-m-d H:i') }}</time>
            </span>
            @if($log->user_id)
              <span class="chip"><i data-lucide="user"></i>{{ $log->user_id }}</span>
            @endif
          </header>
          @if($log->note)
            <p style="margin-top:6px">{{ $log->note }}</p>
          @endif
        </article>
      @empty
        <p class="muted">ยังไม่มีบันทึก</p>
      @endforelse
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/lucide@latest"></script>
<script>lucide.createIcons({attrs:{width:18,height:18}});</script>
@endpush
