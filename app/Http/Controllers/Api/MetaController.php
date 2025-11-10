<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\User;

class MetaController extends Controller
{
    public function departments()
    {
        $rows = DB::table('departments')
            ->select('id','code','name')
            ->orderBy('name')
            ->get()
            ->map(fn($r) => [
                'id'   => $r->id,
                'code' => $r->code,
                'name' => $r->name,
            ]);
        return response()->json(['data' => $rows]);
    }

    public function categories()
    {
        $rows = DB::table('asset_categories')
            ->select('id','name')
            ->orderBy('name')
            ->get()
            ->map(fn($r) => [
                'id'   => $r->id,
                'name' => $r->name,
            ]);
        return response()->json(['data' => $rows]);
    }

    public function users(Request $r)
    {
        $role = $r->query('role');
        $q = User::query()->select('id','name','role','department');
        if ($role) {
            $q->where('role', $role);
        }
        $rows = $q->orderBy('name')->limit(200)->get()->map(fn(User $u) => [
            'id'         => $u->id,
            'name'       => $u->name,
            'role'       => $u->role,
            'department' => $u->department,
        ]);
        return response()->json(['data' => $rows]);
    }
}
