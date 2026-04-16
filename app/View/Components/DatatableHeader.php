<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class DatatableHeader extends Component
{
    public function __construct(
        public string $title,
        public ?string $createRoute = null,
        public string $addLabel = 'Add Data',
        public bool $withHistory = false,
    ) {}

    public function render(): View|Closure|string
    {
        return view('components.datatable-header');
    }
}
