<?php

use App\Http\Livewire\ShowQuestions;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

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
    'verified'
])->group(function () {
    Route::get('/dashboard', function () {
        $result = Http::get(env('API_ENDPOINT').'/summary');
        return $result->ok()
            ? view('dashboard', [
                'results' => (object) $result->json()
            ])
            : view('dashboard');
    })->name('dashboard');
    Route::get('/questions', function () {
        return view('list-all-questions');
    })->name('questions');
    Route::get('/questions/{question_id}/votes', function ($question_id) {
        return view('list-one-question', [
            'question_id' => $question_id,
        ]);
    })->name('question');
});

