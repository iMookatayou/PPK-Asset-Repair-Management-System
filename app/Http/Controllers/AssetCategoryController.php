<?php

namespace App\Http\Controllers;

use App\Models\AssetCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AssetCategoryController extends Controller
{
    public function index()
    {
        $categories = AssetCategory::orderBy('name')->paginate(12);

        Log::info('[AssetCategory::index] listing', [
            'total'    => $categories->total(),
            'actor_id' => request()->user()?->id,
        ]);

        return view('assets.categories.index', compact('categories'));
    }

    public function create()
    {
        $category = new AssetCategory(['is_active' => true]);
        return view('assets.categories.create', compact('category'));
    }

    public function store(Request $request)
    {
        $validatorBase = Validator::make($request->all(), [
            'name'        => 'required|string|max:100|unique:asset_categories,name',
            'description' => 'nullable|string|max:1000',
            'color'       => 'nullable|string|max:20',
            'is_active'   => 'boolean',
        ]);

        if ($validatorBase->fails()) {
            Log::warning('[AssetCategory::store] validation failed (base)', [
                'errors'   => $validatorBase->errors()->toArray(),
                'input'    => $request->except(['_token']),
                'actor_id' => $request->user()?->id,
            ]);

            return back()
                ->withErrors($validatorBase)
                ->withInput()
                ->with('toast', \App\Support\Toast::error('ข้อมูลไม่ถูกต้อง: name', 2600));
        }

        $base = $validatorBase->validated();
        $slug = Str::slug($request->input('slug') ?: $base['name']);

        $validatorSlug = Validator::make(
            ['slug' => $slug],
            ['slug' => ['required', 'string', 'max:120', Rule::unique('asset_categories', 'slug')]]
        );

        if ($validatorSlug->fails()) {
            Log::warning('[AssetCategory::store] slug validation failed', [
                'slug'     => $slug,
                'actor_id' => $request->user()?->id,
            ]);

            return back()
                ->withErrors($validatorSlug)
                ->withInput()
                ->with('toast', \App\Support\Toast::error('Slug ไม่ถูกต้อง หรือซ้ำ', 2600));
        }

        $validatorSlug->validated();
        $data     = array_merge($base, ['slug' => $slug]);
        $category = AssetCategory::create($data);

        Log::info('[AssetCategory::store] created', [
            'category_id' => $category->id,
            'name'        => $category->name,
            'slug'        => $category->slug,
            'is_active'   => $category->is_active,
            'actor_id'    => $request->user()?->id,
        ]);

        return redirect()
            ->route('asset-categories.index')
            ->with('toast', \App\Support\Toast::success('เพิ่มหมวดหมู่เรียบร้อย'));
    }

    public function edit(AssetCategory $asset_category)
    {
        return view('assets.categories.edit', ['category' => $asset_category]);
    }

    public function update(Request $request, AssetCategory $asset_category)
    {
        $validatorBase = Validator::make($request->all(), [
            'name'        => 'required|string|max:100|unique:asset_categories,name,' . $asset_category->id,
            'description' => 'nullable|string|max:1000',
            'color'       => 'nullable|string|max:20',
            'is_active'   => 'boolean',
        ]);

        if ($validatorBase->fails()) {
            Log::warning('[AssetCategory::update] validation failed (base)', [
                'category_id' => $asset_category->id,
                'errors'      => $validatorBase->errors()->toArray(),
                'actor_id'    => $request->user()?->id,
            ]);

            return back()
                ->withErrors($validatorBase)
                ->withInput()
                ->with('toast', \App\Support\Toast::error('ข้อมูลไม่ถูกต้อง', 2600));
        }

        $base      = $validatorBase->validated();
        $slugInput = $request->input('slug');
        $slug      = Str::slug($slugInput ?: $base['name']);

        $validatorSlug = Validator::make(
            ['slug' => $slug],
            ['slug' => ['required', 'string', 'max:120', Rule::unique('asset_categories', 'slug')->ignore($asset_category->id)]]
        );

        if ($validatorSlug->fails()) {
            Log::warning('[AssetCategory::update] slug validation failed', [
                'category_id' => $asset_category->id,
                'slug'        => $slug,
                'actor_id'    => $request->user()?->id,
            ]);

            return back()
                ->withErrors($validatorSlug)
                ->withInput()
                ->with('toast', \App\Support\Toast::error('Slug ไม่ถูกต้อง หรือซ้ำ', 2600));
        }

        $validatorSlug->validated();

        $before = $asset_category->only(['name', 'slug', 'color', 'is_active', 'description']);
        $data   = array_merge($base, ['slug' => $slug]);
        $asset_category->update($data);

        Log::info('[AssetCategory::update] updated', [
            'category_id' => $asset_category->id,
            'before'      => $before,
            'after'       => $asset_category->only(['name', 'slug', 'color', 'is_active', 'description']),
            'actor_id'    => $request->user()?->id,
        ]);

        return redirect()
            ->route('asset-categories.index')
            ->with('toast', \App\Support\Toast::success('อัปเดตหมวดหมู่แล้ว'));
    }

    public function destroy(AssetCategory $asset_category)
    {
        $categoryId   = $asset_category->id;
        $categoryName = $asset_category->name;

        $asset_category->delete();

        Log::info('[AssetCategory::destroy] deleted', [
            'category_id' => $categoryId,
            'name'        => $categoryName,
            'actor_id'    => request()->user()?->id,
        ]);

        return back()->with('toast', \App\Support\Toast::success('ลบหมวดหมู่เรียบร้อย'));
    }
}
