<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;

class NotificationCenter extends Component
{
    public $message;
    public $show = false;
    #[On('workUpdated')] 
     public function flashMessage(){
        $this->show = true;
        $this->message = "Lavorazione aggiornata con successo!";
     }
    public function render()
    {
        return view('livewire.notification-center');
    }
}
