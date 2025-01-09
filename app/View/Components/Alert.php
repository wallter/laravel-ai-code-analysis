<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Alert extends Component
{
    /**
     * Create a new component instance.
     *
     * @param  string  $class  Optional additional CSS classes.
     * @return void
     */
    public function __construct(
        /**
         * The CSS classes for the alert.
         */
        public $class = ''
    ) {}

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return view('components.alert');
    }
}
