<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;

class UserTable extends Component
{
    public $users;

    public function addRole($id,$role){
        $this->users->where("id",$id)->first()->assignRole($role);
    }

    public function removeRole($id,$role){
        $this->users->where("id",$id)->first()->removeRole($role);  
    }

    public function deleteUser($id){
        $this->users->where("id",$id)->first()->delete();  
    }

    public function mount(){
    }
    public function render()
    {
        $this->users = User::all();
        return view('livewire.user-table');
    }
}
