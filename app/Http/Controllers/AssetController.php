<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $q            = trim($request->string('q')->toString());
        $status       = $request->string('status')->toString();
        $type         = $request->string('type')->toString();
        $category     = $request->string('category')->toString();        // legacy string
        $categoryId   = $request->integer('category_id');                // FK
        $deptId       = $request->integer('department_id');
        $location     = $request->string('location')->toString();

        $perPageInput = (int) $request->integer('per_page', 20);
        $perPage      = max(1, min($perPageInput, 100));

        $sortBy  = $request->string('sort_by', 'id')->toString();
        $sortDir = strtolower($request->string('sort_dir', 'desc')->toString()) === 'asc' ? 'asc' : 'desc';

        $allowedSort = ['id','asset_code','name','purchase_date','warranty_expire','status','created_at'];
        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'id';
        }

        $assets = Asset::query()
            ->with(['categoryRef','department'])
            ->when($q, fn($s) =>
                $s->where(function ($w) use ($q) {
                    $w->where('asset_code', 'like', "%{$q}%")
                      ->orWhere('name', 'like', "%{$q}%")
                      ->orWhere('serial_number', 'like', "%{$q}%");
                })
            )
            ->when($status, fn($s) => $s->where('status', $status))
            ->when($type, fn($s) => $s->where('type', $type))
            ->when($category, fn($s) => $s->where('category', $category))
            ->when($categoryId, fn($s) => $s->where('category_id', $categoryId))
            ->when($deptId, fn($s) => $s->where('department_id', $deptId))
            ->when($location, fn($s) => $s->where('location', $location))
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);

        $options = JSON_UNESCAPED_UNICODE | ($request->boolean('pretty') ? JSON_PRETTY_PRINT : 0);
        return response()->json($assets, 200, [], $options);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'asset_code'      => ['required','string','max:100','unique:assets,asset_code'],
            'name'            => ['required','string','max:255'],
            'type'            => ['nullable','string','max:100'],
            'category'        => ['nullable','string','max:100'],               // legacy string
            'category_id'     => ['nullable','integer','exists:asset_categories,id'],
            'brand'           => ['nullable','string','max:100'],
            'model'           => ['nullable','string','max:100'],
            'serial_number'   => ['nullable','string','max:100','unique:assets,serial_number'],
            'location'        => ['nullable','string','max:255'],
            'department_id'   => ['nullable','integer','exists:departments,id'],
            'purchase_date'   => ['nullable','date'],
            'warranty_expire' => ['nullable','date','after_or_equal:purchase_date'],
            'status'          => ['nullable', Rule::in(['active','in_repair','disposed'])],
        ]);

        $asset = Asset::create($data);

        $options = JSON_UNESCAPED_UNICODE | ($request->boolean('pretty') ? JSON_PRETTY_PRINT : 0);
        return response()->json([
            'message' => 'created',
            'data'    => $asset->load(['categoryRef','department']),
        ], 201, [], $options);
    }

    public function show(Asset $asset)
    {
        $asset->load(['categoryRef','department']);
        $options = JSON_UNESCAPED_UNICODE | (request()->boolean('pretty') ? JSON_PRETTY_PRINT : 0);
        return response()->json($asset, 200, [], $options);
    }

    public function update(Request $request, Asset $asset)
    {
        $data = $request->validate([
            'asset_code'      => ['sometimes','string','max:100','unique:assets,asset_code,'.$asset->id],
            'name'            => ['sometimes','string','max:255'],
            'type'            => ['nullable','string','max:100'],
            'category'        => ['nullable','string','max:100'],               // legacy string
            'category_id'     => ['nullable','integer','exists:asset_categories,id'],
            'brand'           => ['nullable','string','max:100'],
            'model'           => ['nullable','string','max:100'],
            'serial_number'   => ['nullable','string','max:100','unique:assets,serial_number,'.$asset->id],
            'location'        => ['nullable','string','max:255'],
            'department_id'   => ['nullable','integer','exists:departments,id'],
            'purchase_date'   => ['nullable','date'],
            'warranty_expire' => ['nullable','date','after_or_equal:purchase_date'],
            'status'          => ['nullable', Rule::in(['active','in_repair','disposed'])],
        ]);

        $asset->update($data);

        $options = JSON_UNESCAPED_UNICODE | ($request->boolean('pretty') ? JSON_PRETTY_PRINT : 0);
        return response()->json([
            'message' => 'updated',
            'data'    => $asset->load(['categoryRef','department']),
        ], 200, [], $options);
    }

    public function destroy(Asset $asset)
    {
        $asset->delete();
        $options = JSON_UNESCAPED_UNICODE | (request()->boolean('pretty') ? JSON_PRETTY_PRINT : 0);
        return response()->json(['message' => 'deleted'], 200, [], $options);
    }
}
