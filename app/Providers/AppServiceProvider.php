<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {   

        Gate::define('tech-only', fn($user) => in_array($user->role, ['technician','admin'], true));
        Gate::define('admin-only', fn($user) => $user->role === 'admin');
        // ลิงก์รีเซ็ตรหัสผ่าน ไปหน้า Frontend
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')
                . "/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        // Pretty JSON เฉพาะตอน local (อ่านง่าย + รองรับภาษาไทย)
        if (app()->isLocal()) {
            Response::macro('prettyJson', function ($value, int $status = 200, array $headers = []) {
                return response()->json(
                    $value,
                    $status,
                    $headers,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );
            });
        }
    }
}
