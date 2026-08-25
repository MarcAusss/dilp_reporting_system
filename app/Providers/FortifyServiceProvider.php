<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->instance(
            LogoutResponse::class,
            new class implements LogoutResponse {
            public function toResponse($request)
            {
                return redirect()->route('login');
            }
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureViews();
        $this->configureAuthentication();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn() => view('auth.login'));
    }

    /**
     * Configure authentication behavior.
     */
    private function configureAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request): ?User {
            $email = Str::lower(
                trim((string) $request->input('email'))
            );

            $user = User::query()
                ->where('email', $email)
                ->first();

            if (!$user) {
                return null;
            }

            if (!$user->is_active) {
                return null;
            }

            if (
                !Hash::check(
                    (string) $request->input('password'),
                    $user->password
                )
            ) {
                return null;
            }

            return $user;
        });
    }

    /**
     * Configure login rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $email = Str::transliterate(
                Str::lower(
                    (string) $request->input(Fortify::username())
                )
            );

            return Limit::perMinute(5)
                ->by($email . '|' . $request->ip());
        });
    }
}