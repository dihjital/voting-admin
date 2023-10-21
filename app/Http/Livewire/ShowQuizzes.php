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

class ShowQuizzes extends Component
{
    use InteractsWithBanner, WithErrorMessage, WithPerPagePagination, WithLogin;
    
    public $error_message;

    public $quiz_id;
    public $name;

    public $results_qrcode = false;
    public $confirm_delete = false;
    public $update_quiz = false;
    public $new_quiz = false;

    const URL = 'http://localhost:8000';
    const PAGINATING = TRUE;

    protected $rules = [
        'name' => 'required|min:6',
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

    public function generateQrCode($quiz_id)
    {
        // TODO: Move this to a separate method
        $url = env('CLIENT_URL', 'https://voting-client.votes365.org');
        $url .= '/quizzes/'.$quiz_id.'/questions?uuid='.Auth::id();

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
        
        try {
            // Get the selected Quiz name ...
            $response = Http::withToken($this->access_token)
                ->withHeaders([
                    'session-id' => $this->session_id,
                ])
                ->get(self::getURL().'/quizzes/'.$this->quiz_id)
                ->throwUnlessStatus(200);
            $this->name = $response->json()['name'];
        } catch (\Exception $e) {
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }
    }

    public function create()
    {
        $this->validate();

        try {
            // Create a new Quiz ...
            $response = Http::withToken($this->access_token)
                ->withHeaders([
                    'session-id' => $this->session_id
                ])->post(self::getURL().'/quizzes', [
                    'name' => $this->name,
                ])->throwUnlessStatus(201);

            $this->banner(__('Quiz successfully created'));
            $this->emit('confirming-quiz-create');
        } catch (\Exception $e) {
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }

        $this->new_quiz = !$this->new_quiz;
    }

    public function update($quiz_id)
    {
        $quiz_id ??= $this->quiz_id;

        $this->validate();

        try {
            // Update the selected Quiz ...
            $response = Http::withToken($this->access_token)
                ->withHeaders([
                    'session-id' => $this->session_id
                ])->put(self::getURL().'/quiz/'.$quiz_id, [
                    'name' => $this->name,
                ])->throwUnlessStatus(200);

            $this->banner(__('Quiz successfully updated'));
            $this->emit('confirming-quiz-name-update');
        } catch (\Exception $e) {
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }

        $this->update_quiz = !$this->update_quiz;
    }

    public function delete($quiz_id)
    {
        $quiz_id ??= $this->quiz_id;

        try {
            // Delete the selected Quiz ...
            $response = Http::withToken($this->access_token)
                ->withHeaders([
                    'session-id' => $this->session_id
                ])->delete(self::getURL().'/quizzes/'.$quiz_id)
                ->throwUnlessStatus(200);

            $this->banner(__('Quiz deleted successfully'));
        } catch (\Exception $e) {
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }

        $this->confirm_delete = !$this->confirm_delete;
    }

    public function fetchData($page = null)
    {
        try {
            $url = self::getURL().'/quizzes';
            
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
                    ['path' => url('/quizzes')]
                )
                : $data;
        } catch (\Exception $e) {
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