<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Central extends Model
{
    protected $table = 'centrals';
    protected $fillable = [
        'central',
        'region'
    ];

    public function works(){
        return $this->hasMany(Work::class);
    }

    public function permessiEnte(){
        return $this->hasMany(PermessoEnte::class, 'central_id');
    }
}
