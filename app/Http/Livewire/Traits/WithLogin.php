<?php

namespace App\Http\Livewire\Traits;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;

trait WithLogin
{
    public $access_token;
    public $refresh_token;

    public $session_id; // Back-end session-id

    protected function login()
    {
        if (Gate::any(['hasApiAccessToken', 'hasApiSessionId'])) {
            $this->access_token = session()->get('access_token');
            $this->refresh_token = session()->get('refresh_token');

            $this->session_id = session()->get(Auth::id().':session_id');
        }
    }
}