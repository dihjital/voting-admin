<?php

namespace App\Http\Livewire;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

use App\Http\Livewire\Traits\WithErrorMessage;
use App\Http\Livewire\Traits\WithOAuthLogin;
use App\Http\Livewire\Traits\WithPerPagePagination;
use App\Http\Livewire\Traits\WithUUIDSession;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Laravel\Jetstream\InteractsWithBanner;
use Illuminate\Pagination\LengthAwarePaginator;

use Livewire\Component;

class ShowQuestions extends Component
{
    use InteractsWithBanner, WithErrorMessage, WithPerPagePagination, WithOAuthLogin, WithUUIDSession;

    public $access_token;
    public $refresh_token;

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
        try {
            // OAuth login process
            list($this->access_token, $this->refresh_token) = $this->login();

            // Send over the current user uuid and get a session id back
            $this->registerUUIDInSession($this->access_token);
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
        try {
            $response = Http::withToken($this->access_token)
                ->withHeaders([
                    'session-id' => $this->session_id
                ])->patch(self::getURL().'/questions/'.$question_id, [
                    'is_closed' => ! $is_closed,
                    'user_id' => Auth::id(), // Until it is not mondatory at the back-end
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
        $url = env('RESULTS_URL', 'https://voting-results.votes365.org');
        $url = $url.'/'.$question_id.'/votes?user_id='.Auth::id();

        return base64_encode(QrCode::format('png')
            ->size(256)
            // ->color(255,255,255)->backgroundColor(0,0,0)
            ->generate($url));
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
            $response = Http::get(self::getURL().'/questions/'.$this->question_id)
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
        // TODO: If Paginating is enabled then deleting the last record on the page
        // should move to the previous page. Now it stays on the empty page instead.
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
            
            if (self::getPAGINATING()) {
                $currentPage = $page ?? request('page', 1);
                $url .= '?page='.$currentPage;
            }
            
            $response = Http::withHeaders([
                'session-id' => $this->session_id
                ])->get($url)
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
