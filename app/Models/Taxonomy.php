<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Taxonomy extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function website() {
        return $this->belongsTo('App\Models\Website');
    }

    public function subcategory(){
        return $this->hasMany('Taxonomy', 'parent_term_id');
    }

}
