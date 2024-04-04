<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function website()
    {
        return $this->belongsTo(Website::class, 'website_id');
    }

    public function changes()
    {
        return $this->hasMany(Change::class);
    }
}
