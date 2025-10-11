@extends('layouts.app')
@section('title','Maintenance – รายการคำขอ')
@section('content')
<style>
  :root{--bg:#0b0b0b;--card:#131313;--line:#27272a;--muted:#a1a1aa;--text:#e5e7eb;--field:#0f0f10;--primary:#1f2937;--primary-line:#3f3f46}
  .card{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:16px;margin:16px 0}
  .row{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
  @media (max-width:900px){.row{grid-template-columns:1fr}}
  input,select{width:100%;padding:10px 12px;background:var(--field);color:#e5e7eb;border:1px solid #30363d;border-radius:10px}
  .btn{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border-radius:10px;border:1px solid var(--primary-line);background:var(--primary);color:#fff;text-decoration:none}
  table{width:100%;border-collapse:collapse}
  th,td{padding:10px;border-bottom:1px solid #2a2a2a}
  .chip{display:inline-flex;align-items:center;gap:6px;padding:4px 8px;border-radius:999px;border:1px solid var(--line);background:#0f0f10;font-size:12px}
</style>

<div class="container">
  <h1 class="inline" style="display:flex;align-items:center;gap:8px"><i data-lucide="wrench"></i> รายการคำขอซ่อม</h1>

  <div class="card">
    <form method="get" class="row" style="align-items:end">
      <div>
        <label class="muted" style="display:block;margin-bottom:6px">สถานะ</label>
        <select name="status">
          <option value="">ทั้งหมด</option>
          @foreach(['pending','in_progress','completed','cancelled'] as $s)
          <option value="{{ $s }}" @selected($status===$s)>{{ $s }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="muted" style="display:block;margin-bottom:6px">ความสำคัญ</label>
        <select name="priority">
          <option value="">ทั้งหมด</option>
          @foreach(['low','medium','high','urgent'] as $p)
          <option value="{{ $p }}" @selected($priority===$p)>{{ $p }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="muted" style="display:block;margin-bottom:6px">ค้นหา</label>
        <input name="q" value="{{ $q }}" placeholder="ชื่อเรื่อง / รายละเอียด">
      </div>
      <div style="display:flex;gap:8px">
        <button class="btn" type="submit"><i data-lucide="search"></i> ค้นหา</button>
        <a href="{{ route('maintenance.createPage') }}" class="btn"><i data-lucide="plus-circle"></i> แจ้งซ่อมใหม่</a>
      </div>
    </form>
  </div>

  <div class="card">
    <table>
      <thead>
        <tr>
          <th>ID</th><th>Asset</th><th>Title</th><th>Status</th><th>Priority</th><th>Requested</th><th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($list as $r)
          <tr>
            <td>{{ $r->id }}</td>
            <td>{{ $r->asset->name ?? $r->asset_id }}</td>
            <td>{{ $r->title }}</td>
            <td><span class="chip"><i data-lucide="workflow"></i>{{ $r->status }}</span></td>
            <td><span class="chip"><i data-lucide="flag-triangle-right"></i>{{ $r->priority }}</span></td>
            <td>{{ $r->request_date ?? $r->created_at }}</td>
            <td><a class="btn" href="{{ route('maintenance.showPage',$r) }}"><i data-lucide="external-link"></i> เปิด</a></td>
          </tr>
        @empty
          <tr><td colspan="7" class="muted">ไม่มีข้อมูล</td></tr>
        @endforelse
      </tbody>
    </table>

    <div style="margin-top:10px">
      {{ $list->links() }}
    </div>
  </div>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script>lucide.createIcons({attrs:{width:18,height:18}});</script>
@endsection
