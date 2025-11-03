<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;

class AssetController extends Controller
{
    private function jsonOptions(Request $request): int
    {
        return JSON_UNESCAPED_UNICODE
             | JSON_UNESCAPED_SLASHES
             | ($request->boolean('pretty') ? JSON_PRETTY_PRINT : 0);
    }

    public function index(Request $request)
    {
        $q          = trim($request->string('q')->toString());
        $status     = $request->string('status')->toString();
        $type       = $request->string('type')->toString();
        $category   = $request->string('category')->toString();    // legacy string
        $categoryId = $request->integer('category_id');            // FK
        $deptId     = $request->integer('department_id');
        $location   = $request->string('location')->toString();

        $perPageInput = (int) $request->integer('per_page', 20);
        $perPage      = max(1, min($perPageInput, 100));

        // map คอลัมน์ให้ชัดเจน
        $sortMap = [
            'id'              => 'id',
            'asset_code'      => 'asset_code',
            'name'            => 'name',
            'purchase_date'   => 'purchase_date',
            'warranty_expire' => 'warranty_expire',
            'status'          => 'status',
            'created_at'      => 'created_at',
        ];
        $sortByReq = $request->string('sort_by', 'id')->toString();
        $sortBy    = $sortMap[$sortByReq] ?? 'id';
        $sortDir   = strtolower($request->string('sort_dir', 'desc')->toString()) === 'asc' ? 'asc' : 'desc';

        $assets = Asset::query()
            ->with(['categoryRef','department'])
            ->when($q !== '', fn($s) =>
                $s->where(function ($w) use ($q) {
                    $w->where('asset_code', 'like', "%{$q}%")
                      ->orWhere('name', 'like', "%{$q}%")
                      ->orWhere('serial_number', 'like', "%{$q}%");
                })
            )
            ->when($status !== '', fn($s) => $s->where('status', $status))
            ->when($type !== '', fn($s) => $s->where('type', $type))
            ->when($category !== '', fn($s) => $s->where('category', $category))
            ->when(filled($categoryId), fn($s) => $s->where('category_id', $categoryId))
            ->when(filled($deptId), fn($s) => $s->where('department_id', $deptId))
            ->when($location !== '', fn($s) => $s->where('location', $location))
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage)
            ->withQueryString(); // คง query เดิมในลิงก์ถัดไป

        return response()->json($assets, 200, [], $this->jsonOptions($request));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'asset_code'      => ['required','string','max:100','unique:assets,asset_code'],
            'name'            => ['required','string','max:255'],
            'type'            => ['nullable','string','max:100'],
            'category'        => ['nullable','string','max:100'], // legacy
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

        $asset = Asset::create($data)->load(['categoryRef','department']);

        return response()->json([
            'message' => 'created',
            'data'    => $asset,
        ], 201, [], $this->jsonOptions($request));
    }

    public function show(Asset $asset)
    {
        $asset->load(['categoryRef','department']);
        return response()->json($asset, 200, [], $this->jsonOptions(request()));
    }

    public function update(Request $request, Asset $asset)
    {
        $data = $request->validate([
            'asset_code'      => ['sometimes','string','max:100','unique:assets,asset_code,'.$asset->id],
            'name'            => ['sometimes','string','max:255'],
            'type'            => ['nullable','string','max:100'],
            'category'        => ['nullable','string','max:100'], // legacy
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

        return response()->json([
            'message' => 'updated',
            'data'    => $asset->load(['categoryRef','department']),
        ], 200, [], $this->jsonOptions($request));
    }

    public function destroy(Asset $asset)
    {
        $asset->delete();
        return response()->json(['message' => 'deleted'], 200, [], $this->jsonOptions(request()));
    }

    // ====================== เพจ (Blade) ======================
    public function indexPage(Request $request)
    {
        $q = trim($request->string('q')->toString());
        $status   = $request->string('status')->toString();
        $category = $request->string('category')->toString();
        $sortBy   = $request->string('sort_by', 'id')->toString();
        $sortDir  = strtolower($request->string('sort_dir', 'desc')->toString()) === 'asc' ? 'asc' : 'desc';

        $sortMap = [
            'id'         => 'id',
            'asset_code' => 'asset_code',
            'name'       => 'name',
            'category'   => 'category',
            'status'     => 'status',
        ];
        $sortCol = $sortMap[$sortBy] ?? 'id';

        $assets = \App\Models\Asset::query()
            ->with(['categoryRef','department'])
            ->when($q !== '', fn($s) =>
                $s->where(function ($w) use ($q) {
                    $w->where('asset_code', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%")
                    ->orWhere('serial_number', 'like', "%{$q}%");
                })
            )
            ->when($status !== '', fn($s) => $s->where('status', $status))
            ->when($category !== '', fn($s) => $s->where('category', $category))
            ->orderBy($sortCol, $sortDir)
            ->paginate(20)
            ->withQueryString();

        return view('assets.index', compact('assets'));
    }

    public function createPage()
    {
        $departments = \App\Models\Department::orderBy('name')->get(['id','name']);
        $categories  = \App\Models\AssetCategory::orderBy('name')->get(['id','name']);
        return view('assets.create', compact('departments','categories'));
    }

    public function storePage(Request $request)
    {
        $data = $request->validate([
            'asset_code'      => ['required','string','max:100','unique:assets,asset_code'],
            'name'            => ['required','string','max:255'],
            'type'            => ['nullable','string','max:100'],
            'category'        => ['nullable','string','max:100'],
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

        $asset = \App\Models\Asset::create($data);
        return redirect()->route('assets.show', $asset)->with('status','Asset created.');
    }

    public function showPage(\App\Models\Asset $asset)
    {
        $asset->load(['categoryRef','department'])
            ->loadCount([
                'maintenanceRequests as maintenance_requests_count',
                'requestAttachments as attachments_count',
            ]);

        $logs = $asset->requestLogs()
            ->select('maintenance_logs.*')
            ->orderBy(Schema::hasColumn('maintenance_logs','created_at') ? 'maintenance_logs.created_at' : 'maintenance_logs.id', 'desc')
            ->limit(10)
            ->get();

        $attQuery = $asset->requestAttachments()->select('attachments.*');

        $attQuery->orderBy(
            Schema::hasColumn('attachments', 'created_at') ? 'attachments.created_at' : 'attachments.id',
            'desc'
        );

        $attachments = $attQuery->get();

        return view('assets.show', compact('asset','logs','attachments'));
    }

    public function editPage(\App\Models\Asset $asset)
    {
        $asset->load(['categoryRef','department']);
        $departments = \App\Models\Department::orderBy('name')->get(['id','name']);
        $categories  = \App\Models\AssetCategory::orderBy('name')->get(['id','name']);
        return view('assets.edit', compact('asset','departments','categories'));
    }

    public function updatePage(Request $request, \App\Models\Asset $asset)
    {
        $data = $request->validate([
            'asset_code'      => ['sometimes','string','max:100','unique:assets,asset_code,'.$asset->id],
            'name'            => ['sometimes','string','max:255'],
            'type'            => ['nullable','string','max:100'],
            'category'        => ['nullable','string','max:100'],
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
        return redirect()->route('assets.show', $asset)->with('status','Asset updated.');
    }

    public function destroyPage(\App\Models\Asset $asset)
    {
        $asset->delete();
        return redirect()->route('assets.index')->with('status','Asset deleted.');
    }
}
