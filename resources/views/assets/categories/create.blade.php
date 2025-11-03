@extends('layouts.app')
@section('title', 'เพิ่มหมวดหมู่')

@section('content')
  <h1 class="text-xl font-semibold mb-4">เพิ่มหมวดหมู่</h1>
  <form method="POST" action="{{ route('asset-categories.store') }}" class="bg-white p-4 rounded-xl border">
    @include('assets.categories._form')
    <div class="mt-4 flex justify-end space-x-2">
      <a href="{{ route('asset-categories.index') }}" class="px-3 py-2 border rounded">ยกเลิก</a>
      <button class="px-4 py-2 bg-emerald-600 text-white rounded">บันทึก</button>
    </div>
  </form>
@endsection
