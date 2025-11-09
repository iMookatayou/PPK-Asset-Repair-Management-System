<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use App\Support\Toast;

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
    // legacy 'category' string removed; use category_id (FK) instead
    $categoryId = $request->integer('category_id');
        $deptId     = $request->integer('department_id');
        $location   = $request->string('location')->toString();

        $perPageInput = (int) $request->integer('per_page', 20);
        $perPage      = max(1, min($perPageInput, 100));

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
            // Use request->filled to avoid treating empty select ("") as 0 which filters out all rows
            ->when($request->filled('category_id'), fn($s) => $s->where('category_id', $categoryId))
            ->when($request->filled('department_id'), fn($s) => $s->where('department_id', $deptId))
            ->when($location !== '', fn($s) => $s->where('location', $location))
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage)
            ->withQueryString();

        $payload = $assets->toArray();
        // ถ้า client ขอ JSON ให้แนบ info toast สำหรับ UX (โหลดข้อมูลสำเร็จ)
        $payload['toast'] = Toast::info('โหลดรายการทรัพย์สินแล้ว', 1200);
        return response()->json($payload, 200, [], $this->jsonOptions($request));
    }

    public function store(Request $request)
    {
        $rules = [
            'asset_code'      => ['required','string','max:100','unique:assets,asset_code'],
            'name'            => ['required','string','max:255'],
            'type'            => ['nullable','string','max:100'],
            'category_id'     => ['nullable','integer','exists:asset_categories,id'],
            'brand'           => ['nullable','string','max:100'],
            'model'           => ['nullable','string','max:100'],
            'serial_number'   => ['nullable','string','max:100','unique:assets,serial_number'],
            'location'        => ['nullable','string','max:255'],
            'department_id'   => ['nullable','integer','exists:departments,id'],
            'purchase_date'   => ['nullable','date'],
            'warranty_expire' => ['nullable','date','after_or_equal:purchase_date'],
            'status'          => ['nullable', Rule::in(['active','in_repair','disposed'])],
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errors = $validator->errors();
            $fieldsHuman = [
                'asset_code' => 'รหัสครุภัณฑ์', 'name' => 'ชื่อครุภัณฑ์', 'serial_number' => 'Serial',
                'category_id' => 'หมวดหมู่', 'department_id' => 'หน่วยงาน', 'warranty_expire' => 'หมดประกัน'
            ];
            $bad = collect(array_keys($errors->toArray()))->map(fn($f) => $fieldsHuman[$f] ?? $f)->implode(', ');
            $msg = $bad ? ('ข้อมูลไม่ถูกต้อง: '.$bad) : 'ข้อมูลไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง';

            if (!$request->expectsJson()) {
                return redirect()->back()->withErrors($validator)->withInput()
                    ->with('toast', Toast::warning($msg, 2200));
            }
            return response()->json([
                'errors' => $errors,
                'toast'  => Toast::warning($msg, 2200),
            ], Response::HTTP_UNPROCESSABLE_ENTITY, [], $this->jsonOptions($request));
        }

        $data = $validator->validated();
        $asset = Asset::create($data)->load(['categoryRef','department']);

        return response()->json([
            'message' => 'created',
            'toast'   => Toast::success('สร้างทรัพย์สินเรียบร้อย', 1600),
            'data'    => $asset,
        ], Response::HTTP_CREATED, [], $this->jsonOptions($request));
    }

    public function show(Asset $asset)
    {
        $asset->load(['categoryRef','department']);
        return response()->json([
            'data'  => $asset,
            'toast' => Toast::info('โหลดข้อมูลทรัพย์สินแล้ว', 1000),
        ], 200, [], $this->jsonOptions(request()));
    }

    public function update(Request $request, Asset $asset)
    {
        $rules = [
            'asset_code'      => ['sometimes','string','max:100','unique:assets,asset_code,'.$asset->id],
            'name'            => ['sometimes','string','max:255'],
            'type'            => ['nullable','string','max:100'],
            'category_id'     => ['nullable','integer','exists:asset_categories,id'],
            'brand'           => ['nullable','string','max:100'],
            'model'           => ['nullable','string','max:100'],
            'serial_number'   => ['nullable','string','max:100','unique:assets,serial_number,'.$asset->id],
            'location'        => ['nullable','string','max:255'],
            'department_id'   => ['nullable','integer','exists:departments,id'],
            'purchase_date'   => ['nullable','date'],
            'warranty_expire' => ['nullable','date','after_or_equal:purchase_date'],
            'status'          => ['nullable', Rule::in(['active','in_repair','disposed'])],
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errors = $validator->errors();
            $fieldsHuman = [
                'asset_code' => 'รหัสครุภัณฑ์', 'name' => 'ชื่อครุภัณฑ์', 'serial_number' => 'Serial',
                'category_id' => 'หมวดหมู่', 'department_id' => 'หน่วยงาน', 'warranty_expire' => 'หมดประกัน'
            ];
            $bad = collect(array_keys($errors->toArray()))->map(fn($f) => $fieldsHuman[$f] ?? $f)->implode(', ');
            $msg = $bad ? ('ข้อมูลไม่ถูกต้อง: '.$bad) : 'ข้อมูลไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง';

            if (!$request->expectsJson()) {
                return redirect()->back()->withErrors($validator)->withInput()
                    ->with('toast', Toast::warning($msg, 2200));
            }
            return response()->json([
                'errors' => $errors,
                'toast'  => Toast::warning($msg, 2200),
            ], Response::HTTP_UNPROCESSABLE_ENTITY, [], $this->jsonOptions($request));
        }

        $data = $validator->validated();
        $asset->update($data);

        return response()->json([
            'message' => 'updated',
            'toast'   => Toast::success('อัปเดตทรัพย์สินเรียบร้อย', 1600),
            'data'    => $asset->load(['categoryRef','department']),
        ], Response::HTTP_OK, [], $this->jsonOptions($request));
    }

    public function destroy(Asset $asset)
    {
        $asset->delete();
        return response()->json([
            'message' => 'deleted',
            'toast'   => Toast::success('ลบทรัพย์สินแล้ว', 1600),
        ], Response::HTTP_OK, [], $this->jsonOptions(request()));
    }

    // ====================== เพจ (Blade) ======================
    public function indexPage(Request $request)
    {
        $q = trim($request->string('q')->toString());
        $status   = $request->string('status')->toString();
        $categoryId = $request->integer('category_id');
        $sortBy   = $request->string('sort_by', 'id')->toString();
        $sortDir  = strtolower($request->string('sort_dir', 'desc')->toString()) === 'asc' ? 'asc' : 'desc';

        $sortMap = [
            'id'         => 'id',
            'asset_code' => 'asset_code',
            'name'       => 'name',
            'status'     => 'status',
            // virtual key 'category' will sort by related category name
            'category'   => 'category',
        ];
        $sortCol = $sortMap[$sortBy] ?? 'id';

        $assetsQ = \App\Models\Asset::query()
            ->with(['categoryRef','department'])
            ->when($q !== '', fn($s) =>
                $s->where(function ($w) use ($q) {
                    $w->where('asset_code', 'like', "%{$q}%")
                      ->orWhere('name', 'like', "%{$q}%")
                      ->orWhere('serial_number', 'like', "%{$q}%");
                })
            )
            ->when($status !== '', fn($s) => $s->where('status', $status))
            // Avoid category_id=0 when select is "ทั้งหมด"
            ->when($request->filled('category_id'), fn($s) => $s->where('category_id', $categoryId));

        if ($sortCol === 'category') {
            $assetsQ->orderByRaw('(select name from asset_categories where asset_categories.id = assets.category_id) '.$sortDir);
        } else {
            $assetsQ->orderBy($sortCol, $sortDir);
        }

        $assets = $assetsQ->paginate(20)->withQueryString();

        $categories = \App\Models\AssetCategory::orderBy('name')->get(['id','name']);

        return view('assets.index', compact('assets','categories'));
    }

    public function createPage()
    {
        // ดึงคอลัมน์จริงเพื่อให้ accessor บนโมเดลทำงานได้ถูกต้อง (หลีกเลี่ยง alias "name" ชนกับ accessor)
        $departments = \App\Models\Department::query()
            ->select(['id','code','name_th','name_en'])
            ->orderByRaw('COALESCE(name_th, name_en, code) asc')
            ->get();

        $categories  = \App\Models\AssetCategory::orderBy('name')->get(['id','name']);

        // แจ้งเตือนหากยังไม่มีข้อมูลที่จำเป็นสำหรับการสร้างรายการ
        if ($departments->isEmpty()) {
            session()->flash('toast', Toast::info('ยังไม่มีข้อมูลหน่วยงาน กรุณา seed หรือเพิ่มใหม่ก่อน', 3200));
        }
        if ($categories->isEmpty()) {
            session()->flash('toast', Toast::info('ยังไม่มีหมวดหมู่ทรัพย์สิน กรุณา seed หรือเพิ่มใหม่ก่อน', 3200));
        }

        return view('assets.create', compact('departments','categories'));
    }

    public function storePage(Request $request)
    {
        $data = $request->validate([
            'asset_code'      => ['required','string','max:100','unique:assets,asset_code'],
            'name'            => ['required','string','max:255'],
            'type'            => ['nullable','string','max:100'],
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
        return redirect()
            ->route('assets.show', $asset)
            ->with('toast', Toast::success('สร้างทรัพย์สินเรียบร้อยแล้ว'));
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

        // ดึงคอลัมน์จริงเพื่อให้ accessor ทำงานถูกต้อง (ไม่ alias เป็น name)
        $departments = \App\Models\Department::query()
            ->select(['id','code','name_th','name_en'])
            ->orderByRaw('COALESCE(name_th, name_en, code) asc')
            ->get();

        $categories  = \App\Models\AssetCategory::orderBy('name')->get(['id','name']);

        if ($departments->isEmpty()) {
            session()->flash('toast', Toast::info('ยังไม่มีข้อมูลหน่วยงาน กรุณา seed หรือเพิ่มใหม่ก่อน', 3200));
        }
        if ($categories->isEmpty()) {
            session()->flash('toast', Toast::info('ยังไม่มีหมวดหมู่ทรัพย์สิน กรุณา seed หรือเพิ่มใหม่ก่อน', 3200));
        }

        return view('assets.edit', compact('asset','departments','categories'));
    }

    public function updatePage(Request $request, \App\Models\Asset $asset)
    {
        $data = $request->validate([
            'asset_code'      => ['sometimes','string','max:100','unique:assets,asset_code,'.$asset->id],
            'name'            => ['sometimes','string','max:255'],
            'type'            => ['nullable','string','max:100'],
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
        return redirect()
            ->route('assets.show', $asset)
            ->with('toast', Toast::success('อัปเดตรายการทรัพย์สินแล้ว'));
    }

    public function destroyPage(\App\Models\Asset $asset)
    {
        $asset->delete();
        return redirect()
            ->route('assets.index')
            ->with('toast', Toast::success('ลบทรัพย์สินเรียบร้อยแล้ว'));
    }

    // ========== Utilities (align with MaintenanceRequestController) ==========
    protected function respondWithToast(
        Request $request,
        array $toast,
        $webRedirect,
        array $jsonPayload = [],
        int $status = Response::HTTP_OK
    ) {
        if (!$request->expectsJson()) {
            return $webRedirect->with('toast', [
                'type'     => $toast['type']    ?? 'info',
                'message'  => $toast['message'] ?? '',
                'position' => $toast['position'] ?? 'tc',
                'timeout'  => $toast['timeout']  ?? 2000,
                'size'     => $toast['size']     ?? 'sm',
            ]);
        }

        $payload = array_merge($jsonPayload, [
            'toast' => [
                'type'     => $toast['type']    ?? 'info',
                'message'  => $toast['message'] ?? '',
                'position' => $toast['position'] ?? 'tc',
                'timeout'  => $toast['timeout']  ?? 2000,
                'size'     => $toast['size']     ?? 'sm',
            ],
        ]);

        return response()->json($payload, $status, [], $this->jsonOptions($request));
    }
}
