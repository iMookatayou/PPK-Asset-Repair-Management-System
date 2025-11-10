<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Policies\UserPolicy;
use App\Models\MaintenanceRequest as MR;
use App\Policies\MaintenanceRequestPolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
        MR::class   => MaintenanceRequestPolicy::class,
    ];

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

        Gate::define('view-my-jobs', function (User $user): bool {
            return in_array($user->role, ['admin', 'technician'], true);
        });
    }
}
