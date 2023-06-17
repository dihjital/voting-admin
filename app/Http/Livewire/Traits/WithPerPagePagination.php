<?php

namespace App\Http\Livewire\Traits;

use Livewire\WithPagination;

trait WithPerPagePagination
{
    use WithPagination;

    public $current_page;

    public static function paginating(): bool
    {
        return self::getPAGINATING();
    }

    public function gotoPage($page, $pageName = 'page')
    {
        $this->setPage($page, $pageName);
        $this->current_page = $page;
    }

    public function nextPage($pageName = 'page')
    {
        $this->setPage($this->paginators[$pageName] + 1, $pageName);
        $this->current_page = $this->paginators[$pageName];
    }

    public function previousPage($pageName = 'page')
    {
        $this->setPage(max($this->paginators[$pageName] - 1, 1), $pageName);
        $this->current_page = $this->paginators[$pageName];
    }

}