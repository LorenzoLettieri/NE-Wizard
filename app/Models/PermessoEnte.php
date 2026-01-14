<?php

namespace App\Models;

use App\Models\Central;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PermessoEnte extends Model
{
    use HasFactory;

    protected $table = 'permessi_ente';

    protected $fillable = [
        'network',
        'consegna',
        'progetto',
        'via',
        'descrizione',
        'ap_chiusini',
        'num_chiusini',
        'scavo_fino_100m',
        'quote_aggiuntive',
        'urgente',
        'ordinaria',
        'fine_lavori',
        'data_fl',
        'ra',
        'data_ra',
        'evaso_dal_dl',
        'mese_saldo',
        'al_dl',
        'a_ne',
        'delta',
        'vdc1',
        'vdc2',
        'vdc3',
        'vdc4',
        'regione_id',
        'comune_id',
        'central_id',
        'status',
        'acception_date',
        'delivery_date',
        'completion_date',
    ];

    protected $casts = [
        'consegna' => 'date',
        'data_fl' => 'date',
        'data_ra' => 'date',
        'evaso_dal_dl' => 'date',
        'mese_saldo' => 'date',
        'ap_chiusini' => 'boolean',
        'scavo_fino_100m' => 'boolean',
        'urgente' => 'boolean',
        'ordinaria' => 'boolean',
        'fine_lavori' => 'boolean',
        'ra' => 'boolean',
        'acception_date' => 'datetime',
        'delivery_date' => 'datetime',
        'completion_date' => 'datetime',
    ];
    /**
     * RELAZIONI
     */

    public function regione()
    {
        return $this->belongsTo(Regione::class);
    }

    public function comune()
    {
        return $this->belongsTo(Comune::class);
    }

    public function central()
    {
        return $this->belongsTo(Central::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
