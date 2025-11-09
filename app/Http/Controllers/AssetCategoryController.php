<?php

namespace App\Http\Controllers;

use App\Models\AssetCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class AssetCategoryController extends Controller
{
    public function index()
    {
        $categories = AssetCategory::orderBy('name')->paginate(12);
        return view('assets.categories.index', compact('categories'));
    }

    public function create()
    {
        $category = new AssetCategory(['is_active' => true]);
        return view('assets.categories.create', compact('category'));
    }

    public function store(Request $request)
    {
        // Validate base fields (excluding slug), then derive slug and validate its uniqueness deterministically
        $base = $request->validate([
            'name'        => 'required|string|max:100|unique:asset_categories,name',
            'description' => 'nullable|string|max:1000',
            'color'       => 'nullable|string|max:20',
            'is_active'   => 'boolean',
        ]);

        $slug = Str::slug($request->input('slug') ?: $base['name']);
        Validator::make(
            ['slug' => $slug],
            ['slug' => ['required','string','max:120', Rule::unique('asset_categories','slug')]]
        )->validate();

        $data = array_merge($base, ['slug' => $slug]);

        AssetCategory::create($data);

        return redirect()->route('asset-categories.index')
            ->with('toast', \App\Support\Toast::success('เพิ่มหมวดหมู่เรียบร้อย'));
    }

    public function edit(AssetCategory $asset_category)
    {
        return view('assets.categories.edit', ['category' => $asset_category]);
    }

    public function update(Request $request, AssetCategory $asset_category)
    {
        // Validate base fields (excluding slug), then re-derive/accept slug and validate uniqueness ignoring current record
        $base = $request->validate([
            'name'        => 'required|string|max:100|unique:asset_categories,name,'.$asset_category->id,
            'description' => 'nullable|string|max:1000',
            'color'       => 'nullable|string|max:20',
            'is_active'   => 'boolean',
        ]);

        $slugInput = $request->input('slug');
        $slug = Str::slug($slugInput ?: $base['name']);
        Validator::make(
            ['slug' => $slug],
            ['slug' => ['required','string','max:120', Rule::unique('asset_categories','slug')->ignore($asset_category->id)]]
        )->validate();

        $data = array_merge($base, ['slug' => $slug]);

        $asset_category->update($data);

        return redirect()->route('asset-categories.index')
            ->with('toast', \App\Support\Toast::success('อัปเดตหมวดหมู่แล้ว'));
    }

    public function destroy(AssetCategory $asset_category)
    {
    $asset_category->delete();
    return back()->with('toast', \App\Support\Toast::success('ลบหมวดหมู่เรียบร้อย'));
    }
}
