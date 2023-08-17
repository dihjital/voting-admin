<?php

namespace App\Http\Controllers;

use App\Http\Livewire\Traits\WithLogin;

use Illuminate\Routing\Controller as BaseController;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class SummaryController extends BaseController
{
    use WithLogin;

    function display()
    {
        // Check if the application has logged in to the API back-end successfully ...
        $this->login();

        $result = Http::withToken($this->access_token)
            ->withHeaders([
                'session-id' => session()->get(Auth::id().':session_id'),    
            ])->get(env('API_ENDPOINT').'/summary');

        return $result->ok()
            ? view('dashboard', ['results' => (object) $result->json()])
            : view('dashboard');
    }
}
