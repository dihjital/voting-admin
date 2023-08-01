<?php

namespace App\Http\Controllers;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Auth;

class QrCodeController extends Controller
{
    const URL = 'https://localhost:8200';

    public function download($question_id)
    {
        return response()->streamDownload(
            function() use ($question_id) {
                echo QrCode::size(200)
                    ->format('png')
                    ->generate(self::getURL().'/questions/'.$question_id.'/votes?user_id='.Auth::id());
            },
            'qr-code.png',
            [
                'content-type' => 'image/png',
            ]
        );
    }

    protected static function getURL()
    {
        return env('RESULTS_URL', self::URL);
    }
}
