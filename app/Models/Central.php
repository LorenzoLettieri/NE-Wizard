<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Central extends Model
{
    protected $fillable = [
        'central',
        'region'
    ];

    public function works(){
        return $this->hasMany(Work::class);
    }
}
