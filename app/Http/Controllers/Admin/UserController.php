<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;

class UserController extends Controller
{
    public function __construct()
    {
        // ให้เข้าหน้านี้ได้เฉพาะคนที่ล็อกอินแล้ว
        // ถ้าจะเข้มขึ้น ค่อยเพิ่ม middleware ตรวจ role ทีหลังได้
        $this->middleware('auth');
    }

    /**
     * แสดงรายการผู้ใช้ทั้งหมด + filter / search
     */
    public function index(Request $request)
    {
        // list ของ code role ที่มีในระบบ (มาจากตาราง roles ผ่าน User::availableRoles())
        $roleCodes   = User::availableRoles();
        $roleLabels  = User::roleLabels(); // ['admin' => 'ผู้ดูแลระบบ', ...]

        $q = User::query()
            ->with([
                // departments: ไม่มีคอลัมน์ name แล้ว → เลือก name_th, name_en พอ
                'departmentRef' => function ($qq) {
                    $qq->select([
                        'id',
                        'code',
                        'name_th',
                        'name_en',
                    ]);
                },
                // preload roleRef ด้วย เผื่ออยากใช้ใน Blade
                'roleRef' => function ($qq) {
                    $qq->select([
                        'id',
                        'code',
                        'name_th',
                        'name_en',
                    ]);
                },
            ]);

        // ค้นหาจากชื่อ, email, department (ตัวพิมพ์เล็ก-ใหญ่ไม่สน)
        $search = trim((string) $request->get('s', ''));
        if ($search !== '') {
            $needle = mb_strtolower($search);
            $q->where(function ($qq) use ($needle) {
                $qq->whereRaw('LOWER(name) LIKE ?', ["%{$needle}%"])
                   ->orWhereRaw('LOWER(email) LIKE ?', ["%{$needle}%"])
                   ->orWhereRaw('LOWER(COALESCE(department, \'\')) LIKE ?', ["%{$needle}%"]);
            });
        }

        // filter ตาม role
        $role = $request->get('role');
        if ($role !== null && $role !== '') {
            $q->where('role', $role);
        }

        // filter ตาม department (เก็บเป็น code)
        $dep = $request->get('department');
        if ($dep !== null && $dep !== '') {
            $q->where('department', $dep);
        }

        $list = $q
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'list'       => $list,
            'roles'      => $roleCodes,   // ใช้ใน filter / select ก็ได้
            'roleLabels' => $roleLabels,  // ใช้แสดง label ไทย ๆ
            'filters'    => [
                's'          => $search,
                'role'       => $role,
                'department' => $dep,
            ],
        ]);
    }

    /**
     * ฟอร์มสร้างผู้ใช้ใหม่
     */
    public function create()
    {
        $roleCodes   = User::availableRoles();
        $roleLabels  = User::roleLabels();

        // ✅ ดึง department ทั้งหมดมาให้เลือกตอน create เหมือนตอน edit
        $departments = \App\Models\Department::orderBy('code')->get([
            'id',
            'code',
            'name_th',
            'name_en',
        ]);

        return view('admin.users.create', [
            'roles'       => $roleCodes,
            'roleLabels'  => $roleLabels,
            'departments' => $departments,   // 👈 ส่งเข้า view แล้ว
        ]);
    }

    /**
     * บันทึกผู้ใช้ใหม่
     */
    public function store(Request $request)
    {
        $availableRoles = User::availableRoles();

        $validator = Validator::make(
            $request->all(),
            [
                'name'       => ['required', 'string', 'max:255'],
                'email'      => [
                    'required',
                    'email', // ไม่เช็ค DNS เพื่อไม่ให้ error แปลก
                    'max:255',
                    'unique:users,email',
                ],
                'password'   => ['required', 'string', 'min:8', 'confirmed'],
                'role'       => [
                    'required',
                    'string',
                    Rule::in($availableRoles),
                ],
                'department' => [
                    'nullable',
                    'string',
                    'max:255',
                    // ถ้าต้องการให้ตรงกับ departments.code เสมอ
                    Rule::exists('departments', 'code'),
                ],
            ],
            [
                'name.required'      => 'กรุณากรอกชื่อผู้ใช้',
                'name.max'           => 'ชื่อผู้ใช้ต้องไม่เกิน :max ตัวอักษร',
                'email.required'     => 'กรุณากรอกอีเมล',
                'email.email'        => 'รูปแบบอีเมลไม่ถูกต้อง',
                'email.max'          => 'อีเมลต้องไม่เกิน :max ตัวอักษร',
                'email.unique'       => 'อีเมลนี้ถูกใช้ไปแล้ว',
                'password.required'  => 'กรุณากรอกรหัสผ่าน',
                'password.min'       => 'รหัสผ่านต้องมีอย่างน้อย :min ตัวอักษร',
                'password.confirmed' => 'รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน',
                'role.required'      => 'กรุณาเลือกบทบาทผู้ใช้',
                'role.in'            => 'บทบาทที่เลือกไม่ถูกต้อง',
                'department.max'     => 'ชื่อหน่วยงานต้องไม่เกิน :max ตัวอักษร',
                'department.exists'  => 'หน่วยงานที่เลือกไม่ถูกต้อง',
            ]
        );

        if ($validator->fails()) {
            return back()
                ->withInput()
                ->withErrors($validator)
                ->with('toast', [
                    'type'     => 'error',
                    'message'  => 'บันทึกผู้ใช้ไม่สำเร็จ',
                    'position' => 'br',
                    'timeout'  => 3200,
                    'details'  => $validator->errors()->first() ?? null,
                ]);
        }

        $data = $validator->validated();

        try {
            DB::beginTransaction();

            $user = new User();
            $user->name       = $data['name'];
            $user->email      = $data['email'];
            $user->password   = Hash::make($data['password']);
            $user->role       = $data['role'];
            $user->department = $data['department'] ?? null;

            if (Schema::hasColumn('users', 'created_by')) {
                $user->created_by = Auth::id();
            }

            $user->save();

            DB::commit();

            return redirect()
                ->route('admin.users.index')
                ->with('toast', [
                    'type'     => 'success',
                    'message'  => 'สร้างผู้ใช้ใหม่เรียบร้อยแล้ว',
                    'position' => 'br',
                    'timeout'  => 2800,
                ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()
                ->withInput()
                ->with('toast', [
                    'type'     => 'error',
                    'message'  => 'เกิดข้อผิดพลาดระหว่างบันทึกข้อมูลผู้ใช้',
                    'position' => 'br',
                    'timeout'  => 4000,
                ]);
        }
    }

    /**
     * ฟอร์มแก้ไขผู้ใช้
     */
    public function edit(User $user)
    {
        $roleCodes   = User::availableRoles();   // ['admin','supervisor',...]
        $roleLabels  = User::roleLabels();       // ['admin' => 'ผู้ดูแลระบบ', ...]

        // ดึง department ทั้งหมดมาเป็นรายการให้เลือก (ถ้ามี)
        $departments = \App\Models\Department::orderBy('code')->get([
            'id',
            'code',
            'name_th',
            'name_en',
        ]);

        return view('admin.users.edit', [
            'user'        => $user,
            'roles'       => $roleCodes,
            'roleLabels'  => $roleLabels,
            'departments' => $departments,
        ]);
    }

    /**
     * อัพเดตข้อมูลผู้ใช้
     */
    public function update(Request $request, User $user)
    {
        $availableRoles = User::availableRoles();

        $validator = Validator::make(
            $request->all(),
            [
                'name'       => ['required', 'string', 'max:255'],
                'email'      => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($user->id),
                ],
                'password'   => ['nullable', 'string', 'min:8', 'confirmed'],
                'role'       => [
                    'required',
                    'string',
                    Rule::in($availableRoles),
                ],
                'department' => [
                    'nullable',
                    'string',
                    'max:255',
                    Rule::exists('departments', 'code'),
                ],
            ],
            [
                'name.required'      => 'กรุณากรอกชื่อผู้ใช้',
                'name.max'           => 'ชื่อผู้ใช้ต้องไม่เกิน :max ตัวอักษร',
                'email.required'     => 'กรุณากรอกอีเมล',
                'email.email'        => 'รูปแบบอีเมลไม่ถูกต้อง',
                'email.max'          => 'อีเมลต้องไม่เกิน :max ตัวอักษร',
                'email.unique'       => 'อีเมลนี้ถูกใช้ไปแล้ว',
                'password.min'       => 'รหัสผ่านต้องมีอย่างน้อย :min ตัวอักษร',
                'password.confirmed' => 'รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน',
                'role.required'      => 'กรุณาเลือกบทบาทผู้ใช้',
                'role.in'            => 'บทบาทที่เลือกไม่ถูกต้อง',
                'department.max'     => 'ชื่อหน่วยงานต้องไม่เกิน :max ตัวอักษร',
                'department.exists'  => 'หน่วยงานที่เลือกไม่ถูกต้อง',
            ]
        );

        if ($validator->fails()) {
            return back()
                ->withInput()
                ->withErrors($validator)
                ->with('toast', [
                    'type'     => 'error',
                    'message'  => 'อัพเดตข้อมูลผู้ใช้ไม่สำเร็จ',
                    'position' => 'br',
                    'timeout'  => 3200,
                    'details'  => $validator->errors()->first() ?? null,
                ]);
        }

        $data = $validator->validated();

        try {
            DB::beginTransaction();

            $user->name       = $data['name'];
            $user->email      = $data['email'];
            $user->role       = $data['role'];
            $user->department = $data['department'] ?? null;

            if (!empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }

            $user->save();

            DB::commit();

            return redirect()
                ->route('admin.users.index')
                ->with('toast', [
                    'type'     => 'success',
                    'message'  => 'อัพเดตข้อมูลผู้ใช้เรียบร้อยแล้ว',
                    'position' => 'br',
                    'timeout'  => 2800,
                ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()
                ->withInput()
                ->with('toast', [
                    'type'     => 'error',
                    'message'  => 'เกิดข้อผิดพลาดระหว่างอัพเดตข้อมูลผู้ใช้',
                    'position' => 'br',
                    'timeout'  => 4000,
                ]);
        }
    }

    /**
     * ลบผู้ใช้
     */
    public function destroy(User $user)
    {
        // กันลบตัวเอง
        if ($user->id === Auth::id()) {
            return back()->with('toast', [
                'type'     => 'error',
                'message'  => 'ไม่สามารถลบบัญชีของตัวเองได้',
                'position' => 'br',
                'timeout'  => 3200,
            ]);
        }

        try {
            DB::beginTransaction();

            $user->delete();

            DB::commit();

            return redirect()
                ->route('admin.users.index')
                ->with('toast', [
                    'type'     => 'success',
                    'message'  => 'ลบผู้ใช้เรียบร้อยแล้ว',
                    'position' => 'br',
                    'timeout'  => 2800,
                ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()->with('toast', [
                'type'     => 'error',
                'message'  => 'เกิดข้อผิดพลาดระหว่างลบผู้ใช้',
                'position' => 'br',
                'timeout'  => 4000,
            ]);
        }
    }
}
