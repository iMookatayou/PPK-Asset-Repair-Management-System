@extends('layouts.app')
@section('title','แจ้งซ่อมใหม่')
@section('content')
<style>
  :root{--bg:#0b0b0b;--card:#131313;--line:#27272a;--muted:#a1a1aa;--text:#e5e7eb;--field:#0f0f10;--primary:#1f2937;--primary-line:#3f3f46}
  .card{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:16px;margin:16px 0}
  .row{display:grid;grid-template-columns:1fr 1fr;gap:10px}
  @media (max-width:900px){.row{grid-template-columns:1fr}}
  input,select,textarea{width:100%;padding:10px 12px;background:var(--field);color:#e5e7eb;border:1px solid #30363d;border-radius:10px}
  .btn{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border-radius:10px;border:1px solid var(--primary-line);background:var(--primary);color:#fff}
  label{font-size:13px;color:var(--muted);display:block;margin-bottom:6px}
</style>

<div class="container">
  <h1 style="display:flex;align-items:center;gap:8px"><i data-lucide="plus-circle"></i> แจ้งซ่อมใหม่</h1>

  <div class="card">
    @if($errors->any())
      <div class="chip" style="margin-bottom:10px;border:1px solid #ef4444;background:#0f0f10;color:#ef4444;display:inline-flex;gap:6px;padding:6px 10px;border-radius:999px">
        <i data-lucide="alert-triangle"></i> ตรวจสอบข้อมูลที่กรอก
      </div>
    @endif

    <form method="post" action="{{ route('maintenance.storeFromBlade') }}">
      @csrf
      <div class="row">
        <div>
          <label><i data-lucide="package"></i> Asset</label>
          <select name="asset_id" required>
            <option value="">-- เลือกทรัพย์สิน --</option>
            @foreach($assets as $a)
              <option value="{{ $a->id }}" @selected(old('asset_id')==$a->id)>{{ $a->id }} — {{ $a->name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label><i data-lucide="flag-triangle-right"></i> Priority</label>
          <select name="priority" required>
            @foreach(['low','medium','high','urgent'] as $p)
              <option value="{{ $p }}" @selected(old('priority','medium')==$p)>{{ $p }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="row" style="margin-top:10px">
        <div>
          <label><i data-lucide="type"></i> Title</label>
          <input name="title" value="{{ old('title') }}" placeholder="อาการ/ปัญหา" required>
        </div>
        <div>
          <label><i data-lucide="user-round"></i> ผู้แจ้ง</label>
          <input value="{{ auth()->user()->name ?? auth()->id() }}" disabled>
        </div>
      </div>

      <div style="margin-top:10px">
        <label><i data-lucide="file-text"></i> Description</label>
        <textarea name="description" rows="3" placeholder="รายละเอียดเพิ่มเติม (ถ้ามี)">{{ old('description') }}</textarea>
      </div>

      <div style="margin-top:12px;display:flex;gap:8px">
        <button class="btn"><i data-lucide="save"></i> บันทึก</button>
        <a class="btn" href="{{ route('maintenance.indexPage') }}"><i data-lucide="arrow-left"></i> กลับรายการ</a>
      </div>
    </form>
  </div>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script>lucide.createIcons({attrs:{width:18,height:18}});</script>
@endsection
