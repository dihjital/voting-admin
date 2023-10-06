<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

use App\Mail\NewUserRegistered;

class LogRegisteredUser
{
    protected string $adminEmailAddress;
    /**
     * Create the event listener.
     */
    public function __construct()
    { 
        $this->adminEmailAddress = env('MAIL_ADMIN_ADDRESS', 'voting-admin@mg.votes365.org');
    }

    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        Log::info('A new user has been registered: '.$event->user->email);

        Mail::to($this->adminEmailAddress)->send(new NewUserRegistered($event->user));
    }
}