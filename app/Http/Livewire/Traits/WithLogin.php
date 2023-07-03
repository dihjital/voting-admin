<?php

namespace App\Http\Livewire\Traits;

use Illuminate\Support\Facades\Http;

trait WithLogin
{
    protected $access_token;
    protected $refresh_token;

    protected $client_id;
    protected $client_secret;

    public function initializeWithLogin()
    {
        $this->api_user = env('API_USER');
        $this->api_secret = env('API_SECRET');

        $this->client_id = env('PASSPORT_CLIENT_ID');
        $this->client_secret = env('PASSPORT_CLIENT_SECRET');
    }

    protected function refreshToken(): array
    {
        // We store the tokens in the current session
        // TODO: Store the tokens in the cache.
        $access_token = session()->get('access_token');
        $refresh_token = session()->get('refresh_token');

        if ($access_token && $refresh_token) {
            $response = Http::asForm()->post(env('PASSPORT_LOGIN_ENDPOINT'), [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refresh_token,
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'scope' => '',
            ]);

            if ($response->ok()) {
                session()->put('access_token', $response['access_token']);
                session()->put('refresh_token', $response['refresh_token']);
                return [$response['access_token'], $response['refresh_token']];
            }
        }

        return ['', ''];
    }

    protected function login(): array 
    {
        // Let us try to refresh our token
        list($access_token, $refresh_token) = $this->refreshToken();
        if ($access_token && $refresh_token)
            return [$access_token, $refresh_token];

        // If refresh token failed for any reason we try to log in
        try {
            $response = Http::asForm()->post(self::getURL().'/login', [
                'email'     => $this->api_user,
                'password'  => $this->api_secret,
            ]);

            if (!$response->ok()) {
                throw new \Exception($response->status().': '.$response->body());
            }

            $access_token = $response['access_token'];
            $refresh_token = $response['refresh_token'];

            session()->put('access_token', $access_token);
            session()->put('refresh_token', $refresh_token);
        } catch (\Exception $e) {
            $this->error_message = $e->getMessage();
        }

        return [$access_token, $refresh_token] ?? ['', ''];
    }

}