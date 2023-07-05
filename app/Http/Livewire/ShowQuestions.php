<?php

namespace App\Http\Livewire;

use App\Http\Livewire\Traits\WithOAuthLogin;
use App\Http\Livewire\Traits\WithPerPagePagination;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Laravel\Jetstream\InteractsWithBanner;

use Livewire\Component;

class ShowQuestions extends Component
{

    use InteractsWithBanner, WithPerPagePagination, WithOAuthLogin;

    public $access_token;
    public $refresh_token;

    public $error_message;

    public $question_id;
    public $question_text;

    public $confirm_delete = false;
    public $update_question = false;
    public $new_question = false;

    const URL = 'http://localhost:8000';
    const PAGINATING = TRUE;

    protected $rules = [
        'question_text' => 'required|min:6',
    ];

    public function mount()
    {
        try {
            list($this->access_token, $this->refresh_token) = $this->login();
        } catch (\Exception $e) {
            $this->error_message = $e->getMessage();
        }
    }

    public static function getURL(): string
    {
        return env('API_ENDPOINT', self::URL);
    }

    public static function getPAGINATING(): bool
    {
        return self::PAGINATING;
    }

    public function toggleCreateQuestionModal()
    {
        $this->resetErrorBag();
        $this->resetValidation();

        $this->question_text = '';
        $this->new_question = !$this->new_question;
    }

    public function toggleDeleteQuestionModal($question_id)
    {
        $this->confirm_delete = !$this->confirm_delete;
        $this->question_id = $question_id;
    }

    public function toggleUpdateQuestionModal($question_id)
    {
        $this->resetErrorBag();
        $this->resetValidation();

        $this->update_question = !$this->update_question;
        $this->question_id = $question_id;
        
        try {
            // Get the selected question text...
            $response = Http::get(self::getURL().'/questions/'.$this->question_id);
            $this->question_text = $response->json()['question_text'];
        } catch (\Exception $e) {
            $this->error_message = $e->getMessage();
        }
    }

    public function create()
    {
        $this->validate();

        if (!$this->access_token) {
            list($this->access_token, $this->refresh_token) = $this->login();
        }

        try {
            // Create a new vote ...
            $response = Http::withToken($this->access_token)
                ->post(self::getURL().'/questions', [
                    'question_text' => $this->question_text,
                ]);

            if (!in_array($response->status(), [200, 201])) {
                throw new \Exception("Return HTTP status code is not ".implode(' or ', [200, 201]));
            }

            $this->banner(__('Question successfully created'));
            $this->emit('confirming-question-create');
        } catch (\Exception $e) {
            $this->error_message = $e->getMessage();
        }

        $this->new_question = !$this->new_question;
    }

    public function update($question_id)
    {
        $question_id ??= $this->question_id;

        $this->validate();

        if (!$this->access_token) {
            list($this->access_token, $this->refresh_token) = $this->login();
        }

        try {
            // Update the selected vote ...
            $response = Http::withToken($this->access_token)
                ->put(self::getURL().'/questions/'.$this->question_id, [
                    'question_text' => $this->question_text,
                ]);

            if ($response->status() !== 200) {
                throw new \Exception("Return HTTP status code is not 200!");
            }

            $this->banner(__('Question successfully updated'));
            $this->emit('confirming-question-text-update');
        } catch (\Exception $e) {
            $this->error_message = $e->getMessage();
        }

        $this->update_question = !$this->update_question;
    }

    public function delete($question_id)
    {
        $question_id ??= $this->question_id;

        if (!$this->access_token) {
            list($this->access_token, $this->refresh_token) = $this->login();
        }

        try {
            // Delete the selected vote ...
            $response = Http::withToken($this->access_token)
                ->delete(self::getURL().'/questions/'.$this->question_id);

            if ($response->status() !== 200) {
                throw new \Exception("Return HTTP status code is not 200!");
            }

            $this->banner(__('Question successfully deleted'));
        } catch (\Exception $e) {
            $this->error_message = $e->getMessage();
        }

        $this->confirm_delete = !$this->confirm_delete;
    }

    public function fetchData($page = null)
    {
        try {
            $url = self::getURL().'/questions';
            
            if (self::PAGINATING) {
                $currentPage = $page ?? request('page', 1);
                $url .= '?page='.$currentPage;
            }
            
            $response = Http::get($url)->throwUnlessStatus(200);
            $data = $response->json();
            
            $paginator = self::PAGINATING
                ? new LengthAwarePaginator(
                    collect($data['data']),
                    $data['total'],
                    $data['per_page'],
                    $data['current_page'],
                    ['path' => url('/questions')]
                )
                : $data;
                
            return $paginator;
        } catch (\Exception $e) {
            $this->error_message = $e->getMessage();
        } 
    }

    public function render()
    {
        return view('livewire.show-questions', [
            'questions' => $this->fetchData($this->current_page),
        ]);
    }

}
