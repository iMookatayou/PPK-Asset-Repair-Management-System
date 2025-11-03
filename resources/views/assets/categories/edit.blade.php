@extends('layouts.app')
@section('title', 'แก้ไขหมวดหมู่')

@section('content')
  <h1 class="text-xl font-semibold mb-4">แก้ไขหมวดหมู่</h1>
  <form method="POST" action="{{ route('asset-categories.update', $category) }}" class="bg-white p-4 rounded-xl border">
    @method('PUT')
    @include('assets.categories._form')
    <div class="mt-4 flex justify-end space-x-2">
      <a href="{{ route('asset-categories.index') }}" class="px-3 py-2 border rounded">ยกเลิก</a>
      <button class="px-4 py-2 bg-emerald-600 text-white rounded">อัปเดต</button>
    </div>
  </form>
@endsection
