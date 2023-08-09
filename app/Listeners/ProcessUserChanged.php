<?php

namespace App\Listeners;

use App\Events\UserChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

use Illuminate\Process\Exceptions\ProcessFailedException;

class ProcessUserChanged implements ShouldQueue
{
    protected const PYTHON = '/usr/bin/python3';
    protected const COPY_USERS_CMD = '/usr/bin/copy_users.py';

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UserChanged $event): void
    {
        Log::info(__('Synchronizing user info for: :userName', ['userName' => $event->user->name]));
        try {
            $process = Process::timeout(120)
                ->env([
                    'SRC_DB' => env('DB_DATABASE', database_path('database.sqlite'))
                ])
                ->run(self::PYTHON.' '.self::COPY_USERS_CMD)->throw();
            Log::info($process->output());
        } catch (ProcessFailedException $e) {
            Log::error($e->getMessage());
        }
    }
}
