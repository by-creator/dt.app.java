<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Throwable;
use Illuminate\Support\Facades\URL;

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
        $this->configureDefaults();
        Schema::defaultStringLength(191);
        $this->ensureAdminUserExists();
        $this->ensureBusinessRolesExist();

        $this->configureDefaults();
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    protected function ensureAdminUserExists(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        if (($this->currentConsoleCommand() === 'create:admin-user')) {
            return;
        }

        app()->booted(function (): void {
            try {
                if (! Schema::hasTable('roles') || ! Schema::hasTable('users')) {
                    return;
                }

                Artisan::call('create:admin-user');
            } catch (Throwable) {
                // Ignore bootstrap-time failures until the database is ready.
            }
        });
    }

    protected function ensureBusinessRolesExist(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        if (($this->currentConsoleCommand() === 'roles:ensure-business')) {
            return;
        }

        app()->booted(function (): void {
            try {
                if (! Schema::hasTable('roles')) {
                    return;
                }

                Artisan::call('roles:ensure-business');
            } catch (Throwable) {
                // Ignore bootstrap-time failures until the database is ready.
            }
        });
    }

    protected function currentConsoleCommand(): ?string
    {
        if (! app()->runningInConsole()) {
            return null;
        }

        return $_SERVER['argv'][1] ?? null;
    }
}
