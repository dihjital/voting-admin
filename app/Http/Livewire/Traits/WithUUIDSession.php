<?php

namespace App\Http\Livewire\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;

trait WithUUIDSession
{
    public $session_id;
    public $session_key;

    protected function startSessionIfRequired($access_token)
    {
        $this->session_key = $this->setSessionKey();

        return $this->isSessionIdExists()
            ? $this->getSessionId()
            : $this->requestNewSessionId($access_token, request('user_id'));
    }

    protected function setSessionKey(): string
    {
        return Auth::id().':session_id';
    }

    protected function deleteSessionId(): void
    {
        session()->forget($this->session_key);
    }

    protected function isSessionIdExists(): bool
    {
        return session()->has($this->session_key);
    }

    protected function getSessionId()
    {
        return session()->get($this->session_key);
    }

    protected function setSessionId($session_id)
    {
        session()->put($this->session_key, $session_id);
    }

    protected function requestNewSessionId($access_token, $user_id = '')
    {
        $response = Http::withToken($access_token)
            ->retry(3, 500, function (\Exception $e, PendingRequest $request) {
                if (! $e instanceof RequestException || !in_array($e->response->status(), [401, 403])) {
                    Log::debug('Request failed with status code: '.$e->response->status());
                    return false;
                }
            
                Log::debug('Request retry in progress');
            
                if ($this->isTokenValid($this->access_token)) {
                    return false;
                }
            
                $this->getNewTokenFromApi();
                $this->storeTokensInCache();
            
                $request->withToken($this->access_token);
            
                return true;
            })
            ->post(self::getURL().'/session', [
                'user_id' => $user_id ?: Auth::id(),
            ])
            ->throwUnlessStatus(200);

        $this->setSessionId($response->json()['session_id']);
        
        return $this->getSessionId();
    }
}