<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\SummaryController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'backend.login', // Get the access_token and the session_id from the back-end for the currently logged in user ...
])->group(function () {
    Route::get('/dashboard', [SummaryController::class, 'display'])->name('dashboard');
    Route::get('/questions', function () {
        return view('list-all-questions');
    })->name('questions');
    Route::get('/questions/{question_id}/votes', function ($question_id) {
        return view('list-one-question', [
            'question_id' => $question_id,
        ]);
    })->name('question');
});

