@extends('layouts.app')
@section('title', 'Asset Categories')

@section('content')
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold">หมวดหมู่ทรัพย์สิน</h1>
    <a href="{{ route('asset-categories.create') }}"
       class="bg-emerald-600 text-white px-3 py-2 rounded hover:bg-emerald-700">
       + เพิ่มหมวดหมู่
    </a>
  </div>

  @if (session('status'))
    <div class="mb-4 p-3 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded">
      {{ session('status') }}
    </div>
  @endif

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
    @forelse ($categories as $c)
      <div class="border rounded-xl p-4 bg-white">
        <div class="flex justify-between">
          <h2 class="font-medium">{{ $c->name }}</h2>
          @if($c->color)
            <span class="inline-block w-4 h-4 rounded" style="background:{{ $c->color }}"></span>
          @endif
        </div>
        <p class="text-sm text-zinc-600 mt-1">{{ $c->description }}</p>
        <div class="mt-3 flex justify-between text-sm">
          <span class="{{ $c->is_active ? 'text-emerald-600' : 'text-zinc-400' }}">
            {{ $c->is_active ? 'Active' : 'Inactive' }}
          </span>
          <div class="space-x-2">
            <a href="{{ route('asset-categories.edit', $c) }}" class="text-blue-600 hover:underline">แก้ไข</a>
            <form method="POST" action="{{ route('asset-categories.destroy', $c) }}" class="inline"
                  onsubmit="return confirm('ลบหมวดหมู่นี้หรือไม่?')">
              @csrf @method('DELETE')
              <button type="submit" class="text-rose-600 hover:underline">ลบ</button>
            </form>
          </div>
        </div>
      </div>
    @empty
      <div class="col-span-full text-center text-zinc-500 py-8">ยังไม่มีข้อมูล</div>
    @endforelse
  </div>

  <div class="mt-4">
    {{ $categories->links() }}
  </div>
@endsection
