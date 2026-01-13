<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Regione extends Model
{
    use HasFactory;

    protected $table = 'regioni';

    protected $fillable = [
        'nome',
        'sigla',
    ];

    public function comuni()
    {
        return $this->hasMany(Comune::class, 'regione_id');
    }

    public function permessiEnte()
    {
        return $this->hasMany(PermessoEnte::class, 'regione_id');
    }
}
