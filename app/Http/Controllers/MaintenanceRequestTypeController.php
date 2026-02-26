<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequestType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Support\Toast;

class MaintenanceRequestTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        // กัน Member ตั้งแต่ระดับ Constructor (ถ้าเป็น member ให้ดีดออกทันที)
        $this->middleware(function ($request, $next) {
            if (auth()->user()->role === 'member') {
                abort(403, 'คุณไม่มีสิทธิ์เข้าถึงส่วนการตั้งค่าประเภทงาน');
            }
            return $next($request);
        });

        $this->middleware('can:viewAny,' . MaintenanceRequestType::class);
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', MaintenanceRequestType::class);

        $q = MaintenanceRequestType::query();

        if ($request->filled('active')) {
            $q->where('is_active', (bool) $request->boolean('active'));
        }

        if ($request->filled('search')) {
            $s = trim((string) $request->string('search'));
            $q->where(function ($w) use ($s) {
                $w->where('name', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%")
                    ->orWhere('default_department_code', 'like', "%{$s}%")
                    ->orWhere('default_role_code', 'like', "%{$s}%");
            });
        }

        $types = $q->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        if (!$request->expectsJson()) {
            return view('settings.maintenance-types.index', [
                'types'  => $types,
                'active' => $request->filled('active') ? (int) $request->boolean('active') : null,
                'search' => trim((string) $request->string('search')),
            ]);
        }

        return response()->json(['data' => $types]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', MaintenanceRequestType::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:maintenance_request_types,name'],
            'description' => ['nullable', 'string'],
            'default_department_code' => ['nullable', 'string', 'max:100'],
            'default_role_code' => ['nullable', 'string', 'max:50'],
            'default_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $type = MaintenanceRequestType::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'default_department_code' => $data['default_department_code'] ?? null,
            'default_role_code' => $data['default_role_code'] ?? null,
            'default_user_id' => $data['default_user_id'] ?? null,
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
            'sort_order' => array_key_exists('sort_order', $data) ? (int) $data['sort_order'] : 0,
        ]);

        if (!$request->expectsJson()) {
            return redirect()
                ->route('settings.maintenance-types.index')
                ->with('toast', Toast::success('เพิ่มประเภทงานเรียบร้อยแล้ว', 1800));
        }

        return response()->json([
            'message' => 'created',
            'data' => $type,
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $type = MaintenanceRequestType::findOrFail($id);
        $this->authorize('update', $type);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('maintenance_request_types', 'name')->ignore($type->id)],
            'description' => ['nullable', 'string'],
            'default_department_code' => ['nullable', 'string', 'max:100'],
            'default_role_code' => ['nullable', 'string', 'max:50'],
            'default_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $type->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'default_department_code' => $data['default_department_code'] ?? null,
            'default_role_code' => $data['default_role_code'] ?? null,
            'default_user_id' => $data['default_user_id'] ?? null,
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : (bool) $type->is_active,
            'sort_order' => array_key_exists('sort_order', $data) ? (int) $data['sort_order'] : (int) $type->sort_order,
        ]);

        if (!$request->expectsJson()) {
            return redirect()
                ->route('settings.maintenance-types.index')
                ->with('toast', Toast::success('บันทึกการแก้ไขเรียบร้อยแล้ว', 1800));
        }

        return response()->json([
            'message' => 'updated',
            'data' => $type->fresh(),
        ]);
    }

    public function destroy(Request $request, int $id)
    {
        $type = MaintenanceRequestType::findOrFail($id);
        $this->authorize('delete', $type);

        $type->update(['is_active' => false]);

        if (!$request->expectsJson()) {
            return redirect()
                ->route('settings.maintenance-types.index')
                ->with('toast', Toast::success('ปิดใช้งานประเภทเรียบร้อยแล้ว', 1800));
        }

        return response()->json([
            'message' => 'disabled',
            'data' => $type->fresh(),
        ]);
    }
}
