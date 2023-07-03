<?php

namespace App\Http\Livewire;

use App\Http\Livewire\Traits\WithLogin;
use Livewire\Component;
use Illuminate\Support\Facades\Http;
use Laravel\Jetstream\InteractsWithBanner;

class ShowOneQuestion extends Component
{

    use InteractsWithBanner, WithLogin;

    public $question_text;
    public $question_id;
    public $votes;
    public $vote_id;
    public $vote_text;

    public $error_message;

    // Modal controllers
    public $confirm_delete = false;
    public $update_vote = false;
    public $new_vote = false;

    const URL = 'http://localhost:8000';

    protected $rules = [
        'vote_text' => 'required|min:6',
    ];

    public static function getURL(): string
    {
        return env('API_ENDPOINT', self::URL);
    }

    public function mount($question_id)
    {
        $this->question_id = $question_id;
        list($this->access_token, $this->refresh_token) = $this->login();
    }

    public function fetchData()
    {
        try {
            // Get the votes ...
            $response = Http::get(self::getURL().'/questions/'.$this->question_id.'/votes');
            $this->votes = $response->json();

            // Get the question text ..
            $response = Http::get(self::getURL().'/questions/'.$this->question_id);
            $this->question_text = $response->json()['question_text'];
        } catch (\Exception $e) {
            $this->error_message = $e->getMessage();
        }
    }

    public function toggleDeleteVoteModal($vote_id)
    {
        // trigger modal and set the $vote_id
        $this->confirm_delete = !$this->confirm_delete;
        $this->vote_id = $vote_id;
    }

    public function toggleUpdateVoteModal($vote_id)
    {
        $this->resetErrorBag();
        $this->resetValidation();

        $this->update_vote = !$this->update_vote;
        $this->vote_id = $vote_id;
        
        try {
            // Get the selected vote text...
            $response = Http::get(self::getURL().'/questions/'.$this->question_id.'/votes/'.$vote_id);
            $this->vote_text = $response->json()['vote_text'];
        } catch (\Exception $e) {
            $this->error_message = $e->getMessage();
        }
    }

    public function toggleCreateVoteModal()
    {
        $this->resetErrorBag();
        $this->resetValidation();

        $this->vote_text = '';
        $this->new_vote = !$this->new_vote;
    }

    public function vote($vote_id)
    {
        try {
            // Vote ...
            $response = Http::patch(self::getURL().'/questions/'.$this->question_id.'/votes/'.$vote_id);

            if ($response->status() !== 200) {
                throw new \Exception("Return HTTP status code is not 200!");
            }

            $this->banner(__('Successful vote'));
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
                ->post(self::getURL().'/questions/'.$this->question_id.'/votes', [
                    'vote_text' => $this->vote_text,
                    'number_of_votes' => 0,
                ]);

            if (!in_array($response->status(), [200, 201])) {
                throw new \Exception("Return HTTP status code is not ".implode(' or ', [200, 201]));
            }

            $this->banner(__('Vote successfully created'));
            $this->emit('confirming-vote-create');
        } catch (\Exception $e) {
            $this->error_message = $e->getMessage();
        }

        $this->new_vote = !$this->new_vote;
    }

    public function update($vote_id)
    {
        $vote_id ??= $this->vote_id;

        $this->validate();

        if (!$this->access_token) {
            list($this->access_token, $this->refresh_token) = $this->login();
        }

        try {
            // Update the selected vote ...
            $response = Http::withToken($this->access_token)
                ->put(self::getURL().'/questions/'.$this->question_id.'/votes/'.$vote_id, [
                    'vote_text' => $this->vote_text,
                    'number_of_votes' => 0,
                ]);

            if ($response->status() !== 200) {
                throw new \Exception("Return HTTP status code is not 200!");
            }

            $this->banner(__('Vote successfully updated'));
            $this->emit('confirming-vote-text-update');
        } catch (\Exception $e) {
            $this->error_message = $e->getMessage();
        }

        $this->update_vote = !$this->update_vote;
    }

    public function delete($vote_id) 
    {
        $vote_id ??= $this->vote_id;

        if (!$this->access_token) {
            list($this->access_token, $this->refresh_token) = $this->login();
        }

        try {
            // Delete the selected vote ...
            $response = Http::withToken($this->access_token)
                ->delete(self::getURL().'/questions/'.$this->question_id.'/votes/'.$vote_id);

            if ($response->status() !== 200) {
                throw new \Exception("Return HTTP status code is not 200!");
            }

            $this->banner(__('Vote successfully deleted'));
        } catch (\Exception $e) {
            $this->error_message = $e->getMessage();
        }

        $this->confirm_delete = !$this->confirm_delete;
    }

    public function render()
    {
        $this->fetchData();
        return view('livewire.show-one-question');
    }

}