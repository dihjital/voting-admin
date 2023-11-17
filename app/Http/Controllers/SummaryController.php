<?php

namespace App\Http\Controllers;

use App\Http\Livewire\Traits\WithLogin;
use App\Http\Livewire\Traits\WithUUIDSession;

use Illuminate\Routing\Controller as BaseController;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Client\PendingRequest;

class SummaryController extends BaseController
{
    use WithLogin, WithUUIDSession;

    function display()
    {
        list(
            'access_token' => $this->access_token,
            'refresh_token' => $this->refresh_token
        ) = $this->getTokensFromCache();

        $this->session_id = $this->startSessionIfRequired($this->access_token);

        // TODO: Add a try ... catch block here
        $result = Http::withToken($this->access_token)
            ->withHeaders([
                'session-id' => $this->session_id,
            ])
            ->retry(3, 500, function (\Exception $e, PendingRequest $request) {
                return $this->retryCallback($e, $request);
            })
            ->get(config('services.api.endpoint',
                    fn() => throw new \Exception('No API endpoint is defined')
                ).'/summary'
            )
            ->throwUnlessStatus(200);

        return $result->ok()
            ? view('dashboard', ['results' => (object) $result->json()])
            : view('dashboard');
    }
}
