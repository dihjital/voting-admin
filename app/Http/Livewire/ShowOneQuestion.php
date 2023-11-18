<?php

namespace App\Http\Livewire;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

use Laravel\Jetstream\InteractsWithBanner;

use Livewire\Component;

use App\Http\Livewire\Traits\WithLogin;
use App\Http\Livewire\Traits\WithUUIDSession;
use App\Http\Livewire\Traits\WithErrorMessage;

use Illuminate\Http\Client\PendingRequest;

class ShowOneQuestion extends Component
{
    use InteractsWithBanner, WithErrorMessage, WithLogin, WithUUIDSession;

    public $question_id;
    public $question_text;
    public $question_closed = false;

    public $votes;
    public $vote_id;
    public $vote_text;
    
    public $reset_number_of_votes = false; // if set to true, updating a vote will reset the number_of_votes to 0 

    // Modal controllers
    public $confirm_delete = false;
    public $update_vote = false;
    public $new_vote = false;

    protected $rules = [
        'vote_text' => 'required|min:6',
    ];

    public function mount($question_id)
    {
        $this->question_id = $question_id;
        
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

    public function closedColor($button = 'modify')
    {
        return [
            'modify' => [
                '0' => 'bg-indigo-500 hover:bg-indigo-600',
                '1' => 'bg-gray-500 hover:bg-gray-600',
            ],
            'vote' => [
                '0' => 'bg-indigo-500 hover:bg-indigo-600',
                '1' => 'bg-gray-500 hover:bg-gray-600',
            ],
            'delete' => [
                '0' => 'bg-red-500 hover:bg-red-600',
                '1' => 'bg-gray-500 hover:bg-gray-600',
            ],
        ][$button][$this->question_closed ?? 0];
    }

    public function fetchData()
    {
        try {
            // Get the votes ...
            $url = config('services.api.endpoint',
                fn() => throw new \Exception('No API endpoint is defined')
            ).'/questions/'.$this->question_id.'/votes';

            $response = Http::withToken($this->access_token)
                ->withHeaders([
                    'session-id' => $this->session_id
                ])
                ->retry(3, 500, function (\Exception $e, PendingRequest $request) {
                    return $this->retryCallback($e, $request);
                })
                ->get($url)
                ->throwUnlessStatus(200);

            $this->votes = $response->json();

            // Get the question text and whether it is open for any modification ...
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
                ->get($url)
                ->throwUnlessStatus(200);
            
            $this->question_text = $response->json()['question_text'];
            $this->question_closed = $response->json()['is_closed'] ?? false;
            
            // Inform the page that new data has been fetched
            $this->emit('data-fetched');
        } catch (\Exception $e) {
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }
    }

    public function toggleDeleteVoteModal($vote_id)
    {
        // Trigger modal and set the $vote_id
        $this->confirm_delete = ! $this->confirm_delete;
        $this->vote_id = $vote_id;
    }

    public function toggleUpdateVoteModal($vote_id)
    {
        $this->resetErrorBag();
        $this->resetValidation();

        $this->update_vote = !$this->update_vote;
        $this->vote_id = $vote_id;

        // Get the selected vote text...
        try {
            $url = config('services.api.endpoint',
                fn() => throw new \Exception('No API endpoint is defined')
            ).'/questions/'.$this->question_id.'/votes/'.$vote_id;

            $response = Http::withToken($this->access_token)
                ->withHeaders([
                    'session-id' => $this->session_id
                ])
                ->retry(3, 500, function (\Exception $e, PendingRequest $request) {
                    return $this->retryCallback($e, $request);
                })
                ->get($url)
                ->throwUnlessStatus(200);
            
            $this->vote_text = $response->json()['vote_text'];
        } catch (\Exception $e) {
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }
    }

    public function toggleCreateVoteModal()
    {
        $this->resetErrorBag();
        $this->resetValidation();

        $this->vote_text = '';
        $this->new_vote = ! $this->new_vote;
    }

    public function vote($vote_id)
    {
        try {
            $url = config('services.api.endpoint',
                fn() => throw new \Exception('No API endpoint is defined')
            ).'/questions/'.$this->question_id.'/votes/'.$vote_id;

            $response = Http::withToken($this->access_token)
                ->withHeaders([
                    'session-id' => $this->session_id
                ])
                ->retry(3, 500, function (\Exception $e, PendingRequest $request) {
                    return $this->retryCallback($e, $request);
                })
                ->patch($url)
                ->throwUnlessStatus(200);

            $this->banner(__('Successful vote'));
        } catch (\Exception $e) {
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }
    }

    public function create()
    {
        $this->validate();

        // Create a new vote ...
        try {
            $url = config('services.api.endpoint',
                fn() => throw new \Exception('No API endpoint is defined')
            ).'/questions/'.$this->question_id.'/votes';

            $response = Http::withToken($this->access_token)
                ->withHeaders([
                    'session-id' => $this->session_id,
                ])
                ->retry(3, 500, function (\Exception $e, PendingRequest $request) {
                    return $this->retryCallback($e, $request);
                })
                ->post($url, [
                    'vote_text' => $this->vote_text,
                    'number_of_votes' => 0,
                ])
                ->throwUnlessStatus(201);

            $this->banner(__('Vote successfully created'));
            $this->emit('confirming-vote-create');
        } catch (\Exception $e) {
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }

        $this->new_vote = ! $this->new_vote;
    }

    public function update($vote_id)
    {
        $vote_id ??= $this->vote_id;

        $this->validate();

        // Update the selected vote ...
        try {
            $url = config('services.api.endpoint',
                fn() => throw new \Exception('No API endpoint is defined')
            ).'/questions/'.$this->question_id.'/votes/'.$vote_id;

            $response = Http::withToken($this->access_token)
                ->withHeaders([
                    'session-id' => $this->session_id,
                ])
                ->retry(3, 500, function (\Exception $e, PendingRequest $request) {
                    return $this->retryCallback($e, $request);
                })
                ->put($url, [
                    'vote_text' => $this->vote_text,
                    'number_of_votes' => $this->reset_number_of_votes ? 0 : null, // if it is set to null then do not reset ...
                ])
                ->throwUnlessStatus(200);

            $this->banner(__('Vote successfully updated'));
            $this->emit('confirming-vote-text-update');
        } catch (\Exception $e) {
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }

        $this->reset_number_of_votes = false; // Default value
        $this->update_vote = ! $this->update_vote;
    }

    public function delete($vote_id) 
    {
        $vote_id ??= $this->vote_id;

        // Delete the selected vote ...
        try {
            $url = config('services.api.endpoint',
                fn() => throw new \Exception('No API endpoint is defined')
            ).'/questions/'.$this->question_id.'/votes/'.$vote_id;

            $response = Http::withToken($this->access_token)
                ->withHeaders([
                    'session-id' => $this->session_id,
                ])
                ->retry(3, 500, function (\Exception $e, PendingRequest $request) {
                    return $this->retryCallback($e, $request);
                })
                ->delete($url)
                ->throwUnlessStatus(200);

            $this->banner(__('Vote successfully deleted'));
        } catch (\Exception $e) {
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }

        $this->confirm_delete = !$this->confirm_delete;
    }

    public function render()
    {
        $this->fetchData();
        return view('livewire.show-one-question');
    }
}