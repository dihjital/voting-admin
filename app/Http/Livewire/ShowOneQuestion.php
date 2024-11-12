<?php

namespace App\Http\Livewire;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

use Laravel\Jetstream\InteractsWithBanner;

use Livewire\Component;
use Livewire\WithFileUploads;

use App\Http\Livewire\Traits\WithLogin;
use App\Http\Livewire\Traits\WithUUIDSession;
use App\Http\Livewire\Traits\WithErrorMessage;
use App\Http\Livewire\Traits\WithRESTApiCalls;
use Illuminate\Http\Client\PendingRequest;

class ShowOneQuestion extends Component
{
    use InteractsWithBanner;
    use WithErrorMessage;
    use WithLogin;
    use WithUUIDSession;
    use WithFileUploads;
    use WithRESTApiCalls;

    public $question_id;
    public $question_text;
    public $question_closed = false;
    public $correct_vote;

    public $votes;
    public $vote_id;
    public $vote_text;
    public $vote_image;
    public $image_url;

    public array $letters; // Letters for each vote
    
    public $reset_number_of_votes = false; // If set to true, updating a vote will reset the number_of_votes to 0 

    // Modal controllers
    public $confirm_delete = false;
    public $update_vote = false;
    public $new_vote = false;
    public $show_image = false;

    protected $rules = [
        'vote_text' => 'required|min:6',
        'vote_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    ];

    public function mount($question_id)
    {
        $this->question_id = $question_id;
        $this->letters = range('A', 'Z');
        
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

            $this->extractQuestionProperties($this->getQuestion($this->question_id));

            // Inform the page that new data has been fetched
            $this->emit('data-fetched');
        } catch (\Exception $e) {
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }
    }

    protected function extractQuestionProperties(?array $question): void
    {
        $this->question_text = $question['question_text'];
        $this->question_closed = $question['is_closed'] ?? false;
        $this->correct_vote = $question['correct_vote'] ?? null;
    }

    public function toggleShowImageModal($vote_id)
    {
        if (! $vote_id) return null;

        $this->show_image = ! $this->show_image;

        $vote = array_values(
            array_map(
                fn($vote) => [
                    'vote_text' => $vote['vote_text'], 
                    'image_url' => $vote['image_url'],
                ], 
                array_filter(
                    $this->votes, 
                    function($vote) use ($vote_id) {
                        return $vote['id'] === $vote_id;
                    }
                )
            )
        );

        list('vote_text' => $this->vote_text, 'image_url' => $this->image_url) = 
            count($vote) && 
            array_key_exists('vote_text', $vote[0]) && 
            array_key_exists('image_url', $vote[0])
                ? array_pop($vote)
                : [
                    'vote_text' => null, 
                    'image_url' => null,
                ];
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

        $this->vote_image = null;

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
            $this->image_url = $response->json()['image_url'];
        } catch (\Exception $e) {
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }
    }

    public function toggleCreateVoteModal()
    {
        $this->resetErrorBag();
        $this->resetValidation();

        $this->vote_text = '';
        $this->vote_image = null;
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

            $request = Http::withToken($this->access_token)
                ->withHeaders([
                    'session-id' => $this->session_id,
                ])
                ->retry(3, 500, function (\Exception $e, PendingRequest $request) {
                    return $this->retryCallback($e, $request);
                });

            if ($this->vote_image) {
                $request
                    ->attach(
                        'image',
                        file_get_contents($this->vote_image->path()),
                        $this->vote_image->getClientOriginalName()
                    );
            }

            $response = 
                $request
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

            $request = Http::withToken($this->access_token)
                ->withHeaders([
                    'session-id' => $this->session_id,
                ])
                ->retry(3, 500, function (\Exception $e, PendingRequest $request) {
                    return $this->retryCallback($e, $request);
                });

            $payload = [
                'vote_text' => $this->vote_text,
                'number_of_votes' => $this->reset_number_of_votes ? 0 : null, // if it is set to null then do not reset ...
            ];

            if ($this->vote_image) {
                $request
                    ->attach(
                        'image',
                        file_get_contents($this->vote_image->path()),
                        $this->vote_image->getClientOriginalName()
                    );
            }

            $response = $this->vote_image 
                ? $request->post($url, $payload)->throwUnlessStatus(200) // Need to post it if we have a file to upload ...
                : $request->put($url, $payload)->throwUnlessStatus(200);

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

    public function deleteVoteImage($vote_id) 
    {
        $vote_id ??= $this->vote_id;

        // Delete the image attaced to the selected vote ...
        try {
            $url = config('services.api.endpoint',
                fn() => throw new \Exception('No API endpoint is defined')
            ).'/questions/'.$this->question_id.'/votes/'.$vote_id.'/image';

            $response = Http::withToken($this->access_token)
                ->withHeaders([
                    'session-id' => $this->session_id,
                ])
                ->retry(3, 500, function (\Exception $e, PendingRequest $request) {
                    return $this->retryCallback($e, $request);
                })
                ->delete($url)
                ->throwUnlessStatus(200);

            $this->banner(__('Vote image successfully deleted'));
        } catch (\Exception $e) {
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }

        $this->image_url = null;
    }

    public function render()
    {
        $this->fetchData();
        return view('livewire.show-one-question');
    }
}