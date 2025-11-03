@extends('layouts.app')
@section('title','Community Chat')

@section('content')
<div class="max-w-5xl mx-auto space-y-4">
  <form method="GET" class="flex gap-2">
    <input name="q" value="{{ e(request('q','')) }}" placeholder="ค้นหากระทู้..."
           class="w-full rounded-lg border border-slate-300 px-3 py-2">
    <button class="rounded-lg px-3 py-2 bg-[#0E2B51] text-white">Search</button>
  </form>

  <form method="POST" action="{{ route('chat.store') }}" class="section-card p-4 space-y-2">
    @csrf
    <label class="text-sm text-slate-600">สร้างกระทู้ใหม่</label>
    <input name="title" required maxlength="180" class="w-full rounded-lg border px-3 py-2"
           placeholder="ถามอะไรก็ได้ เช่น “วิธีเลือกเครื่องพิมพ์สำหรับงานเวรคืน”">
    <div class="text-right">
      <button class="rounded-lg bg-emerald-600 text-white px-3 py-2">โพสต์</button>
    </div>
  </form>

  <div class="section-card">
    <div class="section-head p-3 border-b">กระทู้ล่าสุด</div>
    <div class="divide-y">
      @forelse($threads as $th)
        <a href="{{ route('chat.show',$th) }}" class="block px-4 py-3 hover:bg-gray-50">
          <div class="font-medium">{{ $th->title }}</div>
          <div class="text-xs text-gray-500">โดย {{ $th->author->name }} • {{ $th->created_at->diffForHumans() }}</div>
        </a>
      @empty
        <div class="p-6 text-gray-500 text-center">ยังไม่มีกระทู้</div>
      @endforelse
    </div>
    <div class="p-3">{{ $threads->withQueryString()->links() }}</div>
  </div>
</div>
@endsection
