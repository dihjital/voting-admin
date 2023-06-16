<?php

namespace App\Http\Livewire;

use App\Http\Livewire\Traits\WithLogin;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;

use Livewire\Component;
use Livewire\WithPagination;

class ShowQuestions extends Component
{

    use WithPagination, WithLogin;

    protected $paginator;
    public $error_message;

    const URL = 'http://localhost:8000';

    public function mount()
    {
        list($this->access_token, $this->refresh_token) = $this->login();
        $this->fetchData();
    }

    public static function getURL(): string
    {
        return self::URL;
    }

    public function toggleCreateQuestionModal()
    {

    }

    public function toggleDeleteQuestionModal($question_id)
    {

    }

    public function toggleUpdateQuestionModal($question_id)
    {

    }

    public function gotoPage($page, $pageName = 'page')
    {
        $this->setPage($page, $pageName);
        $this->fetchData($page);
    }

    public function nextPage($pageName = 'page')
    {
        $this->setPage($this->paginators[$pageName] + 1, $pageName);
        $this->fetchData($this->paginators[$pageName]);
    }

    public function previousPage($pageName = 'page')
    {
        $this->setPage(max($this->paginators[$pageName] - 1, 1), $pageName);
        $this->fetchData($this->paginators[$pageName]);
    }

    public function fetchData($page = null)
    {
        // Query the API endpoint with pagination ...
        $currentPage = $page ?? request('page', 1);
        
        $response = Http::get(self::URL.'/questions?page='.$currentPage)->json();

        $this->paginator = new LengthAwarePaginator(
            collect($response['data']),
            $response['total'],
            $response['per_page'],
            $response['current_page'],
            ['path' => url('/questions')]
        );
    
        /* $data = collect($response->json()); // Decodes the JSON response into an array
        $this->questions = $data->paginate(5);

        dd($this->questions); */
    }

    public function render()
    {
        return view('livewire.show-questions', [
            'questions' => $this->paginator,
        ]);
    }

}
