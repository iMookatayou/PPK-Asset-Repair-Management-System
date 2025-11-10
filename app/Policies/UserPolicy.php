<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function before(User $auth): bool|null
    {
        if ($auth->role === 'admin') {
            return true;
        }
        return null;
    }

    public function viewAny(User $auth): bool
    {
        return false;
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
        return $auth->id !== $user->id;
    }

    public function restore(User $auth, User $user): bool
    {
        return true;
    }

    public function forceDelete(User $auth, User $user): bool
    {
        return $auth->id !== $user->id;
    }
}
