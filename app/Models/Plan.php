<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    public function taxonomy()
    {
        return $this->belongsTo('App\Models\Taxonomy');
    }
}
