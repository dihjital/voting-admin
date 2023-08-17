<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use Illuminate\Auth\Access\Response;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Gate::define('hasApiSessionId', function () {
            Log::debug('hasApiSessionId Gate is being evaluated');
            $session_key = Auth::id().':session_id';
            return session()->has($session_key)
                ? Response::allow()
                : Response::deny(__('A valid session id is required to access the API back-end'));
        });

        Gate::define('hasApiAccessToken', function () {
            Log::debug('hasApiAccessToken Gate is being evaluated');
            return session()->has('access_token') && session()->has('refresh_token')
                ? Response::allow()
                : Response::deny(__('An access token is required to access the API back-end'));
        });
    }
}