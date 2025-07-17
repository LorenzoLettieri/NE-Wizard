<?php

namespace App\Models;

use Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute as CastsAttribute;
use Illuminate\Database\Eloquent\SoftDeletes;

class Work extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'company_id','central_id','status','network','ao_cno','description','ntw_scope','type','phase','company_assistant','completion_date','acception_date','delivery_date','nroe','wo_number','unica_number','suspension_history','notes'
    ];

   public function users(){
    return $this->belongsToMany(User::class);
   }
   public function company(){
    return $this->belongsTo(Company::class);
   }

   public function central(){
    return $this->belongsTo(Central::class);
   }



}
