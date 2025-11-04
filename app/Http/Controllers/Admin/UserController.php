<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth; // <<< ใช้ Auth::id()

class UserController extends Controller
{
    /**
     * Roles ที่รองรับในระบบตอนนี้
     */
    private const ROLES = ['admin', 'technician', 'staff'];

    // ลบ __construct() ที่เรียก $this->middleware(...) ออก
    // เราใส่ can:manage-users ไว้ที่ routes แล้ว

    /**
     * GET /admin/users
     */
    public function index(Request $request)
    {
        $q = User::query();

        if ($search = trim((string) $request->get('s', ''))) {
            $needle = mb_strtolower($search);
            $q->where(function ($qq) use ($needle) {
                $qq->whereRaw('LOWER(name) LIKE ?', ["%{$needle}%"])
                   ->orWhereRaw('LOWER(email) LIKE ?', ["%{$needle}%"])
                   ->orWhereRaw('LOWER(COALESCE(department, \'\')) LIKE ?', ["%{$needle}%"]);
            });
        }

        if ($role = $request->get('role')) {
            $q->where('role', $role);
        }

        if ($dept = $request->get('department')) {
            $q->where('department', $dept);
        }

        $users = $q->orderByDesc('id')->paginate(15)->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => self::ROLES,
            'filters' => [
                's' => $request->get('s'),
                'role' => $request->get('role'),
                'department' => $request->get('department'),
            ],
        ]);
    }

    /**
     * GET /admin/users/create
     */
    public function create()
    {
        return view('admin.users.create', [
            'roles' => self::ROLES,
        ]);
    }

    /**
     * POST /admin/users
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'   => ['required', 'confirmed', 'min:8'],
            'department' => ['nullable', 'string', 'max:100'],
            'role'       => ['required', Rule::in(self::ROLES)],
        ]);

        $data['password'] = Hash::make($data['password']);

        $user = DB::transaction(fn () => User::create($data));

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('status', 'User created successfully.');
    }

    /**
     * GET /admin/users/{user}/edit
     */
    public function edit(User $user)
    {
        return view('admin.users.edit', [
            'user'  => $user,
            'roles' => self::ROLES,
        ]);
    }

    /**
     * PUT /admin/users/{user}
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->getKey())],
            'password'   => ['nullable', 'confirmed', 'min:8'],
            'department' => ['nullable', 'string', 'max:100'],
            'role'       => ['required', Rule::in(self::ROLES)],
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        DB::transaction(fn () => $user->update($data));

        return back()->with('status', 'User updated successfully.');
    }

    /**
     * DELETE /admin/users/{user}
     */
    public function destroy(User $user)
    {
        if (Auth::id() === $user->id) { // <<< ใช้ Auth::id()
            return back()->withErrors(['delete' => 'You cannot delete yourself.']);
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User deleted.');
    }

    /**
     * POST /admin/users/bulk
     * action: change_role | delete
     * ids[]: [1,2,3]
     * role: admin/technician/staff (เมื่อ action = change_role)
     */
    public function bulk(Request $request)
    {
        $validated = $request->validate([
            'action' => ['required', 'string'],
            'ids'    => ['required', 'array', 'min:1'],
            'ids.*'  => ['integer', 'exists:users,id'],
            'role'   => ['nullable', Rule::in(self::ROLES)],
        ]);

        $ids = collect($validated['ids'])->unique()->values();

        // ห้ามลบ/แก้ role ของตัวเองใน bulk
        $ids = $ids->reject(fn ($id) => (int)$id === (int) Auth::id()); // <<< ใช้ Auth::id()

        if ($ids->isEmpty()) {
            return back()->withErrors(['bulk' => 'No valid targets (self-action is not allowed).']);
        }

        DB::transaction(function () use ($validated, $ids) {
            $query = User::whereIn('id', $ids);

            switch ($validated['action']) {
                case 'change_role':
                    $role = $validated['role'] ?? null;
                    if (!$role) {
                        abort(422, 'Role is required for change_role action.');
                    }
                    $query->update(['role' => $role]);
                    break;

                case 'delete':
                    $query->delete();
                    break;

                default:
                    abort(422, 'Unknown bulk action.');
            }
        });

        return back()->with('status', 'Bulk action completed.');
    }
}
