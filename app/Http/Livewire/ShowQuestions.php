<?php

namespace App\Http\Livewire;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

use App\Http\Livewire\Traits\WithLogin;
use App\Http\Livewire\Traits\WithErrorMessage;
use App\Http\Livewire\Traits\WithPerPagePagination;
use App\Http\Livewire\Traits\WithUUIDSession;
use App\Http\Livewire\Traits\WithRESTApiCalls;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

use Laravel\Jetstream\InteractsWithBanner;
use Illuminate\Pagination\LengthAwarePaginator;

use Illuminate\Http\Client\PendingRequest;

use Livewire\Component;

use Carbon\Carbon;

class ShowQuestions extends Component
{
    use InteractsWithBanner;
    use WithErrorMessage;
    use WithPerPagePagination;
    use WithLogin;
    use WithUUIDSession;
    use WithRESTApiCalls;
    
    public $error_message;

    public $question_id;
    public $question_text;
    public $question_close_at = null;
    public bool $show_current_votes = true;
    public $correct_vote;

    public array $votes = []; // Voting options for the selected question

    public $quiz_id;

    public $results_qrcode = false;
    public $confirm_delete = false;
    public $update_question = false;
    public $new_question = false;

    public $filters = [
        'closed' => true,
        'quizzes' => true,
    ];

    const PAGINATING = TRUE;

    protected $rules = [
        'question_text' => 'required|min:6',
        // 'is_closed' => 'nullable|boolean', If the new question modal has a toggle for this property
        // 'is_secure' => 'nullable|boolean', If the new question modal has a toggle for this property
        'show_current_votes' => 'required|bool',
        'question_close_at' => 'nullable|date',
        'correct_vote' => 'nullable|integer',
    ];

    protected function initializeFiltering()
    {
        $filters = session()->get('showQuestions.filters', $this->filters);
        foreach($filters as $key => $value) {
            $this->filters[$key] = $value;
        }
    }

    public function updatedFilters($value, $key)
    {
        session()->put('showQuestions.filters', $this->filters);
    }

    public function updatedQuestionCloseAt($value)
    {
        // Flowbite DatePicker is sending back "" even if the DatePicker value is undefined ... 
        $this->question_close_at = $value === "" ? null : $value;
    }

    public function mount($quiz_id = null)
    {
        // It will only assing $quiz_id to the public variable if the public variable is null ...
        $this->quiz_id ??= $quiz_id;

        $this->initializeFiltering();

        try {
            list(
                'access_token' => $this->access_token,
                'refresh_token' => $this->refresh_token
            ) = $this->getTokensFromCache();
            $this->session_id = $this->startSessionIfRequired($this->access_token);
        } catch (\Exception $e) {
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

    public function secureQuestion($question_id, $is_secure)
    {
        // Secure the selected Question ...
        try {
            $this->patchQuestion($question_id, 'is_secure', ! $is_secure);

            $this->banner(__('Question successfully updated'));
            $this->emit('confirming-question-update');
        } catch (\Exception $e) {
            Log::error('Failed to update question: ' . $e->getMessage());
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }
    }

    public function openQuestion($question_id, $is_closed)
    {
        // Open or close the selected Question ...
        try {
            $this->patchQuestion($question_id, 'is_closed', ! $is_closed);
            
            $this->banner(__('Question successfully updated'));
            $this->emit('confirming-question-update');
        } catch (\Exception $e) {
            Log::error('Failed to update question: ' . $e->getMessage());
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }
    }

    public function generateQrCode($question_id)
    {
        // TODO: Move this to a separate method
        $url = env('CLIENT_URL', 'https://voting-client.votes365.org');
        $url .= '/questions/'.$question_id.'/votes?uuid='.Auth::id();

        return base64_encode(QrCode::format('png')
            ->size(200)
            ->generate($url));
    }

    public function generateQrCodeForMobile($question_id)
    {
        return base64_encode(QrCode::format('png')
            ->size(200)
            ->generate(json_encode([
                'user_id' => Auth::id(),
                'question_id' => $question_id,
            ])));
    }

    public function toggleQRCodeModal($question_id)
    {
        $this->results_qrcode = ! $this->results_qrcode;
        $this->question_id = $question_id;
    }

    public function toggleCreateQuestionModal()
    {
        $this->resetErrorBag();
        $this->resetValidation();

        $this->question_text = '';
        $this->question_close_at = null;
        
        $this->new_question = ! $this->new_question;
    }

    public function toggleDeleteQuestionModal($question_id)
    {
        $this->confirm_delete = ! $this->confirm_delete;
        $this->question_id = $question_id;
    }

    public function toggleUpdateQuestionModal($question_id)
    {
        $this->resetErrorBag();
        $this->resetValidation();

        $this->update_question = ! $this->update_question;
        $this->question_id = $question_id;

        // Get the selected question text...
        try {
            $url = config('services.api.endpoint',
                fn() => throw new \Exception('No API endpoint is defined')
            ).'/questions/'.$this->question_id;

            $response = Http::withToken($this->access_token)
                ->withHeaders([
                    'session-id' => $this->session_id,
                ])
                ->retry(3, 500, function (\Exception $e, PendingRequest $request) {
                    return $this->retryCallback($e, $request);
                })
                ->get($url)
                ->throwUnlessStatus(200);

            $this->extractQuestionData($response);
        } catch (\Exception $e) {
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }
    }

    protected function extractQuestionData($response): void
    {
        if ($response) {
            // Question text
            $this->question_text = $response->json()['question_text'];

            // Question close date
            $closed_at = $response->json()['closed_at'];
            $this->question_close_at = $closed_at
                ? Carbon::parse($closed_at)->format('m/d/Y')
                : null;

            // Show current votes for question
            $this->show_current_votes = $response->json()['show_current_votes'];

            // Correct vote if set. Will return the id of the vote.
            $this->correct_vote = $response->json()['correct_vote'];

            // Get the voting options for this question
            $this->votes = $response->json()['votes'];
        }
    }

    public function create()
    {
        $this->validate();

        // Create a new question ...
        try {
            $url = config('services.api.endpoint',
                fn() => throw new \Exception('No API endpoint is defined')
            ).'/questions';

            $response = Http::withToken($this->access_token)
                ->withHeaders([
                    'session-id' => $this->session_id
                ])
                ->retry(3, 500, function (\Exception $e, PendingRequest $request) {
                    return $this->retryCallback($e, $request);
                })
                ->post($url, [
                    'question_text' => $this->question_text,
                    'closed_at' => $this->question_close_at ?? null,
                    'quiz_id' => $this->quiz_id ?? null,
                ])
                ->throwUnlessStatus(201);

            $this->banner(__('Question successfully created'));
            $this->emit('confirming-question-create');
        } catch (\Exception $e) {
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }

        $this->new_question = !$this->new_question;
    }

    public function update($question_id)
    {
        $question_id ??= $this->question_id;

        $this->validate();

        // Update the selected vote ...
        try {
            $url = config('services.api.endpoint',
                fn() => throw new \Exception('No API endpoint is defined')
            ).'/questions/'.$this->question_id;

            $response = Http::withToken($this->access_token)
                ->withHeaders([
                    'session-id' => $this->session_id
                ])
                ->retry(3, 500, function (\Exception $e, PendingRequest $request) {
                    return $this->retryCallback($e, $request);
                })
                ->put($url, [
                    'question_text' => $this->question_text,
                    'closed_at' => $this->question_close_at ?? null,
                    'show_current_votes' => $this->show_current_votes,
                    'correct_vote' => $this->correct_vote ?? null,
                ])
                ->throwUnlessStatus(200);

            $this->banner(__('Question successfully updated'));
            $this->emit('confirming-question-update');
        } catch (\Exception $e) {
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }

        $this->update_question = !$this->update_question;
    }

    public function delete($question_id)
    {
        $question_id ??= $this->question_id;

        // Delete the selected question ...
        try {
            $this->deleteQuestion($question_id);
            $this->banner(__('Question successfully deleted'));
        } catch (\Exception $e) {
            Log::error('Failed to delete question: ' . $e->getMessage());
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }

        $this->confirm_delete = ! $this->confirm_delete;
    }

    public function fetchData($page = null)
    {
        try {
            $endpoint = config('services.api.endpoint',
                fn() => throw new \Exception('No API endpoint is defined')
            );
            $url = $this->quiz_id
                ? $endpoint.'/quizzes/'.$this->quiz_id.'/questions'
                : $endpoint.'/questions';
            
            $response = Http::withToken($this->access_token)
                ->withHeaders([
                    'session-id' => $this->session_id
                ])
                ->retry(3, 500, function (\Exception $e, PendingRequest $request) {
                    return $this->retryCallback($e, $request);
                })
                ->get($url, array_filter([
                    'page' => self::getPAGINATING() ? $page ?? request('page', 1) : '',
                    'closed' => $this->filters['closed'] ?? null,
                    'quizzes' => $this->filters['quizzes'] ?? null,
                ]))
                ->throwUnlessStatus(200);

            $data = $response->json();
            
            return self::getPAGINATING()
                ? new LengthAwarePaginator(
                    collect($data['data']),
                    $data['total'],
                    $data['per_page'],
                    $data['current_page'],
                    ['path' => url('/questions')]
                )
                : $data;
        } catch (\Exception $e) {
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        } 
    }

    public function render()
    {
        return view('livewire.show-questions', [
            'questions' => $this->fetchData($this->current_page),
        ]);
    }
}