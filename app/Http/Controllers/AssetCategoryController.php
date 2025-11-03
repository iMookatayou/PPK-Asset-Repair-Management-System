<?php

namespace App\Http\Controllers;

use App\Models\AssetCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:asset_categories,name',
            'slug' => 'nullable|string|max:120|unique:asset_categories,slug',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        AssetCategory::create($data);

        return redirect()->route('asset-categories.index')->with('status', 'Category created successfully.');
    }

    public function edit(AssetCategory $asset_category)
    {
        return view('assets.categories.edit', ['category' => $asset_category]);
    }

    public function update(Request $request, AssetCategory $asset_category)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:asset_categories,name,'.$asset_category->id,
            'slug' => 'nullable|string|max:120|unique:asset_categories,slug,'.$asset_category->id,
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $asset_category->update($data);

        return redirect()->route('asset-categories.index')->with('status', 'Category updated.');
    }

    public function destroy(AssetCategory $asset_category)
    {
        $asset_category->delete();
        return back()->with('status', 'Category deleted.');
    }
}
