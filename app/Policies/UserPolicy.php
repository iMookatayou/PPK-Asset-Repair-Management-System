<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Admin override ทุกอย่าง
     */
    public function before(User $auth): bool|null
    {
        if ($auth->role === 'admin') {
            return true;
        }
        return null; // ไปเช็ค method ปกติถ้าไม่ใช่ admin
    }

    public function viewAny(User $auth): bool
    {
        return false; // non-admin denied
    }

    public function view(User $auth, User $user): bool
    {
        return false;
    }

    public function create(User $auth): bool
    {
        return false;
    }

    public function update(User $auth, User $user): bool
    {
        return false;
    }

    public function delete(User $auth, User $user): bool
    {
        // block self-delete แม้เป็น admin
        return $auth->id !== $user->id;
    }

    public function restore(User $auth, User $user): bool
    {
        return true;
    }

    public function forceDelete(User $auth, User $user): bool
    {
        // แนะนำให้ admin ก็ลบถาวรตัวเองไม่ได้
        return $auth->id !== $user->id;
    }
}
