<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Gbx extends Model
{
    protected $fillable = [
        'company_id',
        'network',
        'SDF',
        'central_id',
        'comune',
        'client',
        'coordinates',
        'appointment_date',
        'inspection_date',
        'verbal_date',
        'is_adeguate',
        'obligation_date',
        'release_date',
        'project_date',
        'speedark_date',
        'permissions',
        'permission_request_date',
        'permission_obtain_date',
        'CO_advancement',
        'cart_update_date',
        'value',
        'company_paid',
        'bezzi_paid',
        'project_paid',
        'dl_paid',
        'inspection_notes',
        'permission_notes',
        'project_notes',
        'client_notes',
    ];

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function central()
    {
        return $this->belongsTo(Central::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
