<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Decommissioning extends Model
{
    protected $fillable = [
        'clli',
        'central_id',
        'comune_id',
        'regione_id',
        'permesso_ente',
        'fl_permesso',
        'note_permesso',
        'progettista_id',
        'status',
        'data_il',
        'data_fl',
        'qty_1',
        'qty_2',
        'qty_3',
        'qty_4',
        'qty_5',
        'qty_6',
        'prog_amount_1',
        'prog_amount_2',
        'prog_amount_3',
        'prog_amount_4',
        'prog_amount_5',
        'prog_amount_6',
        'ne_amount_1',
        'ne_amount_2',
        'ne_amount_3',
        'ne_amount_4',
        'ne_amount_5',
        'ne_amount_6',
        'tot_prog',
        'tot_ne',
        'agio',
        'pagata_prog',
        'pagata_ne',
        'val',
    ];

    protected $casts = [
        'fl_permesso' => 'date',
        'data_il' => 'date',
        'data_fl' => 'date',
        'qty_1' => 'integer',
        'qty_2' => 'integer',
        'qty_3' => 'integer',
        'qty_4' => 'integer',
        'qty_5' => 'integer',
        'qty_6' => 'integer',
        'prog_amount_1' => 'decimal:2',
        'prog_amount_2' => 'decimal:2',
        'prog_amount_3' => 'decimal:2',
        'prog_amount_4' => 'decimal:2',
        'prog_amount_5' => 'decimal:2',
        'prog_amount_6' => 'decimal:2',
        'ne_amount_1' => 'decimal:2',
        'ne_amount_2' => 'decimal:2',
        'ne_amount_3' => 'decimal:2',
        'ne_amount_4' => 'decimal:2',
        'ne_amount_5' => 'decimal:2',
        'ne_amount_6' => 'decimal:2',
        'tot_prog' => 'decimal:2',
        'tot_ne' => 'decimal:2',
        'agio' => 'decimal:2',
        'pagata_prog' => 'boolean',
        'pagata_ne' => 'boolean',
        'val' => 'boolean',
    ];

    public function central(): BelongsTo
    {
        return $this->belongsTo(Central::class);
    }

    public function comune(): BelongsTo
    {
        return $this->belongsTo(Comune::class);
    }

    public function regione(): BelongsTo
    {
        return $this->belongsTo(Regione::class);
    }

    public function progettista(): BelongsTo
    {
        return $this->belongsTo(User::class, 'progettista_id');
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }
}
