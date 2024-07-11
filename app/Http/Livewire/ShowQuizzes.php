<?php

namespace App\Http\Livewire;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

use App\Http\Livewire\Traits\WithLogin;
use App\Http\Livewire\Traits\WithUUIDSession;
use App\Http\Livewire\Traits\WithErrorMessage;
use App\Http\Livewire\Traits\WithRESTApiCalls;
use App\Http\Livewire\Traits\WithPerPagePagination;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use Laravel\Jetstream\InteractsWithBanner;
use Illuminate\Pagination\LengthAwarePaginator;

use Illuminate\Http\Client\PendingRequest;

use Livewire\Component;

use Exception;

class ShowQuizzes extends Component
{
    use InteractsWithBanner;
    use WithErrorMessage;
    use WithPerPagePagination;
    use WithLogin;
    use WithUUIDSession;
    use WithRESTApiCalls;
    
    public $error_message;

    public $quiz_id;
    public $name;

    public $results_qrcode = false;
    public $confirm_delete = false;
    public $update_quiz = false;
    public $new_quiz = false;

    const PAGINATING = TRUE;

    protected $rules = [
        'name' => 'required|min:6',
    ];

    public function mount()
    {
        try {
            list(
                'access_token' => $this->access_token,
                'refresh_token' => $this->refresh_token
            ) = $this->getTokensFromCache();

            $this->session_id = $this->startSessionIfRequired($this->access_token);
        } catch (Exception $e) {
            Log::error('Error while starting session: ' . $e->getMessage());
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }
    }

    public static function getPAGINATING(): bool
    {
        return env('PAGINATING', self::PAGINATING);
    }

    public function closedColor($button = 'modify', $is_closed = 0)
    {
        return [
            'modify' => [
                '0' => 'bg-indigo-500 hover:bg-indigo-600',
                '1' => 'bg-gray-500 hover:bg-gray-600',
            ],
            'delete' => [
                '0' => 'bg-red-500 hover:bg-red-600',
                '1' => 'bg-gray-500 hover:bg-gray-600',
            ],
        ][$button][$is_closed];
    }

    public function getQrCodeUrlForVotingClient($quiz_id)
    {
        return env('CLIENT_URL', 'https://voting-client.votes365.org').
            '/quizzes/' . $quiz_id . '/questions?uuid=' . Auth::id();
    }

    public function generateQrCode($quiz_id)
    {
        $url = $this->getQrCodeUrlForVotingClient($quiz_id);

        return base64_encode(QrCode::format('png')
            ->size(200)
            ->generate($url));
    }

    public function generateQrCodeForMobile($quiz_id)
    {
        return base64_encode(QrCode::format('png')
            ->size(200)
            ->generate(json_encode([
                'user_id' => Auth::id(),
                'quiz_id' => $quiz_id,
            ])));
    }

    public function toggleQRCodeModal($quiz_id)
    {
        $this->results_qrcode = ! $this->results_qrcode;
        $this->quiz_id = $quiz_id;
    }

    public function toggleCreateQuestionModal()
    {
        $this->resetErrorBag();
        $this->resetValidation();

        $this->name = '';
        $this->new_quiz = ! $this->new_quiz;
    }

    public function toggleDeleteQuizModal($quiz_id)
    {
        $this->confirm_delete = ! $this->confirm_delete;
        $this->quiz_id = $quiz_id;
    }

    public function toggleUpdateQuizModal($quiz_id)
    {
        $this->resetErrorBag();
        $this->resetValidation();

        $this->update_quiz = ! $this->update_quiz;
        $this->quiz_id = $quiz_id;

        // Get the selected Quiz name ...
        try {
            $url = config('services.api.endpoint',
                fn() => throw new \Exception('No API endpoint is defined')
            ).'/quizzes/'.$this->quiz_id;

            $response = Http::withToken($this->access_token)
                ->withHeaders([
                    'session-id' => $this->session_id,
                ])
                ->retry(3, 500, function (\Exception $e, PendingRequest $request) {
                    return $this->retryCallback($e, $request);
                })
                ->get($url)
                ->throwUnlessStatus(200);

            $this->name = $response->json()['name'];
        } catch (Exception $e) {
            Log::error('Error while fetching quiz name: ' . $e->getMessage());
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }
    }

    public function create()
    {
        $this->validate();

        // Create a new Quiz ...
        try {
            $url = config('services.api.endpoint',
                fn() => throw new \Exception('No API endpoint is defined')
            ).'/quizzes';

            $response = Http::withToken($this->access_token)
                ->withHeaders([
                    'session-id' => $this->session_id
                ])
                ->retry(3, 500, function (\Exception $e, PendingRequest $request) {
                    return $this->retryCallback($e, $request);
                })
                ->post($url, [
                    'name' => $this->name,
                ])
                ->throwUnlessStatus(201);

            $this->banner(__('Quiz successfully created'));
            $this->emit('confirming-quiz-create');
        } catch (Exception $e) {
            Log::error('Error while creating quiz: ' . $e->getMessage());
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }

        $this->new_quiz = !$this->new_quiz;
    }

    public function update($quiz_id)
    {
        $quiz_id ??= $this->quiz_id;

        $this->validate();

        // Update the selected Quiz ...
        try {
            $url = config('services.api.endpoint',
                fn() => throw new \Exception('No API endpoint is defined')
            ).'/quizzes/'.$quiz_id;

            $response = Http::withToken($this->access_token)
                ->withHeaders([
                    'session-id' => $this->session_id
                ])
                ->retry(3, 500, function (\Exception $e, PendingRequest $request) {
                    return $this->retryCallback($e, $request);
                })
                ->put($url, [
                    'name' => $this->name,
                ])
                ->throwUnlessStatus(200);

            $this->banner(__('Quiz successfully updated'));
            $this->emit('confirming-quiz-name-update');
        } catch (Exception $e) {
            Log::error('Error while updating quiz: ' . $e->getMessage());
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }

        $this->update_quiz = !$this->update_quiz;
    }

    public function delete($quiz_id = null)
    {
        $quiz_id ??= $this->quiz_id;

        // Delete the selected quiz ...
        try {
            $this->deleteQuiz($quiz_id);
            $this->banner(__('Quiz deleted successfully'));
        } catch (Exception $e) {
            Log::error('Failed to delete quiz: ' . $e->getMessage());
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }

        $this->confirm_delete = ! $this->confirm_delete;
    }

    public function fetchData($page = null)
    {
        try {
            $url = config('services.api.endpoint',
                fn() => throw new \Exception('No API endpoint is defined')
            ).'/quizzes';
            
            $response = Http::withToken($this->access_token)
                ->withHeaders([
                    'session-id' => $this->session_id
                ])
                ->retry(3, 500, function (\Exception $e, PendingRequest $request) {
                    return $this->retryCallback($e, $request);
                })
                ->get($url, array_filter([
                    'page' => self::getPAGINATING() ? $page ?? request('page', 1) : '',
                ]))
                ->throwUnlessStatus(200);

            $data = $response->json();
            
            return self::getPAGINATING()
                ? new LengthAwarePaginator(
                    collect($data['data']),
                    $data['total'],
                    $data['per_page'],
                    $data['current_page'],
                    ['path' => url('/quizzes')]
                )
                : $data;
        } catch (Exception $e) {
            Log::error('Error while fetching quizzes: ' . $e->getMessage());
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        } 
    }

    public function secureQuiz($quiz_id, $is_secure)
    {
        // Secure the selected Quiz ...
        // A Quiz will be considered secure if it's is_secure attribute is set to TRUE OR
        // if it has ANY Questions whose is_secure attribute are set to TRUE
        try {
            $this->patchQuiz($quiz_id, 'is_secure', ! $is_secure);

            $this->banner(__('Quiz successfully updated'));
        } catch (Exception $e) {
            Log::error('Failed to update quiz: ' . $e->getMessage());
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.show-quizzes', [
            'quizzes' => $this->fetchData($this->current_page),
        ]);
    }
}