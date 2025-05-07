<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Work extends Model
{
    
    protected $fillable = [
        'company_id','status','network','ao_cno','description','ntw_scope','type','phase','company_assistant','completion_date','acception_date','delivery_date','nroe','wo_number','unica_number','suspension_history','notes'
    ];
}
