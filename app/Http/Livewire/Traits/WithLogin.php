<?php

namespace App\Http\Livewire\Traits;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;

use Carbon\Carbon;

trait WithLogin
{
    public $access_token;
    public $refresh_token;

    public $expires_in;

    public $scope = [
        'list-quizzes',
        'list-questions',
        'list-votes',
        'create-quiz',
        'create-question',
        'create-vote',
        'delete-quiz',
        'delete-question',
        'delete-votes',
        'delete-vote',
        'modify-quiz',
        'modify-question',
        'modify-vote',
        'close-question',
        'vote',
    ];

    protected function getNewTokenFromApi(): array
    {
        $api_user = env('API_USER');
        $api_secret = env('API_SECRET');
        $api_endpoint = env('API_ENDPOINT');

        $client_id = env('PASSPORT_CLIENT_ID');
        $client_secret = env('PASSPORT_CLIENT_SECRET');

        // Tokens are not stored in session neither in cache so we have to log in ...
        $response = Http::asForm()->post($api_endpoint.'/login', [
            'email'     => $api_user,
            'password'  => $api_secret,
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'scope' => implode(' ', $this->scope),
        ]);

        if (!$response->ok()) {
            throw new \Exception($response->status().': '.$response->body());
        }

        $this->access_token = $response['access_token'];
        $this->refresh_token = $response['refresh_token'];
        $this->expires_in = $response['expires_in'];

        Log::debug('Returning tokens from the back-end');
        return [$this->access_token, $this->refresh_token];
    }

    protected function checkHalfTime($issued_at, $expires_in): bool
    {
        if (empty($issued_at) || empty($expires_in))
            return true;

        $expires_at = $issued_at->copy()->addSeconds($expires_in);
        $half_time = $issued_at->copy()->average($expires_at);

        return Carbon::now() > $half_time;
    }

    protected function refreshToken($refresh_token): array
    {
        $response = Http::asForm()->post(env('PASSPORT_LOGIN_ENDPOINT'), [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refresh_token,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'scope' => implode(' ', $this->scope),
        ]);

        if ($response->ok()) {
            return [
                $response['access_token'], 
                $response['refresh_token'], 
                $response['expires_in']
            ];
        }

        $this->deleteTokensFromCache();

        // TODO: Log here that refresh-token has failed. Use Log::error
        return [];
    }

    protected function isTokenValid($access_token = ''): bool
    {
        if (!$access_token) return false;

        $api_endpoint = env('PASSPORT_LOGIN_ENDPOINT');

        $response = Http::withToken($access_token)
            ->get($api_endpoint.'/validate');

        if (!$response->ok()) {
            Log::error($response->body());
            throw new \Exception($response->body(), $response->status());
        }

        if ($response['valid'] !== true) {
            Log::debug('Access token is no longer valid');
            return false;
        }

        Log::debug('Access token is still valid');
        return true;
    }

    protected function getTokensFromCache(): array
    {
        if (Cache::has('access_token') && 
            Cache::has('refresh_token')) {
                return [
                    'access_token' => Cache::get('access_token'),
                    'refresh_token' => Cache::get('refresh_token'),
                ];
        }

        return ['access_token' => '', 'refresh_token' => ''];
    }

    protected function storeTokensInCache($access_token = '', $refresh_token = '', $expires_in = ''): void
    {
        Cache::put('access_token', $access_token ?: $this->access_token);
        Cache::put('refresh_token', $refresh_token) ?: $this->refresh_token;
        Cache::put('issued_at', Carbon::now());
        Cache::put('expires_in', $expires_in ?: $this->expires_in);
    }

    protected function retryCallback(\Exception $e, PendingRequest $request) 
    {
        if ($e instanceof RequestException && $e->response->status() === 419) {
            // Session expired and the session id is stuck ...
            Log::debug('Session id expired: '.$this->session_id);
            $this->deleteSessionId();
            return true;
        }

        if (! $e instanceof RequestException || !in_array($e->response->status(), [401, 403])) {
            Log::debug('Request failed with status code: '.$e->response->status());
            return false;
        }
    
        Log::debug('Request retry in progress ...');
        Log::debug('Session id is: '.$this->session_id);
        
        if ($this->isTokenValid($this->access_token)) {
            // Make sure we use a valid token ...
            $request->withToken($this->access_token);

            return true; // If true then it will retry again ...
        }
    
        $this->getNewTokenFromApi();
        $this->storeTokensInCache();
    
        $this->deleteSessionId();
        $this->session_id = $this->startSessionIfRequired($this->access_token);
    
        $request
            ->withToken($this->access_token)
            ->withHeaders([
                'session-id' => $this->session_id,
            ]);
    
        return true;
    }
}