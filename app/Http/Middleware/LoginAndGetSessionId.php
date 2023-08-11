<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Traits\WithUUIDSession;
use App\Http\Middleware\Traits\WithOAuthLogin;

use Illuminate\Http\Request;
use Illuminate\Http\Client\RequestException;
use Symfony\Component\HttpFoundation\Response;

use Illuminate\Support\Facades\Auth;

use Closure;

class LoginAndGetSessionId
{
    use WithOAuthLogin, WithUUIDSession;

    const URL = 'http://localhost:8000';

    public function __construct()
    {
        $this->initializeWithOAuthLogin();
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            list($access_token, $refresh_token) = $this->login();

            // Send over the current user uuid and get a session id back if a user logged into our application already ...
            Auth::id() && $this->registerUUIDInSession($access_token);
        } catch (RequestException $e) {
            // If the response is 400 then we did not provide a user_id
            // since the user did not log in to the application yet
            $response = $e->response;
            if ($response->status() !== 400) {
                abort(403, __('Authentication failed to the back-end: '.$e->getMessage()));
            }
        } catch (\Exception $e) {
            abort(403, __('Authentication failed to the back-end: '.$e->getMessage()));
        }

        return $next($request);
    }

    protected static function getURL()
    {
        return env('API_ENDPOINT', self::URL);
    }
}