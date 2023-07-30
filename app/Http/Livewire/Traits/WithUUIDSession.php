<?php

namespace App\Http\Livewire\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

trait WithUUIDSession
{
    public $session_id;
    public $session_key;

    protected function registerUUIDInSession($access_token)
    {
        $this->session_key = $this->getSessionKey();

        $this->session_id = $this->isSessionIdExists()
            ? $this->getSessionId()
            : $this->requestNewSessionId($access_token);
    }

    protected function getSessionKey(): string
    {
        return Auth::id().':session_id';
    }

    protected function isSessionIdExists(): bool
    {
        return session()->has($this->session_key);
    }

    protected function getSessionId()
    {
        return session()->get($this->session_key);
    }

    protected function storeSessionId($session_id)
    {
        session()->put($this->session_key, $session_id);
    }

    protected function requestNewSessionId($access_token)
    {
        $response = Http::withToken($access_token)
        ->post(self::getURL().'/session', [
            'user_id' => Auth::id(),
        ])->throwUnlessStatus(200);

        $this->storeSessionId($response->json()['session_id']);
        
        return $this->getSessionId();
    }
}