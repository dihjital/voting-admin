<?php

namespace App\Http\Livewire\Traits;

use GuzzleHttp\Client;

trait WithLogin
{
    public $access_token;
    public $refresh_token;

    protected function login(): array 
    {
        $access_token = session()->get('access_token');
        $refresh_token = session()->get('refresh_token');

        if ($access_token && $refresh_token)
             return [$access_token, $refresh_token];

        // Log in
        $client = new Client();

        try {
            $response = $client->post(self::getURL().'/login', [
                'form_params' => [
                    'email' => 'alexandra.csonka13@gmail.com',
                    'password' => 'GailBa1!',
                ],
            ]);
            $tokenData = json_decode($response->getBody(), true);

            $access_token = $tokenData['access_token'];
            $refresh_token = $tokenData['refresh_token'];
            session()->put('access_token', $access_token);
            session()->put('refresh_token', $refresh_token);
        } catch (\Exception $e) {
            $this->error_message = $e->getMessage();
        }

        return [$access_token, $refresh_token] ?? ['', ''];
    }

}