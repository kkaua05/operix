<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        // Platform-wide bypass: a SUPER_ADMIN is not a tenant-scoped role
        // (spec §10 — "Controle completo da plataforma"), so it's a flag
        // on the user rather than a spatie/permission role.
        Gate::before(function (User $user, string $ability) {
            return $user->is_super_admin ? true : null;
        });

        // Event → listener wiring for §37 (WorkOrderAssigned, WorkOrderStatusChanged,
        // SlaBreached) is picked up automatically by Laravel's event discovery —
        // every class in app/Listeners with a typed handle(SomeEvent $event)
        // method is wired without any registration here. Registering it again
        // explicitly would double-fire the listener.
    }
}
