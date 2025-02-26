<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap application services.
     */
    public function boot(): void
    {
        // API ve Web route'larını tanımlıyoruz
        $this->app->booted(function (Application $app) {
            $app->configureRoutes(function () {
                Route::prefix('api')
                    ->middleware('api')
                    ->middleware(['api', 'is_admin'])
                    ->group(base_path('routes/api.php'));

                Route::middleware('web')
                    ->group(base_path('routes/web.php'));
            });
        });

        // Eğer kullanıcı oturum açmamışsa yönlendirilecek login route'u
        Route::get('/login', function () {
            return response()->json(['error' => 'Unauthorized'], 401);
        })->name('login');
    }
}
