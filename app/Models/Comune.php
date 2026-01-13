<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comune extends Model
{
    protected $table = 'comuni';
    protected $fillable = [
        'id',
        'code',
        'name',
        'location',
        'region',
        'sovracomune',
        'catasto_code',
    ];

    public function regione()
    {
        return $this->belongsTo(Regione::class, 'regione_id');
    }

    public function permessiEnte()
    {
        return $this->hasMany(PermessoEnte::class, 'comune_id');
    }    protected $casts = [
        'consegna' => 'date:Y-m-d',
        'data_fl' => 'date:Y-m-d',
        'data_ra' => 'date:Y-m-d',
        'evaso_dl' => 'date:Y-m-d',
        'mese_saldo' => 'date:Y-m',
        'ap_chiusini' => 'boolean',
        'scavo_100' => 'boolean',
        'urgente' => 'boolean',
        'ordinaria' => 'boolean',
        'fine_lavori' => 'boolean',
        'ra' => 'boolean',
    ];
}
