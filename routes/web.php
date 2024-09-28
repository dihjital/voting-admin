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

Route::get('/register', fn() => abort(404));
Route::post('/register', fn() => abort(404));

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [SummaryController::class, 'display'])->name('dashboard');

    Route::get('/quizzes', function () {
        return view('list-all-quizzes');
    })->name('quizzes');

    Route::get('/quizzes/{quiz_id}/questions', function ($quiz_id) {
        return view('list-all-questions', 
            ['quiz_id' => $quiz_id]
        );
    })->name('quiz_questions');

    Route::get('/questions', function () {
        return view('list-all-questions');
    })->name('questions');
    
    Route::get('/questions/{question_id}/votes', function ($question_id) {
        return view('list-one-question', [
            'question_id' => $question_id,
        ]);
    })->name('question');
});

