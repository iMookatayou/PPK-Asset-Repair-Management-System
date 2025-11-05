<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Policies\UserPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Policy mapping
     */
    protected $policies = [
        User::class => UserPolicy::class,
    ];

    /**
     * Register auth services
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('manage-users', function (User $user): bool {
            return in_array($user->role, [
                'admin', 
                'technician',
            ], true);
        });

        Gate::define('view-repair-dashboard', function (User $user): bool {
            return in_array($user->role, ['admin', 'technician'], true);
        });
    }
}
