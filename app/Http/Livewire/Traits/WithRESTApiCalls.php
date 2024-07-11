<?php

namespace App\Http\Livewire\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Client\PendingRequest;

trait WithRESTApiCalls
{
    public function patchQuiz($quiz_id, $paramName, $paramValue)
    {
        $url = config('services.api.endpoint',
            fn() => throw new \Exception('No API endpoint is defined')
        ) . '/quizzes/' . $quiz_id;

        Http::withToken($this->access_token)
            ->withHeaders([
                'session-id' => $this->session_id,
            ])
            ->retry(3, 500, function (\Exception $e, PendingRequest $request) {
                return $this->retryCallback($e, $request);
            })
            ->patch($url, [
                $paramName => $paramValue,
            ])
            ->throwUnlessStatus(200);
    }

    public function deleteQuiz($quiz_id)
    {
        // Delete the selected quiz ...
        $url = config('services.api.endpoint',
            fn() => throw new \Exception('No API endpoint is defined')
        ) . '/quizzes/' . $quiz_id;

        Http::withToken($this->access_token)
            ->withHeaders([
                'session-id' => $this->session_id
            ])
            ->retry(3, 500, function (\Exception $e, PendingRequest $request) {
                return $this->retryCallback($e, $request);
            })
            ->delete($url)
            ->throwUnlessStatus(200);
    }

    public function patchQuestion($question_id, $paramName, $paramValue)
    {
        $url = config('services.api.endpoint',
            fn() => throw new \Exception('No API endpoint is defined')
        ) . '/questions/' . $question_id;

        Http::withToken($this->access_token)
            ->withHeaders([
                'session-id' => $this->session_id,
            ])
            ->retry(3, 500, function (\Exception $e, PendingRequest $request) {
                return $this->retryCallback($e, $request);
            })
            ->patch($url, [
                $paramName => $paramValue,
            ])
            ->throwUnlessStatus(200);
    }

    public function deleteQuestion($question_id)
    {
        // Delete the selected question ...
        $url = config('services.api.endpoint',
            fn() => throw new \Exception('No API endpoint is defined')
        ) . '/questions/' . $question_id;

        Http::withToken($this->access_token)
            ->withHeaders([
                'session-id' => $this->session_id
            ])
            ->retry(3, 500, function (\Exception $e, PendingRequest $request) {
                return $this->retryCallback($e, $request);
            })
            ->delete($url)
            ->throwUnlessStatus(200);
    }
}