<?php

namespace App\Http\Livewire;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

use App\Http\Livewire\Traits\WithLogin;
use App\Http\Livewire\Traits\WithErrorMessage;
use App\Http\Livewire\Traits\WithPerPagePagination;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

use Laravel\Jetstream\InteractsWithBanner;
use Illuminate\Pagination\LengthAwarePaginator;

use Livewire\Component;

class ShowQuestions extends Component
{
    use InteractsWithBanner, WithErrorMessage, WithPerPagePagination, WithLogin;
    
    public $error_message;

    public $question_id;
    public $question_text;

    public $results_qrcode = false;
    public $confirm_delete = false;
    public $update_question = false;
    public $new_question = false;

    const URL = 'http://localhost:8000';
    const PAGINATING = TRUE;

    protected $rules = [
        'question_text' => 'required|min:6',
        // 'is_closed' => 'nullable|boolean', Should add a property as well to the model
    ];

    public function mount()
    {
        // Check if the application has logged in to the API back-end successfully ...
        try {
            $this->login();
        } catch (\Exception $e) {
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }
    }

    public static function getURL(): string
    {
        return env('API_ENDPOINT', self::URL);
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

    public function openQuestion($question_id, $is_closed)
    {
        // Open or close the selected Question ...
        // TODO: Move this to mandatory session-id check at the back-end
        try {
            $response = Http::withToken($this->access_token)
                ->withHeaders([
                    'session-id' => $this->session_id,
                ])->patch(self::getURL().'/questions/'.$question_id, [
                    'is_closed' => ! $is_closed,
                ])->throwUnlessStatus(200);

            $this->banner(__('Question successfully updated'));
            $this->emit('confirming-question-text-update');
        } catch (\Exception $e) {
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

    public function generateQrCodeForUuid($question_id)
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
        
        try {
            // Get the selected question text...
            $response = Http::withToken($this->access_token)
                ->withHeaders([
                    'session-id' => $this->session_id,
                ])
                ->get(self::getURL().'/questions/'.$this->question_id)
                ->throwUnlessStatus(200);
            $this->question_text = $response->json()['question_text'];
        } catch (\Exception $e) {
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }
    }

    public function create()
    {
        $this->validate();

        try {
            // Create a new question ...
            $response = Http::withToken($this->access_token)
                ->withHeaders([
                    'session-id' => $this->session_id
                ])->post(self::getURL().'/questions', [
                    'question_text' => $this->question_text,
                ])->throwUnlessStatus(201);

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

        try {
            // Update the selected vote ...
            $response = Http::withToken($this->access_token)
                ->withHeaders([
                    'session-id' => $this->session_id
                ])->put(self::getURL().'/questions/'.$this->question_id, [
                    'question_text' => $this->question_text,
                ])->throwUnlessStatus(200);

            $this->banner(__('Question successfully updated'));
            $this->emit('confirming-question-text-update');
        } catch (\Exception $e) {
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }

        $this->update_question = !$this->update_question;
    }

    public function delete($question_id)
    {
        $question_id ??= $this->question_id;

        try {
            // Delete the selected vote ...
            $response = Http::withToken($this->access_token)
                ->withHeaders([
                    'session-id' => $this->session_id
                ])->delete(self::getURL().'/questions/'.$this->question_id)
                ->throwUnlessStatus(200);

            $this->banner(__('Question successfully deleted'));
        } catch (\Exception $e) {
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }

        $this->confirm_delete = !$this->confirm_delete;
    }

    public function fetchData($page = null)
    {
        try {
            $url = self::getURL().'/questions';
            
            $response = Http::withToken($this->access_token)
                ->withHeaders([
                    'session-id' => $this->session_id
                ])->get($url, array_filter([
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